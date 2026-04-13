<?php
switch ($action) {

    case 'list':
        $page    = max(1, (int)($_GET['page']    ?? 1));
        $perPage = (int)($_GET['per_page']        ?? 20);
        $search  = trim($_GET['search']           ?? '');
        $group   = trim($_GET['group']            ?? '');
        $where   = "status != 'blacklisted'";
        $params  = [];
        if ($search) {
            $where  .= ' AND (name LIKE ? OR phone LIKE ? OR email LIKE ? OR code LIKE ?)';
            $params  = array_merge($params, ["%$search%","%$search%","%$search%","%$search%"]);
        }
        if ($group) { $where .= ' AND `group`=?'; $params[] = $group; }
        $total = (int)DB::fetch("SELECT COUNT(*) as n FROM customers WHERE $where", $params)['n'];
        $pg    = paginate($total, $page, $perPage);
        $rows  = DB::fetchAll(
            "SELECT * FROM customers WHERE $where
             ORDER BY id DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",
            $params
        );
        Response::success(['customers' => $rows, 'pagination' => $pg]);
        break;

    case 'get':
        $id  = (int)($_GET['id'] ?? 0);
        $row = DB::fetch("SELECT * FROM customers WHERE id=?", [$id]);
        if (!$row) Response::error('Customer not found', 404);
        $row['recent_orders'] = DB::fetchAll(
            "SELECT id, invoice_no, total, status, created_at
             FROM orders WHERE customer_id=? ORDER BY id DESC LIMIT 10", [$id]
        );
        $row['loyalty_history'] = DB::fetchAll(
            "SELECT * FROM loyalty_transactions WHERE customer_id=? ORDER BY id DESC LIMIT 10", [$id]
        );
        Response::success($row);
        break;

    case 'save':
        $id    = (int)($_POST['id'] ?? 0);
        $name  = trim($_POST['name'] ?? '');
        if (!$name) Response::error('Customer name is required');
        $phone = trim($_POST['phone'] ?? '');
        if ($phone) {
            $exists = DB::fetch(
                "SELECT id FROM customers WHERE phone=?" . ($id ? " AND id!=?" : ""),
                $id ? [$phone, $id] : [$phone]
            );
            if ($exists) Response::error('Phone number already registered');
        }
        $data = [
            'name'          => $name,
            'email'         => trim($_POST['email']   ?? ''),
            'phone'         => $phone,
            'address'       => trim($_POST['address'] ?? ''),
            'date_of_birth' => $_POST['date_of_birth'] ?: null,
            'group'         => $_POST['group']         ?? 'regular',
            'discount_rate' => (float)($_POST['discount_rate'] ?? 0),
            'credit_limit'  => (float)($_POST['credit_limit']  ?? 0),
            'notes'         => trim($_POST['notes']    ?? ''),
            'status'        => 'active',
        ];
        if ($id) {
            DB::update('customers', $data, 'id=?', [$id]);
            log_activity('update_customer', 'customers', "Updated: {$name}", $id);
        } else {
            $data['code'] = customer_code();
            $id = DB::insert('customers', $data);
            log_activity('add_customer', 'customers', "Added: {$name}", $id);
        }
        Response::success(DB::fetch("SELECT * FROM customers WHERE id=?", [$id]), 'Customer saved');
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        DB::update('customers', ['status' => 'inactive'], 'id=?', [$id]);
        log_activity('delete_customer', 'customers', "Deleted customer #{$id}", $id);
        Response::success(null, 'Customer deleted');
        break;

    case 'adjust_points':
        $id     = (int)($_POST['customer_id'] ?? 0);
        $points = (int)($_POST['points']      ?? 0);
        $type   = $_POST['type'] ?? 'adjust';
        $note   = $_POST['note'] ?? '';
        if (!$id || !$points) Response::error('Invalid data');
        $cust = DB::fetch("SELECT loyalty_points FROM customers WHERE id=?", [$id]);
        if (!$cust) Response::error('Customer not found', 404);
        $newBalance = max(0, $cust['loyalty_points'] + ($type === 'redeem' ? -$points : $points));
        DB::update('customers', ['loyalty_points' => $newBalance], 'id=?', [$id]);
        DB::insert('loyalty_transactions', [
            'customer_id'  => $id,
            'type'         => $type,
            'points'       => $type === 'redeem' ? -$points : $points,
            'balance_after'=> $newBalance,
            'note'         => $note,
        ]);
        Response::success(['new_balance' => $newBalance], 'Points updated');
        break;

    case 'search':
        $q    = trim($_GET['q'] ?? '');
        if (!$q) Response::success([]);
        $rows = DB::fetchAll(
            "SELECT id, name, phone, email, loyalty_points,
                    outstanding_balance, discount_rate, `group`
             FROM customers
             WHERE status='active'
               AND (name LIKE ? OR phone LIKE ? OR code LIKE ?)
             LIMIT 10",
            ["%$q%", "%$q%", "%$q%"]
        );
        Response::success($rows);
        break;

    case 'guests':
        $page     = max(1, (int)($_GET['page']      ?? 1));
        $perPage  = (int)($_GET['per_page']           ?? 20);
        $search   = trim($_GET['search']              ?? '');
        $dateFrom = trim($_GET['date_from']           ?? '');
        $dateTo   = trim($_GET['date_to']             ?? '');
        $where    = "o.customer_id IS NULL AND o.status='completed'";
        $params   = [];
        if ($search) {
            $where   .= ' AND (o.invoice_no LIKE ? OR u.name LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($dateFrom) { $where .= ' AND DATE(o.created_at) >= ?'; $params[] = $dateFrom; }
        if ($dateTo)   { $where .= ' AND DATE(o.created_at) <= ?'; $params[] = $dateTo; }
        $total = (int)DB::fetch(
            "SELECT COUNT(*) as n FROM orders o
             LEFT JOIN users u ON u.id=o.cashier_id
             WHERE $where", $params
        )['n'];
        $pg   = paginate($total, $page, $perPage);
        $rows = DB::fetchAll(
            "SELECT o.id, o.invoice_no, o.total, o.paid, o.change_due, o.subtotal,
                    o.discount_amount, o.tax_amount, o.created_at,
                    u.name AS cashier_name,
                    (SELECT COUNT(*) FROM order_items WHERE order_id=o.id) AS item_count,
                    (SELECT GROUP_CONCAT(oi.name SEPARATOR ', ') FROM order_items oi WHERE oi.order_id=o.id LIMIT 3) AS items_preview
             FROM orders o
             LEFT JOIN users u ON u.id=o.cashier_id
             WHERE $where
             ORDER BY o.id DESC
             LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",
            $params
        );
        Response::success(['orders' => $rows, 'pagination' => $pg, 'total_guests' => $total]);
        break;

    case 'count':
        Response::success((int)DB::fetch("SELECT COUNT(*) as n FROM customers WHERE status='active'")['n']);
        break;

    case 'stats':
        Response::success([
            'total'     => DB::fetch("SELECT COUNT(*) as n FROM customers WHERE status='active'")['n'],
            'vip'       => DB::fetch("SELECT COUNT(*) as n FROM customers WHERE `group`='vip' AND status='active'")['n'],
            'wholesale' => DB::fetch("SELECT COUNT(*) as n FROM customers WHERE `group`='wholesale' AND status='active'")['n'],
            'new_today' => DB::fetch("SELECT COUNT(*) as n FROM customers WHERE DATE(created_at)=CURDATE()")['n'],
        ]);
        break;

    default:
        Response::error("Unknown action: {$action}", 404);
}
