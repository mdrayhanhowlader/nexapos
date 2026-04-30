<?php
switch ($action) {

    // ── List all returns ──────────────────────────────────────────────────────
    case 'list':
        $page    = max(1, (int)($_GET['page']   ?? 1));
        $perPage = (int)($_GET['per_page']       ?? 20);
        $search  = trim($_GET['search']          ?? '');
        $status  = trim($_GET['status']          ?? '');
        $from    = trim($_GET['from']            ?? '');
        $to      = trim($_GET['to']              ?? '');
        $where   = '1=1'; $params = [];
        if ($search) {
            $where  .= ' AND (r.reference LIKE ? OR o.invoice_no LIKE ? OR c.name LIKE ?)';
            $params  = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
        }
        if ($status) { $where .= ' AND r.status=?';                 $params[] = $status; }
        if ($from)   { $where .= ' AND DATE(r.created_at) >= ?';    $params[] = $from; }
        if ($to)     { $where .= ' AND DATE(r.created_at) <= ?';    $params[] = $to; }

        $total = (int)DB::fetch(
            "SELECT COUNT(*) as n FROM returns r
             JOIN orders o ON o.id=r.order_id
             LEFT JOIN customers c ON c.id=o.customer_id
             WHERE $where", $params
        )['n'];
        $pg = paginate($total, $page, $perPage);
        $rows = DB::fetchAll(
            "SELECT r.*, o.invoice_no, c.name AS customer_name, u.name AS cashier_name,
                    (SELECT COUNT(*) FROM return_items ri WHERE ri.return_id=r.id) AS item_count
             FROM returns r
             JOIN orders o ON o.id=r.order_id
             LEFT JOIN customers c ON c.id=o.customer_id
             LEFT JOIN users u ON u.id=r.user_id
             WHERE $where ORDER BY r.id DESC
             LIMIT {$pg['per_page']} OFFSET {$pg['offset']}", $params
        );
        Response::success(['returns' => $rows, 'pagination' => $pg]);
        break;

    // ── Get single return with items ─────────────────────────────────────────
    case 'get':
        $id  = (int)($_GET['id'] ?? 0);
        $row = DB::fetch(
            "SELECT r.*, o.invoice_no, c.name AS customer_name, u.name AS cashier_name
             FROM returns r
             JOIN orders o ON o.id=r.order_id
             LEFT JOIN customers c ON c.id=o.customer_id
             LEFT JOIN users u ON u.id=r.user_id
             WHERE r.id=?", [$id]
        );
        if (!$row) Response::error('Return not found', 404);
        $row['items'] = DB::fetchAll(
            "SELECT ri.*, p.image FROM return_items ri
             LEFT JOIN products p ON p.id=ri.product_id
             WHERE ri.return_id=?", [$id]
        );
        Response::success($row);
        break;

    // ── Look up an order to return against ───────────────────────────────────
    case 'get_order':
        $q  = trim($_GET['q'] ?? '');
        if (!$q) Response::error('Search term required');
        $order = DB::fetch(
            "SELECT o.*, c.name AS customer_name
             FROM orders o
             LEFT JOIN customers c ON c.id=o.customer_id
             WHERE (o.invoice_no=? OR o.id=?) AND o.status NOT IN ('cancelled','held')
             LIMIT 1",
            [$q, is_numeric($q) ? (int)$q : 0]
        );
        if (!$order) Response::error('Order not found');
        $order['items'] = DB::fetchAll(
            "SELECT oi.*, p.image,
                    COALESCE(
                        (SELECT SUM(ri.quantity) FROM return_items ri
                         JOIN returns r ON r.id=ri.return_id
                         WHERE ri.order_item_id=oi.id AND r.status='approved'),
                        0
                    ) AS returned_qty
             FROM order_items oi
             LEFT JOIN products p ON p.id=oi.product_id
             WHERE oi.order_id=?", [$order['id']]
        );
        // Filter out items already fully returned
        $order['items'] = array_values(array_filter($order['items'], function($i) {
            return ($i['quantity'] - $i['returned_qty']) > 0;
        }));
        Response::success($order);
        break;

    // ── Create a return ───────────────────────────────────────────────────────
    case 'save':
        $orderId      = (int)($_POST['order_id']      ?? 0);
        $reason       = trim($_POST['reason']         ?? 'Customer request');
        $refundMethod = trim($_POST['refund_method']  ?? 'cash');
        $note         = trim($_POST['note']           ?? '');
        $restock      = (int)($_POST['restock']       ?? 1);
        $items        = json_decode($_POST['items']   ?? '[]', true);

        if (!$orderId)       Response::error('Order ID required');
        if (empty($items))   Response::error('No items selected for return');

        $order = DB::fetch("SELECT * FROM orders WHERE id=?", [$orderId]);
        if (!$order) Response::error('Order not found', 404);
        if ($order['status'] === 'cancelled') Response::error('Cannot return a cancelled order');

        // Validate return quantities against original order
        foreach ($items as $item) {
            $oi = DB::fetch("SELECT * FROM order_items WHERE id=? AND order_id=?",
                [(int)$item['order_item_id'], $orderId]);
            if (!$oi) Response::error("Item #{$item['order_item_id']} not in this order");

            $alreadyReturned = (float)DB::fetch(
                "SELECT COALESCE(SUM(ri.quantity),0) AS v FROM return_items ri
                 JOIN returns r ON r.id=ri.return_id
                 WHERE ri.order_item_id=? AND r.status='approved'",
                [(int)$item['order_item_id']]
            )['v'];
            $maxReturn = $oi['quantity'] - $alreadyReturned;
            if ((float)$item['quantity'] > $maxReturn) {
                Response::error("Cannot return more than {$maxReturn} of \"{$oi['name']}\"");
            }
        }

        $refundTotal = 0;
        foreach ($items as $item) {
            $oi       = DB::fetch("SELECT * FROM order_items WHERE id=?", [(int)$item['order_item_id']]);
            $qty      = (float)$item['quantity'];
            $netPrice = (float)$oi['unit_price'] - (float)($oi['discount_amount'] ?? 0);
            $refundTotal += round($qty * $netPrice, 2);
        }

        $returnId = DB::transaction(function() use (
            $orderId, $reason, $refundMethod, $note, $restock, $items, $refundTotal, $order
        ) {
            $ref = generate_ref('RET');
            $rid = DB::insert('returns', [
                'order_id'      => $orderId,
                'reference'     => $ref,
                'reason'        => $reason,
                'refund_method' => $refundMethod,
                'refund_amount' => $refundTotal,
                'restocked'     => $restock,
                'status'        => 'approved',
                'note'          => $note,
                'user_id'       => Auth::id(),
            ]);

            foreach ($items as $item) {
                $oi      = DB::fetch("SELECT * FROM order_items WHERE id=?", [(int)$item['order_item_id']]);
                $qty      = (float)$item['quantity'];
                $netPrice = (float)$oi['unit_price'] - (float)($oi['discount_amount'] ?? 0);
                $subtotal = round($qty * $netPrice, 2);

                DB::insert('return_items', [
                    'return_id'     => $rid,
                    'order_item_id' => $oi['id'],
                    'product_id'    => $oi['product_id'],
                    'name'          => $oi['name'],
                    'quantity'      => $qty,
                    'unit_price'    => $netPrice,
                    'subtotal'      => $subtotal,
                    'restock'       => $restock,
                ]);

                // Restock inventory
                if ($restock && $oi['product_id']) {
                    $wid = $order['warehouse_id'] ?? 1;
                    $cur = DB::fetch(
                        "SELECT quantity FROM inventory WHERE product_id=? AND warehouse_id=?",
                        [$oi['product_id'], $wid]
                    );
                    $before = $cur ? (float)$cur['quantity'] : 0;
                    $after  = $before + $qty;
                    DB::query(
                        "INSERT INTO inventory (product_id,variant_id,warehouse_id,quantity)
                         VALUES (?,0,?,?) ON DUPLICATE KEY UPDATE quantity=?",
                        [$oi['product_id'], $wid, $after, $after]
                    );
                    DB::insert('stock_movements', [
                        'product_id'      => $oi['product_id'],
                        'warehouse_id'    => $wid,
                        'user_id'         => Auth::id(),
                        'type'            => 'return',
                        'reference'       => $ref,
                        'quantity'        => $qty,
                        'quantity_before' => $before,
                        'quantity_after'  => $after,
                        'note'            => "Return #{$ref}",
                    ]);
                }
            }

            // Update order status if fully returned
            $totalOrdered  = (float)DB::fetch("SELECT SUM(quantity) as v FROM order_items WHERE order_id=?", [$orderId])['v'];
            $totalReturned = (float)DB::fetch(
                "SELECT COALESCE(SUM(ri.quantity),0) AS v FROM return_items ri
                 JOIN returns r ON r.id=ri.return_id
                 WHERE r.order_id=? AND r.status='approved'", [$orderId]
            )['v'];
            if ($totalReturned >= $totalOrdered) {
                DB::update('orders', ['status' => 'refunded'], 'id=?', [$orderId]);
            }

            // ── Loyalty: deduct proportional points on return ─────────────────
            $loyaltyEnabled = DB::fetch("SELECT value FROM settings WHERE `key`='loyalty_enabled'")['value'] ?? '0';
            if ($loyaltyEnabled === '1' && !empty($order['customer_id'])) {
                $earnRow = DB::fetch(
                    "SELECT points FROM loyalty_transactions WHERE order_id=? AND type='earn' LIMIT 1",
                    [$orderId]
                );
                if ($earnRow && (int)$earnRow['points'] > 0) {
                    $orderTotal  = (float)$order['total'];
                    $ratio       = $orderTotal > 0 ? min(1.0, $refundTotal / $orderTotal) : 1.0;
                    $deductPts   = (int)floor((int)$earnRow['points'] * $ratio);
                    if ($deductPts > 0) {
                        $custRow    = DB::fetch("SELECT loyalty_points FROM customers WHERE id=?", [$order['customer_id']]);
                        $curBal     = $custRow ? (int)$custRow['loyalty_points'] : 0;
                        $newBal     = max(0, $curBal - $deductPts);
                        DB::insert('loyalty_transactions', [
                            'customer_id'  => $order['customer_id'],
                            'order_id'     => $orderId,
                            'type'         => 'adjust',
                            'points'       => -$deductPts,
                            'balance_after'=> $newBal,
                            'note'         => "Deducted on return {$ref}",
                        ]);
                        DB::query(
                            "UPDATE customers SET loyalty_points = ? WHERE id=?",
                            [$newBal, $order['customer_id']]
                        );
                    }
                }
            }

            log_activity('create_return', 'returns', "Return {$ref} for order #{$orderId}", $rid);
            return $rid;
        });

        $saved = DB::fetch(
            "SELECT r.*, o.invoice_no FROM returns r JOIN orders o ON o.id=r.order_id WHERE r.id=?",
            [$returnId]
        );
        $saved['items'] = DB::fetchAll("SELECT * FROM return_items WHERE return_id=?", [$returnId]);
        Response::success($saved, 'Return processed successfully');
        break;

    // ── Summary KPIs ──────────────────────────────────────────────────────────
    case 'summary':
        $today = date('Y-m-d');
        $month = date('Y-m-01');
        Response::success([
            'total'       => DB::fetch("SELECT COUNT(*) as n FROM returns WHERE status='approved'")['n'],
            'today'       => DB::fetch("SELECT COUNT(*) as n FROM returns WHERE status='approved' AND DATE(created_at)=?", [$today])['n'],
            'this_month'  => DB::fetch("SELECT COUNT(*) as n FROM returns WHERE status='approved' AND created_at>=?", [$month])['n'],
            'refunded'    => DB::fetch("SELECT COALESCE(SUM(refund_amount),0) as v FROM returns WHERE status='approved'")['v'],
            'month_refund'=> DB::fetch("SELECT COALESCE(SUM(refund_amount),0) as v FROM returns WHERE status='approved' AND created_at>=?", [$month])['v'],
        ]);
        break;

    default:
        Response::error("Unknown action: {$action}", 404);
}
