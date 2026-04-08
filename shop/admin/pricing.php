<?php
$page_title = 'Quản Lý Giá Bán';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)$_POST['id'];
    $profit = (float)$_POST['profit_rate'];
    if ($id) {
        $cost = $pdo->prepare("SELECT cost_price FROM products WHERE id=?");
        $cost->execute([$id]); $cost = $cost->fetchColumn();
        $new_sell = round($cost * (1 + $profit / 100), 0);
        $pdo->prepare("UPDATE products SET profit_rate=?, sell_price=? WHERE id=?")->execute([$profit, $new_sell, $id]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'✅ Đã cập nhật giá bán!'];
    }
    redirect('/shop/admin/pricing.php');
}

$q      = trim($_GET['q'] ?? '');
$cat_id = (int)($_GET['cat'] ?? 0);
$where  = []; $params = [];
if ($q) { $where[] = "p.name LIKE ?"; $params[] = "%$q%"; }
if ($cat_id) { $where[] = "p.category_id=?"; $params[] = $cat_id; }
$w = $where ? 'WHERE '.implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id=c.id $w ORDER BY c.name, p.name");
$stmt->execute($params);
$products = $stmt->fetchAll();
$cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center;">
    <form style="display:flex;gap:10px;">
        <input type="text" name="q" class="form-control" placeholder="Tìm sản phẩm..." value="<?= sanitize($q) ?>" style="width:220px">
        <select name="cat" class="form-control" style="width:180px">
            <option value="">-- Tất cả loại --</option>
            <?php foreach ($cats as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $cat_id==$c['id']?'selected':'' ?>><?= sanitize($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">🔍 Lọc</button>
        <?php if ($q||$cat_id): ?><a href="/shop/admin/pricing.php" class="btn btn-secondary">✕</a><?php endif; ?>
    </form>
</div>

<div class="alert alert-info">
    💡 <strong>Công thức:</strong> Giá bán = Giá vốn × (100% + Tỉ lệ lợi nhuận%) &nbsp;|&nbsp;
    Giá vốn được tính theo phương pháp bình quân khi nhập hàng
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Mã</th><th>Tên sản phẩm</th><th>Loại</th><th>Đơn vị</th><th>Giá vốn</th><th>Tỉ lệ LN (%)</th><th>Giá bán hiện tại</th><th>Cập nhật</th></tr></thead>
            <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td style="font-size:12px"><?= sanitize($p['code']) ?></td>
                <td><strong><?= sanitize($p['name']) ?></strong></td>
                <td style="font-size:13px"><?= sanitize($p['cat_name']) ?></td>
                <td><?= sanitize($p['unit']) ?></td>
                <td style="font-weight:600"><?= formatPrice($p['cost_price']) ?></td>
                <td>
                    <form method="POST" style="display:flex;gap:6px;align-items:center;">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <input type="number" name="profit_rate" value="<?= $p['profit_rate'] ?>" min="0" max="1000" step="0.5"
                               style="width:80px;padding:6px 8px;border:2px solid var(--border);border-radius:6px;font-family:inherit;font-size:13px;">
                        <button type="submit" class="btn btn-primary btn-sm">💾</button>
                    </form>
                </td>
                <td style="font-weight:800;font-size:16px;color:var(--accent)"><?= formatPrice($p['sell_price']) ?></td>
                <td style="font-size:12px;color:var(--text-muted)">
                    <em>Dự kiến: <?= formatPrice(round($p['cost_price'] * (1 + $p['profit_rate']/100), 0)) ?></em>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
