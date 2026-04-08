<?php
$page_title = 'Đơn Hàng Của Tôi';
require_once 'includes/header.php';
requireLogin();

$user_id = $_SESSION['user_id'];

$orders = $pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
$orders->execute([$user_id]);
$orders = $orders->fetchAll();

$status_labels = [
    'pending'   => ['label'=>'Chờ xác nhận', 'badge'=>'badge-warning'],
    'confirmed' => ['label'=>'Đã xác nhận',  'badge'=>'badge-info'],
    'shipping'  => ['label'=>'Đang giao',     'badge'=>'badge-info'],
    'done'      => ['label'=>'Đã giao',       'badge'=>'badge-success'],
    'cancelled' => ['label'=>'Đã hủy',        'badge'=>'badge-danger'],
];
?>

<div class="breadcrumb">
    <a href="/shop/index.php">🏠 Trang chủ</a>
    <span class="sep">›</span>
    <span>📦 Đơn hàng của tôi</span>
</div>

<div style="padding:32px 40px;">
    <h1 style="font-size:26px;font-weight:800;margin-bottom:24px;">📦 Lịch sử đơn hàng</h1>

    <?php if (empty($orders)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="icon">📦</div>
            <h3>Bạn chưa có đơn hàng nào</h3>
            <a href="/shop/products.php" class="btn btn-primary mt-2">🛍️ Mua sắm ngay</a>
        </div>
    </div>
    <?php else: ?>

    <?php foreach ($orders as $order): ?>
    <?php
    $items = $pdo->prepare("SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
    $items->execute([$order['id']]);
    $items = $items->fetchAll();
    $st = $status_labels[$order['status']] ?? ['label'=>$order['status'],'badge'=>'badge-secondary'];
    ?>
    <div class="card mb-3">
        <div class="card-header">
            <div>
                <span style="font-weight:700;">🧾 <?= $order['order_code'] ?></span>
                <span style="font-size:13px;color:var(--text-muted);margin-left:12px;">
                    📅 <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                </span>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                <span class="badge <?= $st['badge'] ?>"><?= $st['label'] ?></span>
                <strong style="font-size:18px;color:var(--accent);"><?= formatPrice($order['total']) ?></strong>
            </div>
        </div>
        <div style="padding:16px 24px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:16px;">
                <div>
                    <div style="font-size:12px;color:var(--text-muted);margin-bottom:2px;">📍 Địa chỉ giao hàng</div>
                    <div style="font-size:14px;"><?= sanitize($order['ship_name']) ?> | 📱 <?= sanitize($order['ship_phone']) ?></div>
                    <div style="font-size:13px;color:var(--text-muted);"><?= sanitize($order['ship_address'].', '.$order['ship_ward'].', '.$order['ship_district'].', '.$order['ship_city']) ?></div>
                </div>
                <div>
                    <div style="font-size:12px;color:var(--text-muted);margin-bottom:2px;">💳 Thanh toán</div>
                    <div style="font-size:14px;">
                        <?= $order['payment_method']==='cash' ? '💵 Tiền mặt khi nhận hàng' : ($order['payment_method']==='transfer' ? '🏦 Chuyển khoản' : '💳 Trực tuyến') ?>
                    </div>
                </div>
            </div>

            <table class="table" style="font-size:13px;">
                <thead><tr><th>Sản phẩm</th><th>Đơn giá</th><th>SL</th><th>Thành tiền</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= sanitize($item['name']) ?></td>
                    <td><?= formatPrice($item['price']) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><strong><?= formatPrice($item['price'] * $item['quantity']) ?></strong></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
