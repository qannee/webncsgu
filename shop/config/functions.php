<?php
// config/functions.php - Các hàm tiện ích dùng chung

function formatPrice($price) {
    return number_format($price, 0, ',', '.') . '₫';
}

function sanitize($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin($redirect = '/shop/login.php') {
    if (!isLoggedIn()) {
        redirect($redirect);
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        redirect('/shop/admin/login.php');
    }
}

function generateCode($prefix = 'DH') {
    return $prefix . date('YmdHis') . rand(10, 99);
}

function getCategories($pdo) {
    return $pdo->query("SELECT * FROM categories WHERE status='show' ORDER BY name")->fetchAll();
}

function getCartCount($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}

function uploadImage($file, $dir) {
    if (!isset($file) || $file['error'] !== 0) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, $allowed)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null;
    $filename = uniqid('img_') . '.' . $ext;
    $dest = $dir . $filename;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (move_uploaded_file($file['tmp_name'], $dest)) return $filename;
    return null;
}

function paginate($total, $per_page, $current_page, $url_pattern) {
    $total_pages = ceil($total / $per_page);
    if ($total_pages <= 1) return '';
    $html = '<nav class="pagination-nav"><ul class="pagination">';
    if ($current_page > 1) {
        $html .= '<li><a href="' . sprintf($url_pattern, $current_page - 1) . '">&laquo;</a></li>';
    }
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    for ($i = $start; $i <= $end; $i++) {
        $active = $i == $current_page ? ' class="active"' : '';
        $html .= "<li$active><a href='" . sprintf($url_pattern, $i) . "'>$i</a></li>";
    }
    if ($current_page < $total_pages) {
        $html .= '<li><a href="' . sprintf($url_pattern, $current_page + 1) . '">&raquo;</a></li>';
    }
    $html .= '</ul></nav>';
    return $html;
}
