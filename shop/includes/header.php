<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
$categories = getCategories($pdo);
$cart_count = (isLoggedIn() && $_SESSION['role']==='customer') ? getCartCount($pdo, $_SESSION['user_id']) : 0;
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? sanitize($page_title) . ' - ' : '' ?>🌿 FoodShop - Thực Phẩm Sạch</title>
<link rel="stylesheet" href="/shop/assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="header-top">
        🚚 Miễn phí giao hàng cho đơn từ 200.000₫ &nbsp;|&nbsp; 📞 Hotline: 1800-1234 &nbsp;|&nbsp; ⏰ 7:00 - 21:00 mỗi ngày
    </div>
    <div class="header-main">
        <a href="/shop/index.php" class="logo">
            <span class="logo-icon">🌿</span>
            <div><div>FoodShop</div><div style="font-size:12px;font-weight:400;color:var(--text-muted)">Thực Phẩm Sạch</div></div>
        </a>

        <form class="search-bar" action="/shop/products.php" method="GET">
            <input type="text" name="q" placeholder="Tìm kiếm rau, thịt, trái cây..." value="<?= sanitize($_GET['q'] ?? '') ?>">
            <button type="submit">🔍</button>
        </form>

        <div class="header-actions">
            <?php if (isLoggedIn()): ?>
                <div class="user-dropdown">
                    <button class="btn-icon">
                        <span class="icon">👤</span>
                        <span><?= sanitize($_SESSION['fullname'] ?? 'Tài khoản') ?></span>
                        <span style="font-size:10px">▼</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="/shop/profile.php">⚙️ Thông tin cá nhân</a>
                        <a href="/shop/orders.php">📦 Đơn hàng của tôi</a>
                        <hr>
                        <a href="/shop/logout.php">🚪 Đăng xuất</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/shop/login.php" class="btn-icon"><span class="icon">👤</span> Đăng nhập</a>
                <a href="/shop/register.php" class="btn btn-outline btn-sm">Đăng ký</a>
            <?php endif; ?>

            <a href="/shop/cart.php" class="btn-icon btn-cart" style="position:relative">
                <span class="icon">🛒</span> Giỏ hàng
                <?php if ($cart_count > 0): ?>
                    <span class="cart-badge"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <nav class="header-nav">
        <ul class="nav-list">
            <li><a href="/shop/index.php" class="<?= $current_page==='index.php'&&$current_dir!=='shop'||$current_page==='index.php'?'active':'' ?>">🏠 Trang chủ</a></li>
            <?php foreach ($categories as $cat): ?>
                <li><a href="/shop/products.php?cat=<?= $cat['id'] ?>"
                       class="<?= (isset($_GET['cat']) && $_GET['cat']==$cat['id'])?'active':'' ?>">
                    <?= sanitize($cat['name']) ?>
                </a></li>
            <?php endforeach; ?>
            <li><a href="/shop/products.php">🛍️ Tất cả sản phẩm</a></li>
        </ul>
    </nav>
</header>

<?php if (isset($_SESSION['flash'])): ?>
<div style="padding: 0 40px; margin-top:12px;">
    <div class="alert alert-<?= $_SESSION['flash']['type'] ?>">
        <?= $_SESSION['flash']['msg'] ?>
    </div>
</div>
<?php unset($_SESSION['flash']); endif; ?>
