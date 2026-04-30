<?php
switch ($action) {

  case 'get_all':
    $rows = DB::fetchAll("SELECT `key`, value FROM settings");
    $out  = [];
    foreach ($rows as $r) $out[$r['key']] = $r['value'];
    Response::success($out);
    break;

  case 'save_all':
    // // Auth::requirePermission('settings'); — handled by session check — handled by session check

    // ── Image uploads ──
    $uploadDir = ROOT_PATH . '/public/uploads/';
    $imageKeys = [
      'bkash_qr_image'  => 'qr/bkash_',
      'nagad_qr_image'  => 'qr/nagad_',
      'rocket_qr_image' => 'qr/rocket_',
      'business_logo'   => 'logos/logo_',
    ];

    foreach ($imageKeys as $key => $prefix) {
      $fileKey = str_replace(['_qr_image','business_'], ['_file',''], $key);
      // Match POST field name to file input name
      $fieldName = ($key === 'business_logo') ? 'business_logo' : str_replace('_image', '_image', $key);

      if (!empty($_FILES[$key]['name'])) {
        $file = $_FILES[$key];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) continue;
        if ($file['size'] > 2 * 1024 * 1024) continue;

        $filename = $prefix . time() . '.' . $ext;
        $dest     = $uploadDir . $filename;

        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
        if (move_uploaded_file($file['tmp_name'], $dest)) {
          // Delete old file
          $old = DB::fetch("SELECT value FROM settings WHERE `key`=?", [$key]);
          if ($old && $old['value'] && file_exists(ROOT_PATH . '/public/' . $old['value'])) {
            @unlink(ROOT_PATH . '/public/' . $old['value']);
          }
          $val = 'nexapos/public/uploads/' . $filename;
          DB::query(
            "INSERT INTO settings (`key`,value,`group`) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE value=?",
            [$key, $val, 'media', $val]
          );
        }
      }
    }

    // ── Text / toggle settings ──
    $allowed = [
      'business_name'        => 'general',
      'business_email'       => 'general',
      'business_phone'       => 'general',
      'business_address'     => 'general',
      'timezone'             => 'general',
      'low_stock_alert'      => 'general',
      'currency'             => 'tax',
      'currency_symbol'      => 'tax',
      'tax_enabled'          => 'tax',
      'tax_rate'             => 'tax',
      'invoice_prefix'       => 'orders',
      'invoice_start'        => 'orders',
      'receipt_footer'       => 'receipt',
      'receipt_auto_print'   => 'receipt',
      'receipt_show_logo'    => 'receipt',
      'thermal_printer'      => 'receipt',
      'cash_drawer_enabled'  => 'hardware',
      'cash_drawer_auto'     => 'hardware',
      'cash_drawer_manual_btn'=> 'hardware',
      'cash_drawer_port'     => 'hardware',
      'barcode_auto_scan'    => 'hardware',
      'barcode_manual_entry' => 'hardware',
      'loyalty_enabled'      => 'loyalty',
      'points_per_amount'    => 'loyalty',
      'points_value'         => 'loyalty',
      'qr_payment_enabled'   => 'payment',
      // bKash API
      'bkash_enabled'        => 'payment',
      'bkash_sandbox'        => 'payment',
      'bkash_app_key'        => 'payment',
      'bkash_app_secret'     => 'payment',
      'bkash_username'       => 'payment',
      'bkash_password'       => 'payment',
      // Nagad API
      'nagad_enabled'        => 'payment',
      'nagad_sandbox'        => 'payment',
      'nagad_merchant_id'    => 'payment',
      'nagad_merchant_key'   => 'payment',
      'nagad_public_key'     => 'payment',
      // VAT label
      'vat_label'            => 'tax',
      'tax_inclusive_default'=> 'tax',
    ];

    foreach ($allowed as $key => $group) {
      if (!isset($_POST[$key])) continue;
      $val = trim($_POST[$key]);
      DB::query(
        "INSERT INTO settings (`key`,value,`group`) VALUES (?,?,?)
         ON DUPLICATE KEY UPDATE value=?",
        [$key, $val, $group, $val]
      );
    }

    log_activity('settings_updated', 'settings', 'Settings saved');
    Response::success(null, 'Settings saved successfully');
    break;

  case 'change_password':
    $current    = trim($_POST['current_password'] ?? '');
    $newPass    = trim($_POST['new_password']     ?? '');
    $confirm    = trim($_POST['confirm_password'] ?? '');
    if (!$current || !$newPass) Response::error('All fields are required');
    if (strlen($newPass) < 6)   Response::error('New password must be at least 6 characters');
    if ($newPass !== $confirm)  Response::error('New passwords do not match');
    $user = DB::fetch("SELECT password FROM users WHERE id=?", [Auth::id()]);
    if (!$user || !password_verify($current, $user['password'])) {
        Response::error('Current password is incorrect');
    }
    DB::update('users', ['password' => password_hash($newPass, PASSWORD_BCRYPT)], 'id=?', [Auth::id()]);
    log_activity('change_password', 'settings', 'User changed their own password');
    Response::success(null, 'Password changed successfully');
    break;

  case 'get_payment_methods':
    Response::success(DB::fetchAll(
        "SELECT id, name, slug, type, color, account_number, instructions, charge_rate, cash_drawer, is_active, sort_order
         FROM payment_methods ORDER BY sort_order"
    ));
    break;

  case 'save_payment_method':
    $id             = (int)($_POST['id'] ?? 0);
    $accountNumber  = trim($_POST['account_number'] ?? '');
    $instructions   = trim($_POST['instructions']   ?? '');
    $isActive       = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
    $chargeRate     = (float)($_POST['charge_rate'] ?? 0);
    if (!$id) Response::error('Invalid payment method ID');
    DB::update('payment_methods', [
        'account_number' => $accountNumber ?: null,
        'instructions'   => $instructions  ?: null,
        'charge_rate'    => $chargeRate,
        'is_active'      => $isActive,
    ], 'id=?', [$id]);
    log_activity('settings_updated', 'settings', "Payment method #{$id} updated");
    Response::success(
        DB::fetch("SELECT * FROM payment_methods WHERE id=?", [$id]),
        'Payment method saved'
    );
    break;

  default:
    Response::error("Unknown action: {$action}", 404);
}
