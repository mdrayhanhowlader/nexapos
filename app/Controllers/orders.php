<?php
switch ($action) {

  case 'list':
    $page    = max(1,(int)($_GET['page'] ?? 1));
    $perPage = min(500,(int)($_GET['per_page'] ?? 25));
    $search  = trim($_GET['search'] ?? '');
    $status  = trim($_GET['status'] ?? '');
    $dateFrom= trim($_GET['date_from'] ?? '');
    $dateTo  = trim($_GET['date_to'] ?? '');
    $where   = '1=1'; $params = [];
    if ($search) {
      $where .= ' AND (o.invoice_no LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)';
      $params = array_merge($params,["%$search%","%$search%","%$search%"]);
    }
    if ($status)   { $where .= ' AND o.status=?';            $params[] = $status; }
    if ($dateFrom) { $where .= ' AND DATE(o.created_at)>=?'; $params[] = $dateFrom; }
    if ($dateTo)   { $where .= ' AND DATE(o.created_at)<=?'; $params[] = $dateTo; }
    $total  = (int)DB::fetch(
      "SELECT COUNT(*) as n FROM orders o
       LEFT JOIN customers c ON c.id=o.customer_id
       WHERE $where", $params)['n'];
    $offset = ($page-1)*$perPage;
    $rows   = DB::fetchAll(
      "SELECT o.*,
              c.name  AS customer_name,
              c.phone AS customer_phone,
              u.name  AS cashier_name,
              (SELECT COUNT(*) FROM order_items WHERE order_id=o.id) AS items_count
       FROM orders o
       LEFT JOIN customers c ON c.id=o.customer_id
       LEFT JOIN users     u ON u.id=o.cashier_id
       WHERE $where
       ORDER BY o.id DESC
       LIMIT $perPage OFFSET $offset", $params);
    Response::success([
      'orders'     => $rows,
      'pagination' => ['total'=>$total,'page'=>$page,'per_page'=>$perPage,'offset'=>$offset]
    ]);
    break;

  case 'get':
    $id    = (int)($_GET['id'] ?? 0);
    $order = DB::fetch(
      "SELECT o.*, c.name AS customer_name, c.phone AS customer_phone, u.name AS cashier_name
       FROM orders o
       LEFT JOIN customers c ON c.id=o.customer_id
       LEFT JOIN users u ON u.id=o.cashier_id
       WHERE o.id=?", [$id]);
    if (!$order) Response::error('Order not found', 404);
    $items = DB::fetchAll(
      "SELECT oi.*, p.sku, p.image
       FROM order_items oi
       LEFT JOIN products p ON p.id=oi.product_id
       WHERE oi.order_id=?", [$id]);
    $payments = DB::fetchAll(
      "SELECT pay.*, pm.name AS method_name
       FROM payments pay
       LEFT JOIN payment_methods pm ON pm.id=pay.payment_method_id
       WHERE pay.order_id=?", [$id]);
    Response::success(['order'=>$order,'items'=>$items,'payments'=>$payments]);
    break;

  case 'stats':
    $today = date('Y-m-d');
    $stats = DB::fetch(
      "SELECT
         COUNT(*)                       AS today_count,
         COALESCE(SUM(total),0)         AS today_revenue,
         COALESCE(AVG(total),0)         AS today_avg,
         COALESCE(SUM(GREATEST(0, total-paid)),0) AS today_due
       FROM orders
       WHERE DATE(created_at)=? AND status != 'cancelled'", [$today]);
    Response::success($stats);
    break;

  case 'cancel':
    $id     = (int)($_POST['id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $order  = DB::fetch("SELECT * FROM orders WHERE id=?", [$id]);
    if (!$order) Response::error('Order not found', 404);
    if (in_array($order['status'], ['cancelled','refunded']))
      Response::error('Order already cancelled');
    DB::update('orders', ['status'=>'cancelled','note'=>$reason], 'id=?', [$id]);
    $items = DB::fetchAll("SELECT * FROM order_items WHERE order_id=?", [$id]);
    foreach ($items as $item) {
      if (!$item['product_id']) continue;
      DB::query(
        "UPDATE inventory SET quantity=quantity+? WHERE product_id=? AND warehouse_id=1",
        [$item['quantity'], $item['product_id']]);
    }
    log_activity('cancel_order','orders',"Cancelled #{$id}: {$reason}", $id);
    Response::success(null, 'Order cancelled');
    break;

  case 'refund':
    $id    = (int)($_POST['id'] ?? 0);
    $order = DB::fetch("SELECT * FROM orders WHERE id=?", [$id]);
    if (!$order) Response::error('Order not found', 404);
    DB::update('orders', ['status'=>'refunded'], 'id=?', [$id]);
    DB::insert('refunds', [
      'order_id'   => $id,
      'user_id'    => Auth::id(),
      'amount'     => $order['paid'],
      'reason'     => trim($_POST['reason'] ?? ''),
      'created_at' => date('Y-m-d H:i:s'),
    ]);
    log_activity('refund_order','orders',"Refunded #{$id}", $id);
    Response::success(null, 'Order refunded');
    break;

  default:
    Response::error("Unknown action: {$action}", 404);
}
