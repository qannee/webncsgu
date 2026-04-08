<?php
$page_title = 'Quản Lý Đơn Hàng';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)$_POST['order_id'];
    $status = $_POST['status'] ?? '';
    $valid  = ['pending','confirmed','shipping','done','cancelled'];
    if ($id && in_array($status, $valid)) {
        $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status, $id]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'✅ Đã cập nhật trạng thái đơn hàng!'];
    }
    redirect('/shop/admin/orders.php');
}

// Filters
$status_filter  = $_GET['status'] ?? '';
$date_from      = $_GET['date_from'] ?? '';
$date_to        = $_GET['date_to'] ?? '';
$ward_filter    = trim($_GET['ward'] ?? '');
$detail_id      = (int)($_GET['detail'] ?? 0);

$where = []; $params = [];
if ($status_filter) { $where[] = "o.status=?"; $params[] = $status_filter; }
if ($date_from)     { $where[] = "DATE(o.created_at) >= ?"; $params[] = $date_from; }
if ($date_to)       { $where[] = "DATE(o.created_at) <= ?"; $params[] = $date_to; }
if ($ward_filter)   { $where[] = "o.ship_ward LIKE ?"; $params[] = "%$ward_filter%"; }
$w = $where ? 'WHERE '.implode(' AND ',$where) : '';

$orders = $pdo->prepare("SELECT o.*, u.fullname, u.email FROM orders o JOIN users u ON o.user_id=u.id $w ORDER BY o.created_at DESC");
$orders->execute($params);
$orders = $orders->fetchAll();

$status_labels = [
    'pending'   => ['label'=>'Chờ xử lý',   'badge'=>'badge-warning'],
    'confirmed' => ['label'=>'Đã xác nhận',  'badge'=>'badge-info'],
    'shipping'  => ['label'=>'Đang giao',    'badge'=>'badge-info'],
    'done'      => ['label'=>'Hoàn thành',   'badge'=>'badge-success'],
    'cancelled' => ['label'=>'Đã hủy',       'badge'=>'badge-danger'],
];

// Detail
$detail_order = null;
if ($detail_id) {
    $ds = $pdo->prepare("SELECT o.*, u.fullname, u.email, u.phone as uphone FROM orders o JOIN users u ON o.user_id=u.id WHERE o.id=?");
    $ds->execute([$detail_id]); $detail_order = $ds->fetch();
    if ($detail_order) {
        $di = $pdo->prepare("SELECT oi.*, p.name, p.unit FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
        $di->execute([$detail_id]); $detail_items = $di->fetchAll();
    }
}
?>

<?php if ($detail_order): ?>
<!-- Order Detail -->
<div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;">
    <a href="/shop/admin/orders.php" class="btn btn-secondary">← Quay lại</a>
    <h2 style="font-size:20px;font-weight:700;">🧾 Chi tiết đơn: <?= $detail_order['order_code'] ?></h2>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
    <div class="card">
        <div class="card-header"><h3>👤 Thông tin khách hàng</h3></div>
        <div class="card-body" style="font-size:14px;line-height:2;">
            <div>👤 <strong><?= sanitize($detail_order['fullname']) ?></strong></div>
            <div>📧 <?= sanitize($detail_order['email']) ?></div>
            <div>📅 <?= date('d/m/Y H:i', strtotime($detail_order['created_at'])) ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3>📍 Thông tin giao hàng</h3></div>
        <div class="card-body" style="font-size:14px;line-height:2;">
            <div>👤 <?= sanitize($detail_order['ship_name']) ?></div>
            <div>📱 <?= sanitize($detail_order['ship_phone']) ?></div>
            <div>📍 <?= sanitize($detail_order['ship_address'].', '.$detail_order['ship_ward'].', '.$detail_order['ship_district'].', '.$detail_order['ship_city']) ?></div>
            <div>💳 <?= $detail_order['payment_method']==='cash'?'Tiền mặt COD':($detail_order['payment_method']==='transfer'?'Chuyển khoản':'Trực tuyến') ?></div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h3>📦 Sản phẩm đặt hàng</h3></div>
    <table class="table">
        <thead><tr><th>Sản phẩm</th><th>Đơn vị</th><th>Đơn giá</th><th>SL</th><th>Thành tiền</th></tr></thead>
        <tbody>
        <?php foreach ($detail_items as $item): ?>
        <tr>
            <td><strong><?= sanitize($item['name']) ?></strong></td>
            <td><?= sanitize($item['unit']) ?></td>
            <td><?= formatPrice($item['price']) ?></td>
            <td><?= $item['quantity'] ?></td>
            <td><strong><?= formatPrice($item['price']*$item['quantity']) ?></strong></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="4" style="text-align:right;font-weight:700;">Tổng cộng:</td>
            <td style="font-weight:800;font-size:20px;color:var(--accent)"><?= formatPrice($detail_order['total']) ?></td></tr>
        </tfoot>
    </table>
</div>

<div class="card">
    <div class="card-header"><h3>📋 Cập nhật trạng thái</h3></div>
    <div class="card-body">
        <form method="POST" style="display:flex;gap:12px;align-items:flex-end;">
            <input type="hidden" name="order_id" value="<?= $detail_order['id'] ?>">
            <div class="form-group" style="margin:0">
                <label>Trạng thái mới</label>
                <select name="status" class="form-control">
                    <?php foreach ($status_labels as $val => $info): ?>
                    <option value="<?= $val ?>" <?= $detail_order['status']===$val?'selected':'' ?>><?= $info['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">💾 Cập nhật</button>
        </form>
        <div style="margin-top:12px;">
            Trạng thái hiện tại: <span class="badge <?= $status_labels[$detail_order['status']]['badge'] ?>">
                <?= $status_labels[$detail_order['status']]['label'] ?>
            </span>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Orders List -->
<form style="display:grid;grid-template-columns:repeat(5,auto);gap:10px;margin-bottom:20px;align-items:end;flex-wrap:wrap;">
    <div class="form-group" style="margin:0">
        <label style="font-size:13px">Trạng thái</label>
        <select name="status" class="form-control">
            <option value="">Tất cả</option>
            <?php foreach ($status_labels as $v => $i): ?>
            <option value="<?= $v ?>" <?= $status_filter===$v?'selected':'' ?>><?= $i['label'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group" style="margin:0">
        <label style="font-size:13px">Từ ngày</label>
        <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
    </div>
    <div class="form-group" style="margin:0">
        <label style="font-size:13px">Đến ngày</label>
        <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
    </div>
    <div class="form-group" style="margin:0">
        <label style="font-size:13px">Phường/Xã</label>
        <input type="text" name="ward" class="form-control" placeholder="Lọc theo phường..." value="<?= sanitize($ward_filter) ?>">
    </div>
    <div style="display:flex;gap:8px;">
        <button type="submit" class="btn btn-primary">🔍 Lọc</button>
        <a href="/shop/admin/orders.php" class="btn btn-secondary">✕</a>
    </div>
</form>

<div class="card">
    <div class="card-header">
        <h3>🧾 Danh sách đơn hàng (<?= count($orders) ?>)</h3>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Giao đến</th><th>Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th>Ngày đặt</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $ord): ?>
            <tr>
                <td><a href="/shop/admin/orders.php?detail=<?= $ord['id'] ?>" style="color:var(--primary);font-weight:700;"><?= $ord['order_code'] ?></a></td>
                <td><?= sanitize($ord['fullname']) ?></td>
                <td style="font-size:12px;"><?= sanitize($ord['ship_ward'].', '.$ord['ship_district']) ?></td>
                <td style="font-weight:700;color:var(--accent)"><?= formatPrice($ord['total']) ?></td>
                <td style="font-size:12px"><?= $ord['payment_method']==='cash'?'💵 COD':($ord['payment_method']==='transfer'?'🏦 CK':'💳 Online') ?></td>
                <td>
                    <form method="POST" style="display:flex;gap:4px;align-items:center;">
                        <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                        <select name="status" class="form-control" style="padding:4px 8px;font-size:12px;width:130px;">
                            <?php foreach ($status_labels as $v => $i): ?>
                            <option value="<?= $v ?>" <?= $ord['status']===$v?'selected':'' ?>><?= $i['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">💾</button>
                    </form>
                </td>
                <td style="font-size:12px;color:var(--text-muted)"><?= date('d/m H:i', strtotime($ord['created_at'])) ?></td>
                <td><a href="/shop/admin/orders.php?detail=<?= $ord['id'] ?>" class="btn btn-outline btn-sm">👁️ Chi tiết</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
            <tr><td colspan="8" class="text-center" style="padding:30px;color:var(--text-muted)">Không có đơn hàng nào</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
