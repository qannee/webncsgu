<?php
$page_title = 'Quản Lý Tồn Kho';
require_once 'includes/header.php';

$filter     = $_GET['filter'] ?? '';
$cat_id     = (int)($_GET['cat'] ?? 0);
$date_from  = $_GET['date_from'] ?? '';
$date_to    = $_GET['date_to'] ?? '';
$date_check = $_GET['date_check'] ?? date('Y-m-d');
$low_thresh = (int)($_GET['low_thresh'] ?? LOW_STOCK_THRESHOLD);

$cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Current stock query
$where = ['p.status="show"']; $params = [];
if ($cat_id)         { $where[] = "p.category_id=?"; $params[] = $cat_id; }
if ($filter === 'low') { $where[] = "p.stock <= ?"; $params[] = $low_thresh; }
$w = 'WHERE '.implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id=c.id $w ORDER BY p.stock ASC, c.name, p.name");
$stmt->execute($params);
$products = $stmt->fetchAll();

// In-out report for date range
$report = [];
if ($date_from && $date_to) {
    $rpt = $pdo->prepare("SELECT p.id, p.name, p.unit, p.stock,
        COALESCE(SUM(CASE WHEN ir.status='completed' AND DATE(ir.import_date) BETWEEN ? AND ? THEN id2.quantity ELSE 0 END),0) as imported,
        COALESCE(SUM(CASE WHEN DATE(o.created_at) BETWEEN ? AND ? AND o.status NOT IN ('cancelled') THEN oi.quantity ELSE 0 END),0) as sold
        FROM products p
        LEFT JOIN import_details id2 ON id2.product_id=p.id
        LEFT JOIN import_receipts ir ON ir.id=id2.receipt_id
        LEFT JOIN order_items oi ON oi.product_id=p.id
        LEFT JOIN orders o ON o.id=oi.order_id
        WHERE p.status='show'
        GROUP BY p.id ORDER BY p.name");
    $rpt->execute([$date_from, $date_to, $date_from, $date_to]);
    $report = $rpt->fetchAll();
}
?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
    <!-- Current stock -->
    <div>
        <div class="card mb-3">
            <div class="card-header"><h3>🏪 Tra cứu tồn kho hiện tại</h3></div>
            <div class="card-body">
                <form style="display:flex;gap:10px;flex-wrap:wrap;">
                    <select name="cat" class="form-control" style="width:180px">
                        <option value="">-- Tất cả loại --</option>
                        <?php foreach ($cats as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $cat_id==$c['id']?'selected':'' ?>><?= sanitize($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <input type="checkbox" name="filter" value="low" id="low_cb" <?= $filter==='low'?'checked':'' ?> onchange="this.form.submit()">
                        <label for="low_cb" style="font-size:13px;cursor:pointer;">⚠️ Sắp hết hàng (≤
                            <input type="number" name="low_thresh" value="<?= $low_thresh ?>" style="width:50px;padding:3px 6px;border:1px solid var(--border);border-radius:4px;font-size:13px;">
                        )</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">🔍</button>
                    <a href="/shop/admin/inventory.php" class="btn btn-secondary btn-sm">✕</a>
                </form>
            </div>
        </div>

        <?php
        $low_count = count(array_filter($products, fn($p) => $p['stock'] <= $low_thresh));
        if ($low_count > 0):
        ?>
        <div class="alert alert-warning">⚠️ <strong><?= $low_count ?></strong> sản phẩm sắp hết hàng!</div>
        <?php endif; ?>

        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Sản phẩm</th><th>Loại</th><th>Đơn vị</th><th>Tồn kho</th></tr></thead>
                    <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr <?= $p['stock'] <= $low_thresh ? 'style="background:#fff8e1"' : '' ?>>
                        <td><strong><?= sanitize($p['name']) ?></strong></td>
                        <td style="font-size:12px"><?= sanitize($p['cat_name']) ?></td>
                        <td><?= sanitize($p['unit']) ?></td>
                        <td>
                            <span class="badge <?= $p['stock'] == 0 ? 'badge-danger' : ($p['stock'] <= $low_thresh ? 'badge-warning' : 'badge-success') ?>">
                                <?= $p['stock'] ?>
                                <?= $p['stock'] <= $low_thresh && $p['stock'] > 0 ? ' ⚠️' : '' ?>
                                <?= $p['stock'] == 0 ? ' Hết hàng' : '' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- In-out report -->
    <div>
        <div class="card mb-3">
            <div class="card-header"><h3>📊 Báo cáo nhập - xuất theo kỳ</h3></div>
            <div class="card-body">
                <form method="GET">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Từ ngày</label>
                            <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
                        </div>
                        <div class="form-group">
                            <label>Đến ngày</label>
                            <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">📊 Xem báo cáo</button>
                </form>
            </div>
        </div>

        <?php if (!empty($report)): ?>
        <div class="card">
            <div class="card-header">
                <h3>Nhập - Xuất: <?= $date_from ?> → <?= $date_to ?></h3>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Sản phẩm</th><th>ĐV</th><th>Nhập</th><th>Xuất</th><th>Tồn hiện tại</th></tr></thead>
                    <tbody>
                    <?php foreach ($report as $r): ?>
                    <?php if ($r['imported'] > 0 || $r['sold'] > 0): ?>
                    <tr>
                        <td style="font-size:13px"><strong><?= sanitize($r['name']) ?></strong></td>
                        <td><?= sanitize($r['unit']) ?></td>
                        <td style="color:var(--primary);font-weight:600;">+<?= $r['imported'] ?></td>
                        <td style="color:var(--accent);font-weight:600;">-<?= $r['sold'] ?></td>
                        <td><span class="badge <?= $r['stock'] <= $low_thresh ? 'badge-warning' : 'badge-success' ?>"><?= $r['stock'] ?></span></td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:var(--bg);">
                            <td colspan="2" style="font-weight:700;">Tổng cộng</td>
                            <td style="color:var(--primary);font-weight:800;">+<?= array_sum(array_column($report,'imported')) ?></td>
                            <td style="color:var(--accent);font-weight:800;">-<?= array_sum(array_column($report,'sold')) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php elseif ($date_from && $date_to): ?>
        <div class="alert alert-info">Không có dữ liệu trong khoảng thời gian này.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
