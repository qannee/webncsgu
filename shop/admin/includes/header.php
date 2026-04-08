<?php
// admin/includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
requireAdmin();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= isset($page_title) ? sanitize($page_title) . ' - ' : '' ?>Admin | FoodShop</title>
<link rel="stylesheet" href="/shop/assets/css/style.css">
</head>
<body>
<div class="admin-layout">

<!-- Sidebar -->
<aside class="admin-sidebar">
    <div class="sidebar-logo">
        <span style="font-size:28px">🌿</span>
        <span>FoodShop Admin</span>
    </div>
    <div class="admin-user">
        <small>Xin chào,</small>
        <strong><?= sanitize($_SESSION['fullname'] ?? 'Admin') ?></strong>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-group-title">Tổng quan</div>
        <a href="/shop/admin/index.php"           class="<?= $current_page==='index.php'?'active':'' ?>">📊 Dashboard</a>

        <div class="nav-group-title">Quản lý</div>
        <a href="/shop/admin/users.php"           class="<?= $current_page==='users.php'?'active':'' ?>">👥 Người dùng</a>
        <a href="/shop/admin/categories.php"      class="<?= $current_page==='categories.php'?'active':'' ?>">🏷️ Loại sản phẩm</a>
        <a href="/shop/admin/products.php"        class="<?= $current_page==='products.php'?'active':'' ?>">📦 Sản phẩm</a>
        <a href="/shop/admin/import.php"          class="<?= $current_page==='import.php'?'active':'' ?>">📥 Nhập hàng</a>
        <a href="/shop/admin/pricing.php"         class="<?= $current_page==='pricing.php'?'active':'' ?>">💰 Giá bán</a>
        <a href="/shop/admin/orders.php"          class="<?= $current_page==='orders.php'?'active':'' ?>">🧾 Đơn hàng</a>
        <a href="/shop/admin/inventory.php"       class="<?= $current_page==='inventory.php'?'active':'' ?>">🏪 Tồn kho</a>

        <div class="nav-group-title">Hệ thống</div>
        <a href="/shop/admin/logout.php">🚪 Đăng xuất</a>
    </nav>
</aside>

<!-- Main content -->
<main class="admin-content">
<div class="admin-topbar">
    <h1><?= isset($page_title) ? sanitize($page_title) : 'Dashboard' ?></h1>
    <div style="font-size:13px; color:var(--text-muted);">
        📅 <?= date('d/m/Y H:i') ?> &nbsp;|&nbsp;
        👤 <?= sanitize($_SESSION['fullname'] ?? '') ?>
    </div>
</div>

<?php if (isset($_SESSION['flash'])): ?>
<div class="alert alert-<?= $_SESSION['flash']['type'] ?>" style="margin-bottom:20px;">
    <?= $_SESSION['flash']['msg'] ?>
</div>
<?php unset($_SESSION['flash']); endif; ?>
