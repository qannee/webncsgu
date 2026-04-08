<?php
require_once 'includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { redirect('/shop/products.php'); }

$stmt = $pdo->prepare("SELECT p.*, c.name as cat_name FROM products p 
    JOIN categories c ON p.category_id=c.id WHERE p.id=? AND p.status='show'");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { redirect('/shop/products.php'); }

$page_title = $p['name'];

// Sản phẩm liên quan
$related = $pdo->prepare("SELECT * FROM products WHERE category_id=? AND id!=? AND status='show' LIMIT 4");
$related->execute([$p['category_id'], $id]);
$related = $related->fetchAll();

$prod_icons = ['🥦','🍅','🌽','🥕','🍎','🍋','🥩','🥚','🧅','🫛'];
?>

<div class="breadcrumb">
    <a href="/shop/index.php">🏠 Trang chủ</a>
    <span class="sep">›</span>
    <a href="/shop/products.php?cat=<?= $p['category_id'] ?>"><?= sanitize($p['cat_name']) ?></a>
    <span class="sep">›</span>
    <span><?= sanitize($p['name']) ?></span>
</div>

<div class="card" style="margin:24px 40px;">
    <div class="product-detail">
        <!-- Ảnh -->
        <div class="prod-img-main">
            <?php if ($p['image'] && file_exists(__DIR__.'/uploads/products/'.$p['image'])): ?>
                <img src="/shop/uploads/products/<?= $p['image'] ?>" alt="<?= sanitize($p['name']) ?>">
            <?php else: ?>
                🥦
            <?php endif; ?>
        </div>

        <!-- Thông tin -->
        <div class="prod-detail-info">
            <div class="badge badge-info mb-2"><?= sanitize($p['cat_name']) ?></div>
            <h1 class="name"><?= sanitize($p['name']) ?></h1>
            <div class="meta">🏷️ Mã SP: <strong><?= sanitize($p['code']) ?></strong></div>
            <div class="meta">📦 Đơn vị: <strong><?= sanitize($p['unit']) ?></strong></div>

            <div class="price"><?= formatPrice($p['sell_price']) ?></div>

            <?php if ($p['stock'] > 0): ?>
                <span class="stock-badge in-stock">✅ Còn hàng (<?= $p['stock'] ?> <?= sanitize($p['unit']) ?>)</span>
            <?php else: ?>
                <span class="stock-badge out-stock">❌ Hết hàng</span>
            <?php endif; ?>

            <?php if ($p['description']): ?>
            <div style="margin: 20px 0; padding: 16px; background: var(--bg); border-radius: 8px; line-height: 1.7; font-size: 14px;">
                <?= nl2br(sanitize($p['description'])) ?>
            </div>
            <?php endif; ?>

            <?php if ($p['stock'] > 0): ?>
            <form action="/shop/cart_action.php" method="POST">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                <input type="hidden" name="redirect" value="/shop/product.php?id=<?= $p['id'] ?>">
                <div style="display:flex; align-items:center; gap:16px; margin-bottom:16px;">
                    <label style="font-weight:600;">Số lượng:</label>
                    <div class="qty-control">
                        <button type="button" onclick="changeQty(-1)">−</button>
                        <input type="number" name="quantity" id="qty" value="1" min="1" max="<?= $p['stock'] ?>">
                        <button type="button" onclick="changeQty(1)">+</button>
                    </div>
                </div>
                <div style="display:flex; gap:12px;">
                    <button type="submit" class="btn btn-accent btn-lg">🛒 Thêm vào giỏ</button>
                    <a href="/shop/cart.php" class="btn btn-outline btn-lg">Xem giỏ hàng</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Sản phẩm liên quan -->
<?php if ($related): ?>
<section class="section">
    <div class="section-title">🔗 Sản phẩm liên quan</div>
    <div class="product-grid">
        <?php foreach ($related as $i => $rp): ?>
        <div class="product-card">
            <a href="/shop/product.php?id=<?= $rp['id'] ?>">
                <div class="img-wrap">
                    <?php if ($rp['image'] && file_exists(__DIR__.'/uploads/products/'.$rp['image'])): ?>
                        <img src="/shop/uploads/products/<?= $rp['image'] ?>" alt="<?= sanitize($rp['name']) ?>">
                    <?php else: ?>
                        <?= $prod_icons[$i % count($prod_icons)] ?>
                    <?php endif; ?>
                </div>
            </a>
            <div class="card-body">
                <div class="prod-name"><a href="/shop/product.php?id=<?= $rp['id'] ?>"><?= sanitize($rp['name']) ?></a></div>
                <div class="prod-price"><?= formatPrice($rp['sell_price']) ?></div>
                <form action="/shop/cart_action.php" method="POST">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $rp['id'] ?>">
                    <button type="submit" class="btn btn-accent btn-sm btn-full">🛒 Thêm giỏ</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<script>
function changeQty(delta) {
    const input = document.getElementById('qty');
    let v = parseInt(input.value) + delta;
    if (v < 1) v = 1;
    if (v > parseInt(input.max)) v = parseInt(input.max);
    input.value = v;
}
</script>

<?php require_once 'includes/footer.php'; ?>
