<?php
switch ($action) {

    case 'list':
        $page    = max(1, (int)($_GET['page']    ?? 1));
        $perPage = (int)($_GET['per_page']        ?? 20);
        $search  = trim($_GET['search']           ?? '');
        $status  = trim($_GET['status']           ?? '');
        $where   = '1=1';
        $params  = [];
        if ($search) {
            $where  .= ' AND (name LIKE ? OR company LIKE ? OR phone LIKE ? OR email LIKE ?)';
            $params  = ["%$search%", "%$search%", "%$search%", "%$search%"];
        }
        if ($status) { $where .= ' AND status=?'; $params[] = $status; }
        $total = (int)DB::fetch("SELECT COUNT(*) as n FROM suppliers WHERE $where", $params)['n'];
        $pg    = paginate($total, $page, $perPage);
        $rows  = DB::fetchAll(
            "SELECT s.*,
             (SELECT COUNT(*) FROM purchases WHERE supplier_id=s.id) AS purchase_count,
             (SELECT COALESCE(SUM(due),0) FROM purchases WHERE supplier_id=s.id AND status!='cancelled') AS total_due
             FROM suppliers s WHERE $where ORDER BY s.name ASC
             LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",
            $params
        );
        Response::success(['suppliers' => $rows, 'pagination' => $pg]);
        break;

    case 'get':
        $id  = (int)($_GET['id'] ?? 0);
        $row = DB::fetch("SELECT * FROM suppliers WHERE id=?", [$id]);
        if (!$row) Response::error('Supplier not found', 404);
        // recent purchases
        $row['recent_purchases'] = DB::fetchAll(
            "SELECT p.id, p.reference, p.total, p.paid, p.due, p.status, p.created_at
             FROM purchases p WHERE p.supplier_id=? ORDER BY p.id DESC LIMIT 5", [$id]
        );
        Response::success($row);
        break;

    case 'save':
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if (!$name) Response::error('Supplier name is required');
        $data = [
            'name'       => $name,
            'company'    => trim($_POST['company']    ?? ''),
            'email'      => trim($_POST['email']      ?? ''),
            'phone'      => trim($_POST['phone']      ?? ''),
            'address'    => trim($_POST['address']    ?? ''),
            'tax_number' => trim($_POST['tax_number'] ?? ''),
            'status'     => in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active',
        ];
        if ($id) {
            DB::update('suppliers', $data, 'id=?', [$id]);
            log_activity('update_supplier', 'suppliers', "Updated supplier: {$name}", $id);
            Response::success(['id' => $id], 'Supplier updated');
        } else {
            $newId = DB::insert('suppliers', $data);
            log_activity('create_supplier', 'suppliers', "Created supplier: {$name}", $newId);
            Response::success(['id' => $newId], 'Supplier created');
        }
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        $sup = DB::fetch("SELECT * FROM suppliers WHERE id=?", [$id]);
        if (!$sup) Response::error('Supplier not found', 404);
        $inUse = DB::fetch("SELECT id FROM purchases WHERE supplier_id=? LIMIT 1", [$id]);
        if ($inUse) {
            // soft-delete: mark inactive instead
            DB::update('suppliers', ['status' => 'inactive'], 'id=?', [$id]);
            Response::success(null, 'Supplier deactivated (has purchase history)');
        }
        DB::delete('suppliers', 'id=?', [$id]);
        log_activity('delete_supplier', 'suppliers', "Deleted supplier: {$sup['name']}", $id);
        Response::success(null, 'Supplier deleted');
        break;

    case 'summary':
        Response::success([
            'total'    => DB::fetch("SELECT COUNT(*) as n FROM suppliers")['n'],
            'active'   => DB::fetch("SELECT COUNT(*) as n FROM suppliers WHERE status='active'")['n'],
            'inactive' => DB::fetch("SELECT COUNT(*) as n FROM suppliers WHERE status='inactive'")['n'],
            'total_due'=> DB::fetch("SELECT COALESCE(SUM(p.due),0) as v FROM purchases p WHERE p.status!='cancelled'")['v'],
        ]);
        break;

    default:
        Response::error("Unknown action: {$action}", 404);
}
