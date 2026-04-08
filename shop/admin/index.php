<?php
$page_title = 'Dashboard';
require_once 'includes/header.php';

// Stats
$total_orders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$total_revenue  = $pdo->query("SELECT SUM(total) FROM orders WHERE status='done'")->fetchColumn() ?: 0;
$total_products = $pdo->query("SELECT COUNT(*) FROM products WHERE status='show'")->fetchColumn();
$total_users    = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$low_stock      = $pdo->query("SELECT COUNT(*) FROM products WHERE stock <= " . LOW_STOCK_THRESHOLD . " AND status='show'")->fetchColumn();

// Recent orders
$recent_orders = $pdo->query("SELECT o.*, u.fullname FROM orders o JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 8")->fetchAll();

// Top products
$top_products = $pdo->query("SELECT p.name, p.unit, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi JOIN products p ON oi.product_id=p.id
    GROUP BY oi.product_id ORDER BY total_sold DESC LIMIT 5")->fetchAll();

$status_labels = [
    'pending'   => ['label'=>'Chờ xử lý', 'badge'=>'badge-warning'],
    'confirmed' => ['label'=>'Đã xác nhận','badge'=>'badge-info'],
    'shipping'  => ['label'=>'Đang giao',  'badge'=>'badge-info'],
    'done'      => ['label'=>'Hoàn thành', 'badge'=>'badge-success'],
    'cancelled' => ['label'=>'Đã hủy',     'badge'=>'badge-danger'],
];
?>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card" style="border-color:#2196f3">
        <div class="stat-icon">🧾</div>
        <div class="stat-info">
            <div class="label">Tổng đơn hàng</div>
            <div class="value"><?= $total_orders ?></div>
        </div>
    </div>
    <div class="stat-card" style="border-color:#ff9800">
        <div class="stat-icon">⏳</div>
        <div class="stat-info">
            <div class="label">Chờ xử lý</div>
            <div class="value"><?= $pending_orders ?></div>
        </div>
    </div>
    <div class="stat-card" style="border-color:#4caf50">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
            <div class="label">Doanh thu</div>
            <div class="value" style="font-size:18px"><?= formatPrice($total_revenue) ?></div>
        </div>
    </div>
    <div class="stat-card" style="border-color:#9c27b0">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
            <div class="label">Khách hàng</div>
            <div class="value"><?= $total_users ?></div>
        </div>
    </div>
</div>

<!-- Alert low stock -->
<?php if ($low_stock > 0): ?>
<div class="alert alert-warning" style="margin-bottom:24px;">
    ⚠️ Có <strong><?= $low_stock ?></strong> sản phẩm sắp hết hàng!
    <a href="/shop/admin/inventory.php?filter=low" style="color:inherit;font-weight:700;margin-left:8px;">Xem ngay →</a>
</div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:2fr 1fr; gap:24px;">
    <!-- Recent orders -->
    <div class="card">
        <div class="card-header">
            <h3>🧾 Đơn hàng gần đây</h3>
            <a href="/shop/admin/orders.php" class="btn btn-outline btn-sm">Xem tất cả</a>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th>Mã đơn</th><th>Khách hàng</th><th>Tổng tiền</th><th>Trạng thái</th><th>Ngày</th>
                </tr></thead>
                <tbody>
                <?php foreach ($recent_orders as $ord): ?>
                <tr>
                    <td><a href="/shop/admin/orders.php?detail=<?= $ord['id'] ?>" style="color:var(--primary);font-weight:600;"><?= $ord['order_code'] ?></a></td>
                    <td><?= sanitize($ord['fullname']) ?></td>
                    <td style="font-weight:700;color:var(--accent)"><?= formatPrice($ord['total']) ?></td>
                    <td><span class="badge <?= $status_labels[$ord['status']]['badge'] ?>"><?= $status_labels[$ord['status']]['label'] ?></span></td>
                    <td style="font-size:12px;color:var(--text-muted)"><?= date('d/m H:i', strtotime($ord['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top products -->
    <div class="card">
        <div class="card-header"><h3>🏆 Bán chạy nhất</h3></div>
        <div class="card-body" style="padding:0">
            <?php if (empty($top_products)): ?>
            <div style="padding:30px;text-align:center;color:var(--text-muted);">Chưa có dữ liệu</div>
            <?php else: ?>
            <?php foreach ($top_products as $i => $tp): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid var(--border);">
                <div style="width:28px;height:28px;background:var(--primary);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;"><?= $i+1 ?></div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= sanitize($tp['name']) ?></div>
                    <div style="font-size:12px;color:var(--text-muted);">Đã bán: <?= $tp['total_sold'] ?> <?= sanitize($tp['unit']) ?></div>
                </div>
                <div style="font-size:12px;font-weight:700;color:var(--primary);white-space:nowrap;"><?= formatPrice($tp['revenue']) ?></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
