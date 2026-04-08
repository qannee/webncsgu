<?php
// cart_action.php - Xử lý thêm/xóa/cập nhật giỏ hàng
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'config/functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect = $_SERVER['HTTP_REFERER'] ?? '/shop/products.php';

if (!isLoggedIn()) {
    redirect('/shop/login.php?redirect=' . urlencode('/shop/cart.php'));
}

$user_id = $_SESSION['user_id'];

switch ($action) {
    case 'add':
        $product_id = (int)($_POST['product_id'] ?? 0);
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        if ($product_id) {
            // Check product exists & has stock
            $prod = $pdo->prepare("SELECT stock FROM products WHERE id=? AND status='show'");
            $prod->execute([$product_id]);
            $prod = $prod->fetch();
            if ($prod) {
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?,?,?)
                    ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + ?, ?)");
                $stmt->execute([$user_id, $product_id, $qty, $qty, $prod['stock']]);
                $_SESSION['flash'] = ['type'=>'success','msg'=>'✅ Đã thêm vào giỏ hàng!'];
            }
        }
        break;

    case 'update':
        $product_id = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);
        if ($product_id && $qty > 0) {
            $pdo->prepare("UPDATE cart SET quantity=? WHERE user_id=? AND product_id=?")->execute([$qty, $user_id, $product_id]);
        } elseif ($qty <= 0) {
            $pdo->prepare("DELETE FROM cart WHERE user_id=? AND product_id=?")->execute([$user_id, $product_id]);
        }
        redirect('/shop/cart.php');
        break;

    case 'remove':
        $product_id = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
        if ($product_id) {
            $pdo->prepare("DELETE FROM cart WHERE user_id=? AND product_id=?")->execute([$user_id, $product_id]);
        }
        redirect('/shop/cart.php');
        break;
}

redirect($redirect);