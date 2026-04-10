<?php
switch ($action) {

    case 'list':
        $page    = max(1, (int)($_GET['page']    ?? 1));
        $perPage = (int)($_GET['per_page']        ?? 20);
        $search  = trim($_GET['search']           ?? '');
        $role    = trim($_GET['role']             ?? '');
        $where   = "u.status != 'suspended'";
        $params  = [];
        if ($search) {
            $where  .= ' AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            $params  = array_merge($params, ["%$search%","%$search%","%$search%"]);
        }
        if ($role) { $where .= ' AND r.slug=?'; $params[] = $role; }
        $total = (int)DB::fetch(
            "SELECT COUNT(*) as n FROM users u
             JOIN roles r ON r.id=u.role_id WHERE $where", $params
        )['n'];
        $pg   = paginate($total, $page, $perPage);
        $rows = DB::fetchAll(
            "SELECT u.id, u.name, u.email, u.phone, u.status,
                    u.last_login, u.created_at,
                    r.name AS role_name, r.slug AS role_slug
             FROM users u
             JOIN roles r ON r.id=u.role_id
             WHERE $where ORDER BY u.id DESC
             LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",
            $params
        );
        Response::success(['employees' => $rows, 'pagination' => $pg]);
        break;

    case 'get':
        Auth::requirePermission('employees');
        $id  = (int)($_GET['id'] ?? 0);
        $row = DB::fetch(
            "SELECT u.id, u.name, u.email, u.phone, u.status,
                    u.last_login, u.created_at, u.avatar,
                    r.name AS role_name, r.slug AS role_slug, r.id AS role_id
             FROM users u JOIN roles r ON r.id=u.role_id
             WHERE u.id=?", [$id]
        );
        if (!$row) Response::error('Employee not found', 404);
        Response::success($row);
        break;

    case 'save':
        Auth::requirePermission('employees');
        $id    = (int)($_POST['id']    ?? 0);
        $name  = trim($_POST['name']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $roleId= (int)($_POST['role_id'] ?? 0);
        $v     = Validator::make($_POST, [
            'name'    => 'required|min:2',
            'email'   => 'required|email',
            'role_id' => 'required|integer',
        ]);
        if ($v->fails()) Response::error($v->firstError());
        $exists = DB::fetch(
            "SELECT id FROM users WHERE email=?" . ($id ? " AND id!=?" : ""),
            $id ? [$email, $id] : [$email]
        );
        if ($exists) Response::error('Email already exists');
        $data = [
            'name'    => $name,
            'email'   => $email,
            'phone'   => trim($_POST['phone']  ?? ''),
            'role_id' => $roleId,
            'status'  => $_POST['status'] ?? 'active',
        ];
        if (!empty($_POST['pin'])) {
            if (!preg_match('/^\d{4,6}$/', $_POST['pin'])) Response::error('PIN must be 4-6 digits');
            $data['pin'] = $_POST['pin'];
        }
        if (!empty($_POST['password'])) {
            if (strlen($_POST['password']) < 6) Response::error('Password must be at least 6 characters');
            $data['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
        } elseif (!$id) {
            Response::error('Password is required for new employee');
        }
        if (!empty($_FILES['avatar']['name'])) {
            $avatar = upload_file($_FILES['avatar'], 'avatars');
            if ($avatar) $data['avatar'] = $avatar;
        }
        if ($id) {
            DB::update('users', $data, 'id=?', [$id]);
            log_activity('update_employee', 'employees', "Updated: {$name}", $id);
        } else {
            $id = DB::insert('users', $data);
            log_activity('add_employee', 'employees', "Added: {$name}", $id);
        }
        $row = DB::fetch(
            "SELECT u.id, u.name, u.email, u.phone, u.status,
                    r.name AS role_name, r.slug AS role_slug
             FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=?", [$id]
        );
        Response::success($row, 'Employee saved');
        break;

    case 'delete':
        Auth::requirePermission('employees');
        $id = (int)($_POST['id'] ?? 0);
        if ($id === Auth::id()) Response::error('Cannot delete your own account');
        DB::update('users', ['status' => 'suspended'], 'id=?', [$id]);
        log_activity('delete_employee', 'employees', "Suspended employee #{$id}", $id);
        Response::success(null, 'Employee suspended');
        break;

    case 'roles':
        Response::success(DB::fetchAll("SELECT * FROM roles ORDER BY id"));
        break;

    case 'reset_password':
        Auth::requirePermission('employees');
        $id       = (int)($_POST['id']       ?? 0);
        $password = trim($_POST['password']  ?? '');
        if (!$id || strlen($password) < 6) Response::error('Invalid data — password min 6 chars');
        DB::update('users', ['password' => password_hash($password, PASSWORD_BCRYPT)], 'id=?', [$id]);
        log_activity('reset_password', 'employees', "Password reset for user #{$id}", $id);
        Response::success(null, 'Password reset successfully');
        break;

    case 'activity':
        Auth::requirePermission('employees');
        $id      = (int)($_GET['user_id'] ?? 0);
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $where   = $id ? 'user_id=?' : '1=1';
        $params  = $id ? [$id] : [];
        $total   = (int)DB::fetch("SELECT COUNT(*) as n FROM activity_logs WHERE $where", $params)['n'];
        $pg      = paginate($total, $page, $perPage);
        $rows    = DB::fetchAll(
            "SELECT al.*, u.name AS user_name
             FROM activity_logs al
             LEFT JOIN users u ON u.id=al.user_id
             WHERE $where ORDER BY al.id DESC
             LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",
            $params
        );
        Response::success(['logs' => $rows, 'pagination' => $pg]);
        break;

    default:
        Response::error("Unknown action: {$action}", 404);
}
