<?php
$page_title = 'Trang Chủ';
require_once 'includes/header.php';

// Lấy sản phẩm nổi bật
$featured = $pdo->query("SELECT p.*, c.name as cat_name FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.status='show' ORDER BY p.id DESC LIMIT 8")->fetchAll();

// Sản phẩm bán chạy (có nhiều trong đơn hàng nhất)
$best = $pdo->query("SELECT p.*, c.name as cat_name, 
    COUNT(oi.id) as sold FROM products p 
    JOIN categories c ON p.category_id = c.id 
    LEFT JOIN order_items oi ON oi.product_id = p.id 
    WHERE p.status='show' GROUP BY p.id ORDER BY sold DESC LIMIT 4")->fetchAll();

$cat_icons = [1=>'🥦', 2=>'🍎', 3=>'🥩', 4=>'🌾', 5=>'🥛'];
$prod_icons = ['🥦','🍅','🌽','🥕','🍎','🍋','🥩','🥚','🧅','🫛'];
?>

<!-- HERO BANNER -->
<section class="hero-banner">
    <h1>🌿 Thực Phẩm Sạch Mỗi Ngày</h1>
    <p>Tươi ngon - An toàn - Giao hàng tận nhà</p>
    <a href="/shop/products.php" class="btn btn-accent btn-lg">🛍️ Mua sắm ngay</a>
</section>

<!-- DANH MỤC -->
<section class="section">
    <div class="section-title">🏷️ Danh mục sản phẩm</div>
    <div class="category-grid">
        <?php foreach ($categories as $cat): ?>
        <a href="/shop/products.php?cat=<?= $cat['id'] ?>" class="cat-card">
            <div class="cat-icon"><?= $cat_icons[$cat['id']] ?? '🌿' ?></div>
            <div class="cat-name"><?= sanitize($cat['name']) ?></div>
        </a>
        <?php endforeach; ?>
        <a href="/shop/products.php" class="cat-card">
            <div class="cat-icon">🛍️</div>
            <div class="cat-name">Tất cả</div>
        </a>
    </div>
</section>

<!-- SẢN PHẨM MỚI -->
<section class="section" style="background:white;padding:40px;">
    <div class="section-title">🆕 Sản phẩm mới nhất</div>
    <div class="product-grid">
        <?php foreach ($featured as $i => $p): ?>
        <div class="product-card">
            <a href="/shop/product.php?id=<?= $p['id'] ?>">
                <div class="img-wrap">
                    <?php if ($p['image'] && file_exists(__DIR__.'/uploads/products/'.$p['image'])): ?>
                        <img src="/shop/uploads/products/<?= $p['image'] ?>" alt="<?= sanitize($p['name']) ?>">
                    <?php else: ?>
                        <?= $prod_icons[$i % count($prod_icons)] ?>
                    <?php endif; ?>
                </div>
            </a>
            <div class="card-body">
                <div class="prod-cat"><?= sanitize($p['cat_name']) ?></div>
                <a href="/shop/product.php?id=<?= $p['id'] ?>">
                    <div class="prod-name"><?= sanitize($p['name']) ?></div>
                </a>
                <div class="prod-unit">📦 <?= sanitize($p['unit']) ?></div>
                <div class="prod-price"><?= formatPrice($p['sell_price']) ?></div>
                <div class="card-actions">
                    <form action="/shop/cart_action.php" method="POST" style="flex:1">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-accent btn-sm btn-full">🛒 Thêm giỏ</button>
                    </form>
                    <a href="/shop/product.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">👁</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- BANNER FEATURES -->
<section style="padding: 40px; background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);">
    <div style="display:grid; grid-template-columns: repeat(4,1fr); gap:20px; text-align:center;">
        <div style="padding:24px; background:white; border-radius:12px; box-shadow:var(--shadow);">
            <div style="font-size:40px; margin-bottom:10px;">🌱</div>
            <h4 style="color:var(--primary); margin-bottom:6px;">100% Sạch</h4>
            <p style="font-size:13px; color:var(--text-muted);">Đảm bảo nguồn gốc rõ ràng, không thuốc trừ sâu</p>
        </div>
        <div style="padding:24px; background:white; border-radius:12px; box-shadow:var(--shadow);">
            <div style="font-size:40px; margin-bottom:10px;">🚚</div>
            <h4 style="color:var(--primary); margin-bottom:6px;">Giao hàng nhanh</h4>
            <p style="font-size:13px; color:var(--text-muted);">Miễn phí giao hàng cho đơn từ 200.000₫</p>
        </div>
        <div style="padding:24px; background:white; border-radius:12px; box-shadow:var(--shadow);">
            <div style="font-size:40px; margin-bottom:10px;">💯</div>
            <h4 style="color:var(--primary); margin-bottom:6px;">Chất lượng cao</h4>
            <p style="font-size:13px; color:var(--text-muted);">Kiểm tra chất lượng trước khi giao đến tay bạn</p>
        </div>
        <div style="padding:24px; background:white; border-radius:12px; box-shadow:var(--shadow);">
            <div style="font-size:40px; margin-bottom:10px;">🔄</div>
            <h4 style="color:var(--primary); margin-bottom:6px;">Đổi trả dễ</h4>
            <p style="font-size:13px; color:var(--text-muted);">Hoàn tiền 100% nếu sản phẩm không đảm bảo</p>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
