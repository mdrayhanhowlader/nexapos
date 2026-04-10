<?php
switch ($action) {

    case 'sales_summary':
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');
        $summary = DB::fetch(
            "SELECT
               COUNT(*) AS total_orders,
               COALESCE(SUM(total),0) AS total_revenue,
               COALESCE(SUM(discount_amount),0) AS total_discounts,
               COALESCE(SUM(tax_amount),0) AS total_tax,
               COALESCE(AVG(total),0) AS avg_order_value,
               COALESCE(SUM(total - tax_amount - discount_amount),0) AS net_revenue
             FROM orders
             WHERE status='completed' AND DATE(created_at) BETWEEN ? AND ?",
            [$from, $to]
        );
        $daily = DB::fetchAll(
            "SELECT DATE(created_at) AS date,
                    COUNT(*) AS orders,
                    COALESCE(SUM(total),0) AS revenue
             FROM orders
             WHERE status='completed' AND DATE(created_at) BETWEEN ? AND ?
             GROUP BY DATE(created_at) ORDER BY date",
            [$from, $to]
        );
        Response::success(['summary' => $summary, 'daily' => $daily]);
        break;

    case 'top_products':
        $from  = $_GET['from']  ?? date('Y-m-01');
        $to    = $_GET['to']    ?? date('Y-m-d');
        $limit = (int)($_GET['limit'] ?? 10);
        $rows  = DB::fetchAll(
            "SELECT oi.product_id, oi.name,
                    SUM(oi.quantity) AS qty_sold,
                    SUM(oi.subtotal) AS revenue,
                    COUNT(DISTINCT oi.order_id) AS orders
             FROM order_items oi
             JOIN orders o ON o.id=oi.order_id
             WHERE o.status='completed' AND DATE(o.created_at) BETWEEN ? AND ?
             GROUP BY oi.product_id, oi.name
             ORDER BY qty_sold DESC LIMIT ?",
            [$from, $to, $limit]
        );
        Response::success($rows);
        break;

    case 'cashier_report':
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');
        $rows = DB::fetchAll(
            "SELECT u.name AS cashier,
                    COUNT(o.id) AS orders,
                    COALESCE(SUM(o.total),0) AS revenue,
                    COALESCE(SUM(o.discount_amount),0) AS discounts
             FROM orders o
             JOIN users u ON u.id=o.cashier_id
             WHERE o.status='completed' AND DATE(o.created_at) BETWEEN ? AND ?
             GROUP BY o.cashier_id ORDER BY revenue DESC",
            [$from, $to]
        );
        Response::success($rows);
        break;

    case 'payment_methods_report':
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');
        $rows = DB::fetchAll(
            "SELECT pm.name, pm.type,
                    COALESCE(SUM(p.amount),0) AS total,
                    COUNT(*) AS count
             FROM payments p
             JOIN payment_methods pm ON pm.id=p.payment_method_id
             JOIN orders o ON o.id=p.order_id
             WHERE o.status='completed' AND DATE(o.created_at) BETWEEN ? AND ?
             GROUP BY p.payment_method_id ORDER BY total DESC",
            [$from, $to]
        );
        Response::success($rows);
        break;

    case 'profit_loss':
        $from     = $_GET['from'] ?? date('Y-m-01');
        $to       = $_GET['to']   ?? date('Y-m-d');
        $revenue  = (float)DB::fetch(
            "SELECT COALESCE(SUM(total),0) AS v FROM orders
             WHERE status='completed' AND DATE(created_at) BETWEEN ? AND ?",
            [$from, $to])['v'];
        $cogs     = (float)DB::fetch(
            "SELECT COALESCE(SUM(oi.unit_cost * oi.quantity),0) AS v
             FROM order_items oi
             JOIN orders o ON o.id=oi.order_id
             WHERE o.status='completed' AND DATE(o.created_at) BETWEEN ? AND ?",
            [$from, $to])['v'];
        $expenses = (float)DB::fetch(
            "SELECT COALESCE(SUM(amount),0) AS v FROM expenses
             WHERE date BETWEEN ? AND ? AND status='approved'",
            [$from, $to])['v'];
        $refunds  = (float)DB::fetch(
            "SELECT COALESCE(SUM(total),0) AS v FROM refunds
             WHERE status='completed' AND DATE(created_at) BETWEEN ? AND ?",
            [$from, $to])['v'];
        $grossProfit = $revenue - $cogs - $refunds;
        $netProfit   = $grossProfit - $expenses;
        Response::success(compact('revenue','cogs','expenses','refunds','grossProfit','netProfit'));
        break;

    case 'inventory_value':
        $rows   = DB::fetchAll(
            "SELECT p.name, p.sku, p.cost_price, p.selling_price,
                    COALESCE(i.quantity,0) AS stock,
                    (p.cost_price    * COALESCE(i.quantity,0)) AS cost_value,
                    (p.selling_price * COALESCE(i.quantity,0)) AS retail_value,
                    c.name AS category
             FROM products p
             LEFT JOIN categories c ON c.id=p.category_id
             LEFT JOIN inventory i  ON i.product_id=p.id AND i.warehouse_id=1
             WHERE p.status='active' ORDER BY retail_value DESC"
        );
        $totals = DB::fetch(
            "SELECT
               COALESCE(SUM(p.cost_price    * COALESCE(i.quantity,0)),0) AS cost_total,
               COALESCE(SUM(p.selling_price * COALESCE(i.quantity,0)),0) AS retail_total
             FROM products p
             LEFT JOIN inventory i ON i.product_id=p.id AND i.warehouse_id=1
             WHERE p.status='active'"
        );
        Response::success(['items' => $rows, 'totals' => $totals]);
        break;

    case 'category_sales':
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');
        $rows = DB::fetchAll(
            "SELECT c.name AS category,
                    COALESCE(SUM(oi.subtotal),0) AS revenue,
                    COALESCE(SUM(oi.quantity),0) AS qty
             FROM order_items oi
             JOIN products p ON p.id=oi.product_id
             JOIN categories c ON c.id=p.category_id
             JOIN orders o ON o.id=oi.order_id
             WHERE o.status='completed' AND DATE(o.created_at) BETWEEN ? AND ?
             GROUP BY p.category_id ORDER BY revenue DESC",
            [$from, $to]
        );
        Response::success($rows);
        break;

    case 'hourly_sales':
        $date = $_GET['date'] ?? date('Y-m-d');
        $rows = DB::fetchAll(
            "SELECT HOUR(created_at) AS hour,
                    COUNT(*) AS orders,
                    COALESCE(SUM(total),0) AS revenue
             FROM orders
             WHERE status='completed' AND DATE(created_at)=?
             GROUP BY HOUR(created_at) ORDER BY hour",
            [$date]
        );
        Response::success($rows);
        break;

    case 'customer_report':
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');
        $rows = DB::fetchAll(
            "SELECT c.name, c.phone, c.group,
                    COUNT(o.id) AS orders,
                    COALESCE(SUM(o.total),0) AS spent,
                    MAX(o.created_at) AS last_visit
             FROM orders o
             JOIN customers c ON c.id=o.customer_id
             WHERE DATE(o.created_at) BETWEEN ? AND ?
             GROUP BY o.customer_id ORDER BY spent DESC LIMIT 50",
            [$from, $to]
        );
        Response::success($rows);
        break;

    case 'shift_report':
        $id    = (int)($_GET['shift_id'] ?? 0);
        if (!$id) Response::error('Shift ID required');
        $shift = DB::fetch(
            "SELECT s.*, u.name AS cashier FROM shifts s
             JOIN users u ON u.id=s.cashier_id WHERE s.id=?", [$id]
        );
        if (!$shift) Response::error('Shift not found', 404);
        $payments  = DB::fetchAll(
            "SELECT pm.name, COALESCE(SUM(p.amount),0) AS total
             FROM payments p
             JOIN payment_methods pm ON pm.id=p.payment_method_id
             JOIN orders o ON o.id=p.order_id
             WHERE o.shift_id=? GROUP BY p.payment_method_id", [$id]
        );
        $movements = DB::fetchAll(
            "SELECT * FROM cash_movements WHERE shift_id=? ORDER BY created_at", [$id]
        );
        Response::success(compact('shift','payments','movements'));
        break;

    case 'expenses_summary':
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');
        $rows = DB::fetchAll(
            "SELECT ec.name AS category,
                    COALESCE(SUM(e.amount),0) AS total,
                    COUNT(*) AS count
             FROM expenses e
             LEFT JOIN expense_categories ec ON ec.id=e.category_id
             WHERE e.date BETWEEN ? AND ? AND e.status='approved'
             GROUP BY e.category_id ORDER BY total DESC",
            [$from, $to]
        );
        $total = DB::fetch(
            "SELECT COALESCE(SUM(amount),0) AS v FROM expenses
             WHERE date BETWEEN ? AND ? AND status='approved'",
            [$from, $to]
        )['v'];
        Response::success(['categories' => $rows, 'total' => $total]);
        break;

    default:
        Response::error("Unknown action: {$action}", 404);
}
