<?php
switch ($action) {

    case 'list':
        $page    = max(1, (int)($_GET['page']    ?? 1));
        $perPage = (int)($_GET['per_page']        ?? 25);
        $search  = trim($_GET['search']           ?? '');
        $catId   = (int)($_GET['category']        ?? 0);
        $alert   = (int)($_GET['low_stock']       ?? 0);
        $where   = "p.status='active'";
        $params  = [];
        if ($search) {
            $where  .= ' AND (p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)';
            $params  = array_merge($params, ["%$search%","%$search%","%$search%"]);
        }
        if ($catId) { $where .= ' AND p.category_id=?'; $params[] = $catId; }
        if ($alert) { $where .= ' AND COALESCE(i.quantity,0) <= p.stock_alert_qty'; }
        $total = (int)DB::fetch(
            "SELECT COUNT(*) as n FROM products p
             LEFT JOIN inventory i ON i.product_id=p.id AND i.warehouse_id=1
             WHERE $where", $params
        )['n'];
        $pg   = paginate($total, $page, $perPage);
        $rows = DB::fetchAll(
            "SELECT p.id, p.name, p.sku, p.barcode, p.unit,
                    p.cost_price, p.selling_price, p.stock_alert_qty,
                    p.track_stock, c.name AS category,
                    COALESCE(i.quantity,0) AS stock,
                    COALESCE(i.quantity,0) * p.cost_price AS stock_value
             FROM products p
             LEFT JOIN categories c ON c.id=p.category_id
             LEFT JOIN inventory i  ON i.product_id=p.id AND i.warehouse_id=1
             WHERE $where ORDER BY p.name ASC
             LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",
            $params
        );
        Response::success(['items' => $rows, 'pagination' => $pg]);
        break;

    case 'movements':
        $pid     = (int)($_GET['product_id'] ?? 0);
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = (int)($_GET['per_page']    ?? 20);
        $where   = $pid ? 'sm.product_id=?' : '1=1';
        $params  = $pid ? [$pid] : [];
        $total   = (int)DB::fetch(
            "SELECT COUNT(*) as n FROM stock_movements sm WHERE $where", $params
        )['n'];
        $pg   = paginate($total, $page, $perPage);
        $rows = DB::fetchAll(
            "SELECT sm.*, p.name AS product_name, u.name AS user_name
             FROM stock_movements sm
             JOIN products p ON p.id=sm.product_id
             LEFT JOIN users u ON u.id=sm.user_id
             WHERE $where ORDER BY sm.id DESC
             LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",
            $params
        );
        Response::success(['movements' => $rows, 'pagination' => $pg]);
        break;

    case 'adjust':
        Auth::requirePermission('inventory');
        $pid    = (int)($_POST['product_id'] ?? 0);
        $qty    = (float)($_POST['quantity']  ?? 0);
        $type   = $_POST['type'] ?? 'adjustment';
        $note   = $_POST['note'] ?? '';
        if (!$pid) Response::error('Product ID required');
        $v      = Validator::make($_POST, ['quantity' => 'required|numeric|min:0']);
        if ($v->fails()) Response::error($v->firstError());
        $cur    = DB::fetch("SELECT quantity FROM inventory WHERE product_id=? AND warehouse_id=1", [$pid]);
        $before = $cur ? (float)$cur['quantity'] : 0;
        $after  = match($type) {
            'add'    => $before + $qty,
            'remove' => max(0, $before - $qty),
            default  => $qty
        };
        DB::query(
            "INSERT INTO inventory (product_id,warehouse_id,quantity) VALUES (?,1,?)
             ON DUPLICATE KEY UPDATE quantity=?",
            [$pid, $after, $after]
        );
        DB::insert('stock_movements', [
            'product_id'      => $pid,
            'warehouse_id'    => 1,
            'user_id'         => Auth::id(),
            'type'            => 'adjustment',
            'quantity'        => $after - $before,
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'note'            => $note,
        ]);
        log_activity('stock_adjust', 'inventory', "Product #{$pid}: {$before} → {$after}", $pid);
        Response::success(['before' => $before, 'after' => $after], 'Stock adjusted');
        break;

    case 'transfer':
        Auth::requirePermission('inventory');
        $pid   = (int)($_POST['product_id']      ?? 0);
        $from  = (int)($_POST['from_warehouse']  ?? 0);
        $to    = (int)($_POST['to_warehouse']    ?? 0);
        $qty   = (float)($_POST['quantity']       ?? 0);
        if (!$pid || !$from || !$to || $qty <= 0) Response::error('Invalid transfer data');
        if ($from === $to) Response::error('Source and destination cannot be same');
        $fromStock = DB::fetch("SELECT quantity FROM inventory WHERE product_id=? AND warehouse_id=?", [$pid, $from]);
        if (!$fromStock || (float)$fromStock['quantity'] < $qty) Response::error('Insufficient stock');
        DB::transaction(function() use ($pid, $from, $to, $qty) {
            $fromQty = (float)DB::fetch("SELECT quantity FROM inventory WHERE product_id=? AND warehouse_id=?", [$pid, $from])['quantity'];
            $toRow   = DB::fetch("SELECT quantity FROM inventory WHERE product_id=? AND warehouse_id=?", [$pid, $to]);
            $toQty   = $toRow ? (float)$toRow['quantity'] : 0;
            DB::query("UPDATE inventory SET quantity=? WHERE product_id=? AND warehouse_id=?", [$fromQty - $qty, $pid, $from]);
            DB::query(
                "INSERT INTO inventory (product_id,warehouse_id,quantity) VALUES (?,?,?)
                 ON DUPLICATE KEY UPDATE quantity=?",
                [$pid, $to, $toQty + $qty, $toQty + $qty]
            );
            DB::insert('stock_movements', [
                'product_id' => $pid, 'warehouse_id' => $from,
                'user_id'    => Auth::id(), 'type' => 'transfer',
                'quantity'   => -$qty, 'quantity_before' => $fromQty, 'quantity_after' => $fromQty - $qty,
                'note'       => "Transfer to warehouse #{$to}",
            ]);
            DB::insert('stock_movements', [
                'product_id' => $pid, 'warehouse_id' => $to,
                'user_id'    => Auth::id(), 'type' => 'transfer',
                'quantity'   => $qty, 'quantity_before' => $toQty, 'quantity_after' => $toQty + $qty,
                'note'       => "Transfer from warehouse #{$from}",
            ]);
            Response::success(null, 'Transfer completed');
        });
        break;

    case 'warehouses':
        Response::success(DB::fetchAll("SELECT * FROM warehouses WHERE status='active' ORDER BY name"));
        break;

    case 'summary':
        Response::success([
            'total_products'  => DB::fetch("SELECT COUNT(*) as n FROM products WHERE status='active'")['n'],
            'total_stock_value'=> DB::fetch("SELECT COALESCE(SUM(p.cost_price * COALESCE(i.quantity,0)),0) as v FROM products p LEFT JOIN inventory i ON i.product_id=p.id AND i.warehouse_id=1 WHERE p.status='active'")['v'],
            'low_stock_count' => DB::fetch("SELECT COUNT(*) as n FROM products p LEFT JOIN inventory i ON i.product_id=p.id AND i.warehouse_id=1 WHERE p.track_stock=1 AND p.status='active' AND COALESCE(i.quantity,0)<=p.stock_alert_qty")['n'],
            'out_of_stock'    => DB::fetch("SELECT COUNT(*) as n FROM products p LEFT JOIN inventory i ON i.product_id=p.id AND i.warehouse_id=1 WHERE p.track_stock=1 AND p.status='active' AND COALESCE(i.quantity,0)=0")['n'],
        ]);
        break;

    default:
        Response::error("Unknown action: {$action}", 404);
}
