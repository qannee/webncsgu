<?php
$page_title = 'Giỏ Hàng';
require_once 'includes/header.php';
requireLogin('/shop/login.php?redirect=/shop/cart.php');

$user_id = $_SESSION['user_id'];

// Lấy giỏ hàng
$stmt = $pdo->prepare("SELECT c.*, p.name, p.image, p.sell_price, p.unit, p.stock, cat.name as cat_name
    FROM cart c JOIN products p ON c.product_id=p.id
    JOIN categories cat ON p.category_id=cat.id
    WHERE c.user_id=? ORDER BY c.id DESC");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

$subtotal = 0;
foreach ($cart_items as $item) $subtotal += $item['sell_price'] * $item['quantity'];

$ship_fee = $subtotal >= 200000 ? 0 : 20000;
$total = $subtotal + $ship_fee;
?>

<div class="breadcrumb">
    <a href="/shop/index.php">🏠 Trang chủ</a>
    <span class="sep">›</span>
    <span>🛒 Giỏ hàng</span>
</div>

<div style="padding: 32px 40px;">
    <h1 style="font-size:26px;font-weight:800;margin-bottom:24px;">🛒 Giỏ hàng của bạn</h1>

    <?php if (empty($cart_items)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="icon">🛒</div>
            <h3>Giỏ hàng trống</h3>
            <p>Hãy thêm sản phẩm vào giỏ hàng để tiếp tục mua sắm</p>
            <a href="/shop/products.php" class="btn btn-primary mt-2">🛍️ Mua sắm ngay</a>
        </div>
    </div>
    <?php else: ?>

    <div class="cart-layout">
        <!-- Danh sách sản phẩm -->
        <div class="card">
            <div class="card-header">
                <h3>📦 Sản phẩm (<?= count($cart_items) ?> loại)</h3>
                <form action="/shop/cart_action.php" method="POST" style="display:inline">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="product_id" value="all">
                </form>
            </div>
            <div class="card-body" style="padding:0">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Đơn giá</th>
                            <th>Số lượng</th>
                            <th>Thành tiền</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <div style="width:60px;height:60px;background:var(--bg);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:28px;overflow:hidden;flex-shrink:0;">
                                        <?php if ($item['image'] && file_exists(__DIR__.'/uploads/products/'.$item['image'])): ?>
                                            <img src="/shop/uploads/products/<?= $item['image'] ?>" style="width:60px;height:60px;object-fit:cover;border-radius:8px;">
                                        <?php else: ?>🥦<?php endif; ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:600;"><?= sanitize($item['name']) ?></div>
                                        <div style="font-size:12px;color:var(--text-muted);"><?= sanitize($item['cat_name']) ?> · <?= sanitize($item['unit']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-weight:600; color:var(--accent);"><?= formatPrice($item['sell_price']) ?></td>
                            <td>
                                <form action="/shop/cart_action.php" method="POST">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                    <div class="qty-control" style="gap:0">
                                        <button type="submit" name="quantity" value="<?= $item['quantity']-1 ?>">−</button>
                                        <input type="number" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" 
                                               onchange="this.form.querySelector('[name=quantity_val]').value=this.value;" 
                                               style="width:50px;height:34px;text-align:center;border:1px solid var(--border);border-left:none;border-right:none;font-size:14px;font-weight:600;outline:none;">
                                        <button type="submit" name="quantity" value="<?= $item['quantity']+1 ?>">+</button>
                                    </div>
                                </form>
                            </td>
                            <td style="font-weight:700; font-size:16px;"><?= formatPrice($item['sell_price'] * $item['quantity']) ?></td>
                            <td>
                                <form action="/shop/cart_action.php" method="POST">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Xóa sản phẩm này?')">🗑</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tóm tắt -->
        <div>
            <div class="card">
                <div class="card-header"><h3>💰 Tóm tắt đơn hàng</h3></div>
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;margin-bottom:12px;font-size:14px;">
                        <span>Tạm tính:</span>
                        <strong><?= formatPrice($subtotal) ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:12px;font-size:14px;">
                        <span>Phí vận chuyển:</span>
                        <strong><?= $ship_fee == 0 ? '<span style="color:var(--primary)">Miễn phí</span>' : formatPrice($ship_fee) ?></strong>
                    </div>
                    <?php if ($subtotal < 200000): ?>
                    <div class="alert alert-info" style="font-size:12px;">
                        🚚 Mua thêm <?= formatPrice(200000 - $subtotal) ?> để được miễn phí vận chuyển
                    </div>
                    <?php endif; ?>
                    <hr style="border:none;border-top:2px solid var(--border);margin:16px 0;">
                    <div style="display:flex;justify-content:space-between;font-size:20px;font-weight:800;color:var(--accent);margin-bottom:20px;">
                        <span>Tổng cộng:</span>
                        <span><?= formatPrice($total) ?></span>
                    </div>
                    <a href="/shop/checkout.php" class="btn btn-accent btn-full btn-lg">✅ Đặt hàng ngay</a>
                    <a href="/shop/products.php" class="btn btn-outline btn-full mt-1">← Tiếp tục mua sắm</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
