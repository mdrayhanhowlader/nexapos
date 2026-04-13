<?php
$db = DB::connect();

switch ($action) {

    case 'dashboard_stats':
        $today = date('Y-m-d');
        Response::success([
            'today_sales'     => DB::fetch("SELECT COALESCE(SUM(total),0) AS v FROM orders WHERE DATE(created_at)=? AND status='completed'", [$today])['v'],
            'today_orders'    => DB::fetch("SELECT COUNT(*) AS v FROM orders WHERE DATE(created_at)=?", [$today])['v'],
            'today_customers' => DB::fetch("SELECT COUNT(DISTINCT customer_id) AS v FROM orders WHERE DATE(created_at)=? AND customer_id IS NOT NULL", [$today])['v'],
            'low_stock'       => DB::fetch("SELECT COUNT(*) AS v FROM inventory i JOIN products p ON p.id=i.product_id WHERE i.quantity<=p.stock_alert_qty AND p.track_stock=1")['v'],
            'held_orders'     => DB::fetch("SELECT COUNT(*) AS v FROM held_orders WHERE cashier_id=?", [Auth::id()])['v'],
            'monthly_sales'   => DB::fetch("SELECT COALESCE(SUM(total),0) AS v FROM orders WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW()) AND status='completed'")['v'],
        ]);
        break;

    case 'scan_barcode':
        $barcode = trim($_GET['barcode'] ?? '');
        if (!$barcode) Response::error('No barcode');
        $product = DB::fetch(
            "SELECT p.*, c.name AS category, COALESCE(i.quantity,0) AS stock
             FROM products p
             LEFT JOIN categories c ON c.id=p.category_id
             LEFT JOIN inventory i ON i.product_id=p.id AND i.warehouse_id=1
             WHERE p.barcode=? AND p.status='active' LIMIT 1",
            [$barcode]
        );
        if (!$product) Response::error('Product not found for barcode: ' . $barcode, 404);
        Response::success(['type' => 'product', 'data' => $product]);
        break;

    case 'lookup_product':
        $q = trim($_GET['q'] ?? '');
        if (!$q) Response::error('No query');
        $rows = DB::fetchAll(
            "SELECT p.id, p.name, p.sku, p.barcode, p.selling_price AS price,
                    p.tax_rate, p.tax_inclusive, p.unit, p.image,
                    p.discount_allowed, p.track_stock, p.type,
                    c.name AS category, COALESCE(i.quantity,0) AS stock
             FROM products p
             LEFT JOIN categories c ON c.id=p.category_id
             LEFT JOIN inventory i ON i.product_id=p.id AND i.warehouse_id=1
             WHERE p.status='active' AND (p.barcode=? OR p.sku=? OR p.name LIKE ?)
             LIMIT 20",
            [$q, $q, "%$q%"]
        );
        Response::success($rows);
        break;

    case 'get_payment_methods':
        $rows = DB::fetchAll("SELECT * FROM payment_methods WHERE is_active=1 ORDER BY sort_order");
        Response::success($rows);
        break;

    case 'checkout':
        $payload  = json_decode(file_get_contents('php://input'), true);
        if (!$payload) Response::error('Invalid payload', 400);
        $items    = $payload['items']    ?? [];
        $payments = $payload['payments'] ?? [];
        if (empty($items))    Response::error('Cart is empty');
        if (empty($payments)) Response::error('No payment selected');

        $result = DB::transaction(function() use ($payload, $items, $payments) {
            $customerId = $payload['customer_id'] ?? null;
            $discType   = $payload['discount_type']  ?? 'fixed';
            $discValue  = (float)($payload['discount_value'] ?? 0);
            $subtotal   = 0; $taxTotal = 0;

            foreach ($items as &$item) {
                $price   = (float)$item['unit_price'];
                $qty     = (float)$item['quantity'];
                $taxRate = (float)($item['tax_rate'] ?? 0);
                $iDisc   = (float)($item['discount_amount'] ?? 0);
                $lineSub = ($price - $iDisc) * $qty;
                $taxAmt  = $lineSub * ($taxRate / 100);
                $item['tax_amount'] = round($taxAmt, 2);
                $item['subtotal']   = round($lineSub, 2);
                $subtotal += $lineSub;
                $taxTotal += $taxAmt;
            }
            unset($item);

            $discAmount    = $discType === 'percent' ? round($subtotal * ($discValue / 100), 2) : min((float)$discValue, $subtotal);
            $serviceCharge = (float)($payload['service_charge'] ?? 0);
            $total         = round($subtotal - $discAmount + $taxTotal + $serviceCharge, 2);
            $paid          = array_sum(array_column($payments, 'amount'));
            $changeDue     = max(0, round($paid - $total, 2));
            $due           = max(0, round($total - $paid, 2));
            $invoiceNo     = generate_invoice();
            $shift         = DB::fetch("SELECT id FROM shifts WHERE cashier_id=? AND status='open' LIMIT 1", [Auth::id()]);

            $orderId = DB::insert('orders', [
                'invoice_no'      => $invoiceNo,
                'outlet_id'       => 1,
                'customer_id'     => $customerId,
                'cashier_id'      => Auth::id(),
                'warehouse_id'    => 1,
                'status'          => $due > 0 ? 'processing' : 'completed',
                'subtotal'        => $subtotal,
                'discount_type'   => $discType,
                'discount_value'  => $discValue,
                'discount_amount' => $discAmount,
                'tax_amount'      => $taxTotal,
                'service_charge'  => $serviceCharge,
                'total'           => $total,
                'paid'            => $paid,
                'change_due'      => $changeDue,
                'due'             => $due,
                'note'            => $payload['note'] ?? '',
                'shift_id'        => $shift['id'] ?? null,
            ]);

            foreach ($items as $item) {
                DB::insert('order_items', [
                    'order_id'        => $orderId,
                    'product_id'      => $item['product_id'],
                    'variant_id'      => $item['variant_id'] ?? null,
                    'name'            => $item['name'],
                    'sku'             => $item['sku']     ?? '',
                    'barcode'         => $item['barcode'] ?? '',
                    'quantity'        => $item['quantity'],
                    'unit'            => $item['unit']    ?? 'pcs',
                    'unit_price'      => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'tax_rate'        => $item['tax_rate']        ?? 0,
                    'tax_amount'      => $item['tax_amount'],
                    'subtotal'        => $item['subtotal'],
                ]);
                if (!empty($item['track_stock'])) {
                    $cur    = DB::fetch("SELECT quantity FROM inventory WHERE product_id=? AND warehouse_id=1", [$item['product_id']]);
                    $before = $cur ? (float)$cur['quantity'] : 0;
                    $after  = max(0, $before - (float)$item['quantity']);
                    DB::query("INSERT INTO inventory (product_id,variant_id,warehouse_id,quantity) VALUES (?,0,1,?) ON DUPLICATE KEY UPDATE quantity=?", [$item['product_id'], $after, $after]);
                    DB::insert('stock_movements', [
                        'product_id'      => $item['product_id'],
                        'warehouse_id'    => 1,
                        'user_id'         => Auth::id(),
                        'type'            => 'sale',
                        'reference'       => "ORD-{$orderId}",
                        'quantity'        => -$item['quantity'],
                        'quantity_before' => $before,
                        'quantity_after'  => $after,
                    ]);
                }
            }

            $openDrawer = false;
            foreach ($payments as $pay) {
                $method = DB::fetch("SELECT * FROM payment_methods WHERE id=?", [$pay['method_id']]);
                if ($method && $method['cash_drawer']) $openDrawer = true;
                DB::insert('payments', [
                    'order_id'          => $orderId,
                    'payment_method_id' => $pay['method_id'],
                    'amount'            => $pay['amount'],
                    'reference'         => $pay['reference']      ?? '',
                    'transaction_id'    => $pay['transaction_id'] ?? '',
                    'account_number'    => $pay['account_number'] ?? '',
                    'status'            => 'completed',
                ]);
            }

            if ($shift) DB::query("UPDATE shifts SET total_sales=total_sales+? WHERE id=?", [$total, $shift['id']]);
            log_activity('checkout', 'pos', "Order {$invoiceNo}", $orderId);

            return [
                'order_id'    => $orderId,
                'invoice_no'  => $invoiceNo,
                'total'       => $total,
                'paid'        => $paid,
                'change_due'  => $changeDue,
                'due'         => $due,
                'open_drawer' => $openDrawer,
            ];
        });
        Response::success($result, 'Sale completed');
        break;

    case 'hold_order':
        $payload = json_decode(file_get_contents('php://input'), true);
        $id = DB::insert('held_orders', [
            'label'           => $payload['label']           ?? 'Hold ' . date('H:i'),
            'cashier_id'      => Auth::id(),
            'customer_id'     => $payload['customer_id']     ?? null,
            'items'           => json_encode($payload['items']),
            'subtotal'        => $payload['subtotal']        ?? 0,
            'discount_amount' => $payload['discount_amount'] ?? 0,
            'total'           => $payload['total']           ?? 0,
            'note'            => $payload['note']            ?? '',
        ]);
        Response::success(['id' => $id], 'Order held');
        break;

    case 'get_held_orders':
        $rows = DB::fetchAll(
            "SELECT h.*, c.name AS customer_name FROM held_orders h
             LEFT JOIN customers c ON c.id=h.customer_id
             WHERE h.cashier_id=? ORDER BY h.created_at DESC",
            [Auth::id()]
        );
        foreach ($rows as &$r) $r['items'] = json_decode($r['items'], true);
        Response::success($rows);
        break;

    case 'delete_held_order':
        DB::delete('held_orders', 'id=? AND cashier_id=?', [$_POST['id'], Auth::id()]);
        Response::success(null, 'Removed');
        break;

    case 'get_receipt':
        $orderId  = (int)($_GET['id'] ?? 0);
        $order    = DB::fetch(
            "SELECT o.*, u.name AS cashier_name, c.name AS customer_name, c.phone AS customer_phone
             FROM orders o
             LEFT JOIN users u ON u.id=o.cashier_id
             LEFT JOIN customers c ON c.id=o.customer_id
             WHERE o.id=?", [$orderId]
        );
        if (!$order) Response::error('Order not found', 404);
        $items    = DB::fetchAll("SELECT * FROM order_items WHERE order_id=?", [$orderId]);
        $payments = DB::fetchAll(
            "SELECT p.*, pm.name AS method_name, pm.icon FROM payments p
             JOIN payment_methods pm ON pm.id=p.payment_method_id WHERE p.order_id=?", [$orderId]
        );
        $outlet = DB::fetch("SELECT * FROM outlets WHERE id=1");
        Response::success(compact('order', 'items', 'payments', 'outlet'));
        break;

    case 'search_customer':
        $q    = trim($_GET['q'] ?? '');
        $rows = DB::fetchAll(
            "SELECT id,name,phone,email,loyalty_points,outstanding_balance,discount_rate,`group`
             FROM customers WHERE status='active' AND (name LIKE ? OR phone LIKE ? OR code LIKE ?) LIMIT 10",
            ["%$q%", "%$q%", "%$q%"]
        );
        Response::success($rows);
        break;

    case 'quick_add_customer':
        $name  = trim($_POST['name']  ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if (!$name) Response::error('Name required');
        $id   = DB::insert('customers', ['code' => customer_code(), 'name' => $name, 'phone' => $phone]);
        Response::success(DB::fetch("SELECT * FROM customers WHERE id=?", [$id]), 'Customer added');
        break;

    case 'apply_discount_code':
        $code  = trim($_POST['code']  ?? '');
        $total = (float)($_POST['total'] ?? 0);
        $disc  = DB::fetch(
            "SELECT * FROM discounts WHERE code=? AND status='active'
             AND (start_date IS NULL OR start_date<=NOW())
             AND (end_date IS NULL OR end_date>=NOW())
             AND (usage_limit IS NULL OR used_count<usage_limit)", [$code]
        );
        if (!$disc) Response::error('Invalid or expired code');
        if ($total < $disc['min_order_amount']) Response::error("Minimum order ৳{$disc['min_order_amount']} required");
        $amount = $disc['type'] === 'percent'
            ? min($total * ($disc['value'] / 100), $disc['max_discount'] ?: PHP_FLOAT_MAX)
            : $disc['value'];
        Response::success(['discount_id' => $disc['id'], 'amount' => round($amount, 2), 'type' => $disc['type']]);
        break;

    case 'open_shift':
        $existing = DB::fetch("SELECT id FROM shifts WHERE cashier_id=? AND status='open'", [Auth::id()]);
        if ($existing) Response::error('Shift already open');
        $id = DB::insert('shifts', ['outlet_id' => 1, 'cashier_id' => Auth::id(), 'opening_balance' => (float)($_POST['opening_balance'] ?? 0)]);
        Response::success(['shift_id' => $id], 'Shift opened');
        break;

    case 'close_shift':
        $shift = DB::fetch("SELECT * FROM shifts WHERE cashier_id=? AND status='open'", [Auth::id()]);
        if (!$shift) Response::error('No open shift');
        $closing  = (float)($_POST['closing_balance'] ?? 0);
        $expected = $shift['opening_balance'] + $shift['total_sales'] + $shift['total_cash_in'] - $shift['total_cash_out'] - $shift['total_refunds'];
        DB::update('shifts', [
            'closing_balance'  => $closing,
            'expected_balance' => $expected,
            'difference'       => $closing - $expected,
            'closed_at'        => date('Y-m-d H:i:s'),
            'status'           => 'closed',
            'note'             => $_POST['note'] ?? '',
        ], 'id=?', [$shift['id']]);
        Response::success(['expected' => $expected, 'closing' => $closing, 'difference' => $closing - $expected], 'Shift closed');
        break;

    case 'get_shift_summary':
        $shift = DB::fetch("SELECT * FROM shifts WHERE cashier_id=? AND status='open'", [Auth::id()]);
        if (!$shift) Response::error('No open shift');
        $sales = DB::fetch("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as total FROM orders WHERE shift_id=? AND status='completed'", [$shift['id']]);
        $shift['orders_count'] = $sales['cnt'];
        $shift['sales_total']  = $sales['total'];
        Response::success($shift);
        break;

    case 'cash_in_out':
        $shift = DB::fetch("SELECT id FROM shifts WHERE cashier_id=? AND status='open'", [Auth::id()]);
        if (!$shift) Response::error('No open shift');
        $type   = $_POST['type'] === 'cash_out' ? 'cash_out' : 'cash_in';
        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount <= 0) Response::error('Invalid amount');
        DB::insert('cash_movements', ['shift_id' => $shift['id'], 'user_id' => Auth::id(), 'type' => $type, 'amount' => $amount, 'reason' => $_POST['reason'] ?? '']);
        $field = $type === 'cash_in' ? 'total_cash_in' : 'total_cash_out';
        DB::query("UPDATE shifts SET $field=$field+? WHERE id=?", [$amount, $shift['id']]);
        Response::success(null, ucfirst(str_replace('_', ' ', $type)) . ' recorded');
        break;

    default:
        Response::error("Unknown action: {$action}", 404);
}
