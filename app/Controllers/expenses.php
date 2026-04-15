<?php
switch ($action) {

    case 'list':
        $page    = max(1, (int)($_GET['page']   ?? 1));
        $perPage = (int)($_GET['per_page']        ?? 20);
        $search  = trim($_GET['search']           ?? '');
        $from    = trim($_GET['from']             ?? '');
        $to      = trim($_GET['to']               ?? '');
        $status  = trim($_GET['status']           ?? '');
        $catId   = (int)($_GET['category_id']     ?? 0);
        $where   = '1=1'; $params = [];
        if ($search) {
            $where  .= ' AND (e.title LIKE ? OR e.reference LIKE ?)';
            $params  = array_merge($params, ["%$search%", "%$search%"]);
        }
        if ($from)   { $where .= ' AND e.date >= ?';         $params[] = $from; }
        if ($to)     { $where .= ' AND e.date <= ?';         $params[] = $to; }
        if ($status) { $where .= ' AND e.status=?';          $params[] = $status; }
        if ($catId)  { $where .= ' AND e.category_id=?';     $params[] = $catId; }
        $total = (int)DB::fetch(
            "SELECT COUNT(*) as n FROM expenses e WHERE $where", $params
        )['n'];
        $pg   = paginate($total, $page, $perPage);
        $rows = DB::fetchAll(
            "SELECT e.*, e.description AS title, ec.name AS category_name, u.name AS user_name
             FROM expenses e
             LEFT JOIN expense_categories ec ON ec.id=e.category_id
             LEFT JOIN users u ON u.id=e.user_id
             WHERE $where ORDER BY e.date DESC, e.id DESC
             LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",
            $params
        );
        Response::success(['expenses' => $rows, 'pagination' => $pg]);
        break;

    case 'save':
        $id     = (int)($_POST['id']          ?? 0);
        $title  = trim($_POST['title']        ?? '');
        $amount = (float)($_POST['amount']    ?? 0);
        if (!$title) Response::error('Title is required');
        if ($amount <= 0) Response::error('Amount must be greater than zero');
        $data = [
            'description' => $title,          // DB column is 'description'
            'amount'      => $amount,
            'date'        => $_POST['date']        ?? date('Y-m-d'),
            'category_id' => (int)($_POST['category_id'] ?? 0) ?: null,
            'status'      => $_POST['status']      ?? 'approved',
            'user_id'     => Auth::id(),
        ];
        if ($id) {
            DB::update('expenses', $data, 'id=?', [$id]);
            log_activity('update_expense', 'expenses', "Updated: {$title}", $id);
        } else {
            $data['reference'] = generate_ref('EXP');
            $id = DB::insert('expenses', $data);
            log_activity('add_expense', 'expenses', "Added: {$title}", $id);
        }
        Response::success(DB::fetch(
            "SELECT e.*, e.description AS title, ec.name AS category_name
             FROM expenses e LEFT JOIN expense_categories ec ON ec.id=e.category_id WHERE e.id=?", [$id]
        ), 'Saved');
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        DB::delete('expenses', 'id=?', [$id]);
        log_activity('delete_expense', 'expenses', "Deleted #{$id}", $id);
        Response::success(null, 'Expense deleted');
        break;

    case 'categories':
        Response::success(DB::fetchAll("SELECT * FROM expense_categories ORDER BY name"));
        break;

    case 'save_category':
        $name = trim($_POST['name'] ?? '');
        if (!$name) Response::error('Name required');
        $id   = (int)($_POST['id'] ?? 0);
        if ($id) DB::update('expense_categories', ['name'=>$name], 'id=?', [$id]);
        else     $id = DB::insert('expense_categories', ['name'=>$name]);
        Response::success(DB::fetch("SELECT * FROM expense_categories WHERE id=?", [$id]), 'Saved');
        break;

    case 'summary':
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');
        $total = DB::fetch("SELECT COALESCE(SUM(amount),0) AS v FROM expenses WHERE date BETWEEN ? AND ? AND status='approved'", [$from, $to])['v'];
        $count = DB::fetch("SELECT COUNT(*) as n FROM expenses WHERE date BETWEEN ? AND ? AND status='approved'", [$from, $to])['n'];
        $month = DB::fetch("SELECT COALESCE(SUM(amount),0) AS v FROM expenses WHERE date BETWEEN ? AND ? AND status='approved'", [date('Y-m-01'), date('Y-m-d')])['v'];
        Response::success(['total'=>$total, 'count'=>$count, 'this_month'=>$month]);
        break;

    default:
        Response::error("Unknown action: {$action}", 404);
}
