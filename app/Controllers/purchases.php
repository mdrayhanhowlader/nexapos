<?php
switch ($action) {

    case 'list':
        $page    = max(1, (int)($_GET['page']    ?? 1));
        $perPage = (int)($_GET['per_page']        ?? 20);
        $search  = trim($_GET['search']           ?? '');
        $status  = trim($_GET['status']           ?? '');
        $from    = trim($_GET['from']             ?? '');
        $to      = trim($_GET['to']               ?? '');
        $where   = '1=1';
        $params  = [];
        if ($search) {
            $where  .= ' AND (p.reference LIKE ? OR s.name LIKE ?)';
            $params  = array_merge($params, ["%$search%", "%$search%"]);
        }
        if ($status) { $where .= ' AND p.status=?';                 $params[] = $status; }
        if ($from)   { $where .= ' AND DATE(p.created_at) >= ?';    $params[] = $from; }
        if ($to)     { $where .= ' AND DATE(p.created_at) <= ?';    $params[] = $to; }
        $total = (int)DB::fetch(
            "SELECT COUNT(*) as n FROM purchases p
             LEFT JOIN suppliers s ON s.id=p.supplier_id WHERE $where", $params
        )['n'];
        $pg   = paginate($total, $page, $perPage);
        $rows = DB::fetchAll(
            "SELECT p.*, s.name AS supplier_name, u.name AS user_name
             FROM purchases p
             LEFT JOIN suppliers s ON s.id=p.supplier_id
             LEFT JOIN users u     ON u.id=p.user_id
             WHERE $where ORDER BY p.id DESC
             LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",
            $params
        );
        Response::success(['purchases' => $rows, 'pagination' => $pg]);
        break;

    case 'get':
        $id  = (int)($_GET['id'] ?? 0);
        $row = DB::fetch(
            "SELECT p.*, s.name AS supplier_name
             FROM purchases p
             LEFT JOIN suppliers s ON s.id=p.supplier_id
             WHERE p.id=?", [$id]
        );
        if (!$row) Response::error('Purchase not found', 404);
        $row['items'] = DB::fetchAll(
            "SELECT pi.*, pr.name AS product_name, pr.sku
             FROM purchase_items pi
             JOIN products pr ON pr.id=pi.product_id
             WHERE pi.purchase_id=?", [$id]
        );
        Response::success($row);
        break;

    case 'save':
        Auth::requirePermission('inventory');
        $id          = (int)($_POST['id']          ?? 0);
        $supplierId  = (int)($_POST['supplier_id'] ?? 0);
        $items       = json_decode($_POST['items'] ?? '[]', true);
        if (empty($items)) Response::error('No items provided');
        $subtotal = 0;
        $taxTotal = 0;
        foreach ($items as &$item) {
            $lineSub   = (float)$item['unit_cost'] * (float)$item['quantity'];
            $taxAmt    = $lineSub * ((float)($item['tax_rate'] ?? 0) / 100);
            $item['tax_amount'] = round($taxAmt, 2);
            $item['subtotal']   = round($lineSub, 2);
            $subtotal += $lineSub;
            $taxTotal += $taxAmt;
        }
        unset($item);
        $discount = (float)($_POST['discount_amount'] ?? 0);
        $shipping = (float)($_POST['shipping_cost']   ?? 0);
        $total    = round($subtotal + $taxTotal - $discount + $shipping, 2);
        $data = [
            'supplier_id'     => $supplierId ?: null,
            'warehouse_id'    => (int)($_POST['warehouse_id'] ?? 1),
            'user_id'         => Auth::id(),
            'status'          => $_POST['status']         ?? 'pending',
            'subtotal'        => $subtotal,
            'tax_amount'      => $taxTotal,
            'discount_amount' => $discount,
            'shipping_cost'   => $shipping,
            'total'           => $total,
            'paid'            => (float)($_POST['paid'] ?? 0),
            'due'             => max(0, $total - (float)($_POST['paid'] ?? 0)),
            'note'            => trim($_POST['note']          ?? ''),
            'expected_date'   => $_POST['expected_date']  ?: null,
        ];
        DB::transaction(function() use ($id, $data, $items) {
            if ($id) {
                DB::update('purchases', $data, 'id=?', [$id]);
                DB::delete('purchase_items', 'purchase_id=?', [$id]);
            } else {
                $data['reference'] = generate_ref('PO');
                $id = DB::insert('purchases', $data);
            }
            foreach ($items as $item) {
                DB::insert('purchase_items', [
                    'purchase_id' => $id,
                    'product_id'  => $item['product_id'],
                    'variant_id'  => $item['variant_id'] ?? null,
                    'quantity'    => $item['quantity'],
                    'unit_cost'   => $item['unit_cost'],
                    'tax_rate'    => $item['tax_rate']   ?? 0,
                    'tax_amount'  => $item['tax_amount'],
                    'subtotal'    => $item['subtotal'],
                ]);
            }
            log_activity('save_purchase', 'purchases', "Purchase #{$id}", $id);
            Response::success(['id' => $id], 'Purchase saved');
        });
        break;

    case 'receive':
        Auth::requirePermission('inventory');
        $id      = (int)($_POST['purchase_id'] ?? 0);
        $items   = json_decode($_POST['items'] ?? '[]', true);
        if (!$id || empty($items)) Response::error('Invalid data');
        $purchase = DB::fetch("SELECT * FROM purchases WHERE id=?", [$id]);
        if (!$purchase) Response::error('Purchase not found', 404);
        DB::transaction(function() use ($id, $items, $purchase) {
            foreach ($items as $item) {
                $receivedQty = (float)($item['received_qty'] ?? 0);
                if ($receivedQty <= 0) continue;
                DB::query(
                    "UPDATE purchase_items SET received_qty=received_qty+? WHERE id=?",
                    [$receivedQty, $item['id']]
                );
                $pid    = $item['product_id'];
                $wid    = $purchase['warehouse_id'] ?? 1;
                $cur    = DB::fetch("SELECT quantity FROM inventory WHERE product_id=? AND warehouse_id=?", [$pid, $wid]);
                $before = $cur ? (float)$cur['quantity'] : 0;
                $after  = $before + $receivedQty;
                DB::query(
                    "INSERT INTO inventory (product_id,warehouse_id,quantity) VALUES (?,?,?)
                     ON DUPLICATE KEY UPDATE quantity=?",
                    [$pid, $wid, $after, $after]
                );
                DB::insert('stock_movements', [
                    'product_id'      => $pid,
                    'warehouse_id'    => $wid,
                    'user_id'         => Auth::id(),
                    'type'            => 'purchase',
                    'reference'       => $purchase['reference'],
                    'quantity'        => $receivedQty,
                    'quantity_before' => $before,
                    'quantity_after'  => $after,
                    'cost_price'      => $item['unit_cost'] ?? 0,
                ]);
                DB::update('products', ['cost_price' => $item['unit_cost'] ?? 0], 'id=?', [$pid]);
            }
            DB::update('purchases', [
                'status'        => 'received',
                'received_date' => date('Y-m-d'),
            ], 'id=?', [$id]);
            log_activity('receive_purchase', 'purchases', "Received purchase #{$id}", $id);
            Response::success(null, 'Stock received successfully');
        });
        break;

    case 'delete':
        Auth::requirePermission('inventory');
        $id = (int)($_POST['id'] ?? 0);
        $purchase = DB::fetch("SELECT status FROM purchases WHERE id=?", [$id]);
        if (!$purchase) Response::error('Purchase not found', 404);
        if ($purchase['status'] === 'received') Response::error('Cannot delete a received purchase');
        DB::update('purchases', ['status' => 'cancelled'], 'id=?', [$id]);
        log_activity('delete_purchase', 'purchases', "Cancelled purchase #{$id}", $id);
        Response::success(null, 'Purchase cancelled');
        break;

    case 'summary':
        Response::success([
            'total'    => DB::fetch("SELECT COUNT(*) as n FROM purchases")['n'],
            'pending'  => DB::fetch("SELECT COUNT(*) as n FROM purchases WHERE status='pending'")['n'],
            'received' => DB::fetch("SELECT COUNT(*) as n FROM purchases WHERE status='received'")['n'],
            'due'      => DB::fetch("SELECT COALESCE(SUM(due),0) as v FROM purchases WHERE status!='cancelled'")['v'],
        ]);
        break;

    default:
        Response::error("Unknown action: {$action}", 404);
}
