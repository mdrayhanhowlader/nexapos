<?php
switch ($action) {

    case 'list':
        $page    = max(1,(int)($_GET['page'] ?? 1));
        $perPage = min(500,(int)($_GET['per_page'] ?? 25));
        $search  = trim($_GET['search'] ?? '');
        $catId   = (int)($_GET['category'] ?? 0);
        $status  = trim($_GET['status'] ?? '');
        $where   = '1=1'; $params = [];
        if ($search) {
            $where .= ' AND (p.name LIKE ? OR p.barcode LIKE ? OR p.sku LIKE ?)';
            $params = array_merge($params,["%$search%","%$search%","%$search%"]);
        }
        if ($catId)  { $where .= ' AND p.category_id=?'; $params[] = $catId; }
        if ($status) { $where .= ' AND p.status=?';      $params[] = $status; }
        $total  = (int)DB::fetch("SELECT COUNT(*) as n FROM products p WHERE $where",$params)['n'];
        $offset = ($page-1)*$perPage;
        $rows   = DB::fetchAll(
            "SELECT p.*, c.name AS category_name, COALESCE(i.quantity,0) AS stock
             FROM products p
             LEFT JOIN categories c ON c.id=p.category_id
             LEFT JOIN inventory i ON i.product_id=p.id AND i.warehouse_id=1
             WHERE $where ORDER BY p.name ASC LIMIT $perPage OFFSET $offset",$params);
        Response::success(['products'=>$rows,'pagination'=>['total'=>$total,'page'=>$page,'per_page'=>$perPage]]);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $p  = DB::fetch(
            "SELECT p.*, COALESCE(i.quantity,0) AS stock
             FROM products p
             LEFT JOIN inventory i ON i.product_id=p.id AND i.warehouse_id=1
             WHERE p.id=?",[$id]);
        if (!$p) Response::error('Not found',404);
        Response::success($p);
        break;

    case 'save':
        Auth::requirePermission('products');
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if (!$name) Response::error('Product name is required');

        // No duplicate check — allow same name (user manages uniqueness via SKU/barcode)

        // ── Image upload ──────────────────────────────────────────────────────
        $image = null;
        if (
            isset($_FILES['image']) &&
            $_FILES['image']['error'] === UPLOAD_ERR_OK &&
            $_FILES['image']['size'] > 0 &&
            $_FILES['image']['size'] <= 5 * 1024 * 1024
        ) {
            $tmp  = $_FILES['image']['tmp_name'];
            $ext  = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg','jpeg','png','gif','webp'];
            // Validate by extension AND mime type
            $mime = function_exists('mime_content_type') ? mime_content_type($tmp) : '';
            $allowed_mime = ['image/jpeg','image/jpg','image/png','image/gif','image/webp',''];
            if (in_array($ext, $allowed_ext) && in_array($mime, $allowed_mime)) {
                $dir = ROOT_PATH . '/public/uploads/products/';
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                if (is_dir($dir) && !is_writable($dir)) chmod($dir, 0777);
                $fname = 'prod_' . time() . '_' . rand(1000,9999) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
                $dest  = $dir . $fname;
                $saved_ok = false;
                // Try GD resize for jpg/png/webp
                if (function_exists('imagecreatefromjpeg') && in_array($ext, ['jpg','jpeg','png','webp'])) {
                    try {
                        $src = null;
                        if ($ext === 'png')              $src = @imagecreatefrompng($tmp);
                        elseif ($ext === 'webp')         $src = @imagecreatefromwebp($tmp);
                        else                             $src = @imagecreatefromjpeg($tmp);
                        if ($src) {
                            $w = imagesx($src); $h = imagesy($src);
                            $ratio = min(800/$w, 800/$h, 1);
                            $nw = max(1,(int)($w*$ratio));
                            $nh = max(1,(int)($h*$ratio));
                            $dst = imagecreatetruecolor($nw,$nh);
                            if ($ext === 'png') {
                                imagealphablending($dst, false);
                                imagesavealpha($dst, true);
                                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                                imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
                            } else {
                                imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
                            }
                            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                            $outFname = 'prod_' . time() . '_' . rand(1000,9999) . '.jpg';
                            $dest  = $dir . $outFname;
                            $saved_ok = imagejpeg($dst, $dest, 85);
                            imagedestroy($src);
                            imagedestroy($dst);
                            if ($saved_ok) $fname = $outFname;
                        }
                    } catch (\Throwable $e) { $saved_ok = false; }
                }
                // Fallback: move file as-is
                if (!$saved_ok) {
                    $fname = 'prod_' . time() . '_' . rand(1000,9999) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
                    $dest  = $dir . $fname;
                    $saved_ok = move_uploaded_file($tmp, $dest);
                    if (!$saved_ok) $saved_ok = copy($tmp, $dest);
                }
                if ($saved_ok && file_exists($dest)) $image = $fname;
            }
        }

        $data = [
            'name'            => $name,
            'category_id'     => (int)($_POST['category_id'] ?? 0) ?: null,
            'sku'             => trim($_POST['sku'] ?? '') ?: null,
            'barcode'         => trim($_POST['barcode'] ?? '') ?: null,
            'description'     => trim($_POST['description'] ?? ''),
            'unit'            => $_POST['unit'] ?? 'pcs',
            'cost_price'      => (float)($_POST['cost_price'] ?? 0),
            'selling_price'   => (float)($_POST['selling_price'] ?? 0),
            'wholesale_price' => (float)($_POST['wholesale_price'] ?? 0),
            'tax_rate'        => (float)($_POST['tax_rate'] ?? 0),
            'stock_alert_qty' => (int)($_POST['stock_alert_qty'] ?? 5),
            'track_stock'     => (int)($_POST['track_stock'] ?? 1),
            'status'          => $_POST['status'] ?? 'active',
        ];
        if ($image !== null) $data['image'] = $image;

        if ($id) {
            DB::update('products', $data, 'id=?', [$id]);
            // ── Stock update on edit ──────────────────────────────────────────
            $newQty = $_POST['stock_qty'] ?? null;
            if ($newQty !== null && $newQty !== '') {
                $newQty = (float)$newQty;
                if ($newQty >= 0) {
                    $cur    = DB::fetch("SELECT quantity FROM inventory WHERE product_id=? AND warehouse_id=1", [$id]);
                    $before = $cur ? (float)$cur['quantity'] : 0;
                    DB::query("INSERT INTO inventory (product_id,variant_id,warehouse_id,quantity) VALUES (?,0,1,?) ON DUPLICATE KEY UPDATE quantity=?", [$id, $newQty, $newQty]);
                    if ($before != $newQty) {
                        DB::insert('stock_movements', ['product_id'=>$id,'warehouse_id'=>1,'user_id'=>Auth::id(),'type'=>'adjustment','quantity'=>$newQty-$before,'quantity_before'=>$before,'quantity_after'=>$newQty,'note'=>'Manual stock update via product edit']);
                    }
                }
            }
            log_activity('update_product', 'products', "Updated: {$name}", $id);
        } else {
            $id  = DB::insert('products', $data);
            $qty = (float)($_POST['opening_stock'] ?? 0);
            if ($qty > 0) {
                DB::query("INSERT INTO inventory (product_id,variant_id,warehouse_id,quantity) VALUES (?,0,1,?) ON DUPLICATE KEY UPDATE quantity=?", [$id, $qty, $qty]);
                DB::insert('stock_movements', ['product_id'=>$id,'warehouse_id'=>1,'user_id'=>Auth::id(),'type'=>'opening','quantity'=>$qty,'quantity_before'=>0,'quantity_after'=>$qty]);
            }
            log_activity('add_product', 'products', "Added: {$name}", $id);
        }
        $saved = DB::fetch("SELECT p.*, COALESCE(i.quantity,0) AS stock FROM products p LEFT JOIN inventory i ON i.product_id=p.id AND i.warehouse_id=1 WHERE p.id=?", [$id]);
        Response::success($saved, 'Product saved');
        break;

    case 'delete':
        Auth::requirePermission('products');
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) Response::error('Invalid product ID');
        $p  = DB::fetch("SELECT image, name FROM products WHERE id=?", [$id]);
        if (!$p) Response::error('Product not found', 404);
        // Check if product has any orders
        $hasOrders = DB::fetch("SELECT COUNT(*) as n FROM order_items WHERE product_id=?", [$id])['n'] ?? 0;
        if ($hasOrders > 0) {
            // Soft-delete: hide from catalog but keep order history intact
            DB::update('products', ['status' => 'inactive'], 'id=?', [$id]);
            DB::query("DELETE FROM inventory WHERE product_id=?", [$id]);
            log_activity('delete_product', 'products', "Soft-deleted (has orders): {$p['name']} #{$id}", $id);
            Response::success(null, 'Product removed from catalog (order history preserved)');
        } else {
            // Hard-delete: no order references
            if ($p['image']) {
                $f = ROOT_PATH . '/public/uploads/products/' . $p['image'];
                if (file_exists($f)) @unlink($f);
            }
            DB::query("DELETE FROM inventory WHERE product_id=?", [$id]);
            DB::delete('products', 'id=?', [$id]);
            log_activity('delete_product', 'products', "Deleted: {$p['name']} #{$id}", $id);
            Response::success(null, 'Product deleted');
        }
        break;

    case 'categories':
        Response::success(DB::fetchAll("SELECT * FROM categories WHERE status='active' ORDER BY name"));
        break;

    case 'save_category':
        Auth::requirePermission('products');
        $name = trim($_POST['name'] ?? '');
        if (!$name) Response::error('Name required');
        $id   = (int)($_POST['id'] ?? 0);
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i','-',$name)).'-'.time();
        $data = ['name'=>$name,'slug'=>$slug,'status'=>'active'];
        if ($id) DB::update('categories',$data,'id=?',[$id]);
        else     $id = DB::insert('categories',$data);
        Response::success(DB::fetch("SELECT * FROM categories WHERE id=?",[$id]),'Saved');
        break;

    case 'delete_category':
        Auth::requirePermission('products');
        DB::update('categories',['status'=>'inactive'],'id=?',[(int)($_POST['id']??0)]);
        Response::success(null,'Deleted');
        break;

    case 'count':
        Response::success((int)DB::fetch("SELECT COUNT(*) as n FROM products WHERE status='active'")['n']);
        break;

    case 'delete_duplicates':
        // Keep the product with the highest stock (or lowest ID as tiebreaker), delete the rest
        Auth::requirePermission('products');
        $deleted = 0;
        // Find groups of products with the same name
        $dupeNames = DB::fetchAll(
            "SELECT name FROM products WHERE status != 'inactive' GROUP BY name HAVING COUNT(*) > 1"
        );
        foreach ($dupeNames as $row) {
            $dups = DB::fetchAll(
                "SELECT p.id, COALESCE(i.quantity,0) AS stock
                 FROM products p
                 LEFT JOIN inventory i ON i.product_id=p.id AND i.warehouse_id=1
                 WHERE p.name=? AND p.status != 'inactive'
                 ORDER BY COALESCE(i.quantity,0) DESC, p.id ASC",
                [$row['name']]
            );
            // Keep the first one (highest stock), delete the rest
            array_shift($dups);
            foreach ($dups as $dup) {
                $hasOrders = DB::fetch("SELECT COUNT(*) as n FROM order_items WHERE product_id=?", [$dup['id']])['n'] ?? 0;
                if ($hasOrders > 0) {
                    DB::update('products', ['status' => 'inactive'], 'id=?', [$dup['id']]);
                } else {
                    $img = DB::fetch("SELECT image FROM products WHERE id=?", [$dup['id']]);
                    if ($img && $img['image']) {
                        $f = ROOT_PATH . '/public/uploads/products/' . $img['image'];
                        if (file_exists($f)) @unlink($f);
                    }
                    DB::query("DELETE FROM inventory WHERE product_id=?", [$dup['id']]);
                    DB::delete('products', 'id=?', [$dup['id']]);
                }
                $deleted++;
            }
        }
        Response::success(['deleted' => $deleted], "Removed {$deleted} duplicate(s)");
        break;

    case 'low_stock':
        Response::success(DB::fetchAll(
            "SELECT p.id,p.name,p.sku,p.stock_alert_qty,c.name AS category,COALESCE(i.quantity,0) AS stock
             FROM products p
             LEFT JOIN categories c ON c.id=p.category_id
             LEFT JOIN inventory i ON i.product_id=p.id AND i.warehouse_id=1
             WHERE p.track_stock=1 AND p.status='active' AND COALESCE(i.quantity,0)<=p.stock_alert_qty
             ORDER BY COALESCE(i.quantity,0) ASC"));
        break;

    case 'suppliers':
        Response::success(DB::fetchAll("SELECT * FROM suppliers WHERE status='active' ORDER BY name"));
        break;

    case 'generate_barcode':
        do { $bc = str_pad(rand(1000000000000,9999999999999),13,'0'); }
        while (DB::exists('products','barcode=?',[$bc]));
        Response::success(['barcode'=>$bc]);
        break;

    default:
        Response::error("Unknown action: {$action}",404);
}
