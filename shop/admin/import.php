<?php
$page_title = 'Quản Lý Nhập Hàng';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $date = $_POST['import_date'] ?? date('Y-m-d');
        $note = trim($_POST['note'] ?? '');
        $code = generateCode('PN');
        $pdo->prepare("INSERT INTO import_receipts (code, import_date, note, admin_id) VALUES (?,?,?,?)")
            ->execute([$code, $date, $note, $_SESSION['user_id']]);
        $rid = $pdo->lastInsertId();
        redirect('/shop/admin/import.php?edit=' . $rid);
    }

    if ($action === 'add_item') {
        $rid    = (int)$_POST['receipt_id'];
        $pid    = (int)$_POST['product_id'];
        $qty    = (int)$_POST['quantity'];
        $price  = (float)$_POST['import_price'];

        // Check receipt is draft
        $r = $pdo->prepare("SELECT status FROM import_receipts WHERE id=?");
        $r->execute([$rid]); $r = $r->fetchColumn();
        if ($r === 'draft' && $pid && $qty > 0 && $price > 0) {
            // Upsert
            $existing = $pdo->prepare("SELECT id, quantity FROM import_details WHERE receipt_id=? AND product_id=?");
            $existing->execute([$rid, $pid]); $existing = $existing->fetch();
            if ($existing) {
                $pdo->prepare("UPDATE import_details SET quantity=?, import_price=? WHERE id=?")
                    ->execute([$qty, $price, $existing['id']]);
            } else {
                $pdo->prepare("INSERT INTO import_details (receipt_id, product_id, quantity, import_price) VALUES (?,?,?,?)")
                    ->execute([$rid, $pid, $qty, $price]);
            }
            $_SESSION['flash'] = ['type'=>'success','msg'=>'✅ Đã thêm/cập nhật sản phẩm vào phiếu!'];
        }
        redirect('/shop/admin/import.php?edit=' . $rid);
    }

    if ($action === 'remove_item') {
        $rid  = (int)$_POST['receipt_id'];
        $did  = (int)$_POST['detail_id'];
        $pdo->prepare("DELETE FROM import_details WHERE id=? AND receipt_id=?")->execute([$did, $rid]);
        redirect('/shop/admin/import.php?edit=' . $rid);
    }

    if ($action === 'complete') {
        $rid = (int)$_POST['receipt_id'];
        $r   = $pdo->prepare("SELECT * FROM import_receipts WHERE id=? AND status='draft'");
        $r->execute([$rid]); $r = $r->fetch();

        if ($r) {
            $details = $pdo->prepare("SELECT * FROM import_details WHERE receipt_id=?");
            $details->execute([$rid]); $details = $details->fetchAll();

            if (empty($details)) {
                $_SESSION['flash'] = ['type'=>'danger','msg'=>'❌ Phiếu nhập chưa có sản phẩm!'];
                redirect('/shop/admin/import.php?edit=' . $rid);
            }

            $pdo->beginTransaction();
            foreach ($details as $d) {
                // Tính giá vốn bình quân
                $prod = $pdo->prepare("SELECT stock, cost_price FROM products WHERE id=?");
                $prod->execute([$d['product_id']]); $prod = $prod->fetch();

                $new_stock = $prod['stock'] + $d['quantity'];
                $new_cost  = $new_stock > 0
                    ? ($prod['stock'] * $prod['cost_price'] + $d['quantity'] * $d['import_price']) / $new_stock
                    : $d['import_price'];

                // Cập nhật stock và giá vốn
                $profit_stmt = $pdo->prepare("SELECT profit_rate FROM products WHERE id=?");
                $profit_stmt->execute([$d['product_id']]);
                $profit_rate = (float)($profit_stmt->fetchColumn() ?: 20);
                $new_sell = round($new_cost * (1 + $profit_rate / 100), 0);

                $pdo->prepare("UPDATE products SET stock=?, cost_price=?, sell_price=? WHERE id=?")
                    ->execute([$new_stock, round($new_cost, 2), $new_sell, $d['product_id']]);
            }
            $pdo->prepare("UPDATE import_receipts SET status='completed' WHERE id=?")->execute([$rid]);
            $pdo->commit();
            $_SESSION['flash'] = ['type'=>'success','msg'=>'✅ Hoàn thành phiếu nhập! Đã cập nhật tồn kho và giá vốn.'];
        }
        redirect('/shop/admin/import.php');
    }

    if ($action === 'delete_receipt') {
        $rid = (int)$_POST['receipt_id'];
        $pdo->prepare("DELETE FROM import_details WHERE receipt_id=?")->execute([$rid]);
        $pdo->prepare("DELETE FROM import_receipts WHERE id=? AND status='draft'")->execute([$rid]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'🗑️ Đã xóa phiếu nhập!'];
        redirect('/shop/admin/import.php');
    }
}

// Edit view
$edit_id = (int)($_GET['edit'] ?? 0);
$edit_receipt = null;
if ($edit_id) {
    $s = $pdo->prepare("SELECT * FROM import_receipts WHERE id=?");
    $s->execute([$edit_id]); $edit_receipt = $s->fetch();
}

// List
$receipts = $pdo->query("SELECT r.*, COUNT(d.id) as item_count, SUM(d.quantity * d.import_price) as total_value
    FROM import_receipts r LEFT JOIN import_details d ON d.receipt_id=r.id
    GROUP BY r.id ORDER BY r.id DESC")->fetchAll();

$all_products = $pdo->query("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id=c.id ORDER BY c.name, p.name")->fetchAll();
?>

<?php if ($edit_receipt): ?>
<!-- Edit Receipt View -->
<?php
$details = $pdo->prepare("SELECT d.*, p.name, p.unit, p.cost_price FROM import_details d JOIN products p ON d.product_id=p.id WHERE d.receipt_id=?");
$details->execute([$edit_id]); $details = $details->fetchAll();
?>
<div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;">
    <a href="/shop/admin/import.php" class="btn btn-secondary">← Quay lại</a>
    <h2 style="font-size:20px;font-weight:700;">
        📥 Phiếu nhập: <?= $edit_receipt['code'] ?>
        <span class="badge <?= $edit_receipt['status']==='draft'?'badge-warning':'badge-success' ?>" style="margin-left:8px;">
            <?= $edit_receipt['status']==='draft'?'Nháp':'Đã hoàn thành' ?>
        </span>
    </h2>
</div>

<?php if ($edit_receipt['status'] === 'draft'): ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
    <!-- Thêm sản phẩm -->
    <div class="card">
        <div class="card-header"><h3>➕ Thêm sản phẩm vào phiếu</h3></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="add_item">
                <input type="hidden" name="receipt_id" value="<?= $edit_id ?>">
                <div class="form-group">
                    <label>Sản phẩm</label>
                    <select name="product_id" class="form-control" required id="imp_product" onchange="fillCost()">
                        <option value="">-- Chọn sản phẩm --</option>
                        <?php foreach ($all_products as $p): ?>
                        <option value="<?= $p['id'] ?>" data-cost="<?= $p['cost_price'] ?>"><?= sanitize($p['cat_name'].' / '.$p['name'].' ('.$p['unit'].')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Số lượng nhập</label><input type="number" name="quantity" class="form-control" value="1" min="1" required></div>
                    <div class="form-group"><label>Giá nhập (₫/<?= 'đơn vị' ?>)</label><input type="number" name="import_price" id="imp_price" class="form-control" value="0" min="0" required></div>
                </div>
                <button type="submit" class="btn btn-primary btn-full">➕ Thêm vào phiếu</button>
            </form>
        </div>
    </div>

    <!-- Thông tin phiếu -->
    <div class="card">
        <div class="card-header"><h3>📋 Thông tin phiếu nhập</h3></div>
        <div class="card-body">
            <div class="form-group"><label>Mã phiếu</label><input class="form-control" value="<?= $edit_receipt['code'] ?>" disabled></div>
            <div class="form-group"><label>Ngày nhập</label><input class="form-control" value="<?= $edit_receipt['import_date'] ?>" disabled></div>
            <div class="form-group"><label>Ghi chú</label><textarea class="form-control" disabled><?= sanitize($edit_receipt['note']) ?></textarea></div>
            <?php if (!empty($details)): ?>
            <form method="POST">
                <input type="hidden" name="action" value="complete">
                <input type="hidden" name="receipt_id" value="<?= $edit_id ?>">
                <button type="submit" class="btn btn-accent btn-full" onclick="return confirm('Hoàn thành phiếu nhập? Không thể sửa sau khi hoàn thành!')">
                    ✅ Hoàn thành phiếu nhập
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Chi tiết phiếu -->
<div class="card">
    <div class="card-header"><h3>📦 Danh sách sản phẩm trong phiếu</h3></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Sản phẩm</th><th>Đơn vị</th><th>Giá vốn cũ</th><th>SL nhập</th><th>Giá nhập</th><th>Thành tiền</th><?= $edit_receipt['status']==='draft'?'<th></th>':'' ?></tr></thead>
            <tbody>
            <?php foreach ($details as $d): ?>
            <tr>
                <td><strong><?= sanitize($d['name']) ?></strong></td>
                <td><?= sanitize($d['unit']) ?></td>
                <td><?= formatPrice($d['cost_price']) ?></td>
                <td><?= $d['quantity'] ?></td>
                <td><?= formatPrice($d['import_price']) ?></td>
                <td><strong><?= formatPrice($d['quantity'] * $d['import_price']) ?></strong></td>
                <?php if ($edit_receipt['status']==='draft'): ?>
                <td>
                    <form method="POST">
                        <input type="hidden" name="action" value="remove_item">
                        <input type="hidden" name="receipt_id" value="<?= $edit_id ?>">
                        <input type="hidden" name="detail_id" value="<?= $d['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Xóa sản phẩm này khỏi phiếu?')">🗑</button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($details)): ?>
            <tr><td colspan="7" class="text-center" style="padding:20px;color:var(--text-muted)">Chưa có sản phẩm trong phiếu</td></tr>
            <?php endif; ?>
            </tbody>
            <?php if (!empty($details)): ?>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align:right;font-weight:700;">Tổng giá trị nhập:</td>
                    <td style="font-weight:800;font-size:18px;color:var(--accent);">
                        <?= formatPrice(array_sum(array_map(fn($d) => $d['quantity'] * $d['import_price'], $details))) ?>
                    </td>
                    <?= $edit_receipt['status']==='draft'?'<td></td>':'' ?>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<script>
const prodData = <?= json_encode(array_column($all_products, 'cost_price', 'id')) ?>;
function fillCost() {
    const sel = document.getElementById('imp_product');
    const pid = sel.value;
    if (pid && prodData[pid] !== undefined) {
        document.getElementById('imp_price').value = prodData[pid];
    }
}
</script>

<?php else: ?>
<!-- List Receipts -->
<div style="display:flex;justify-content:flex-end;margin-bottom:20px;">
    <button class="btn btn-accent" onclick="document.getElementById('modal-create').style.display='flex'">➕ Tạo phiếu nhập mới</button>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Mã phiếu</th><th>Ngày nhập</th><th>Số SP</th><th>Tổng giá trị</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php foreach ($receipts as $r): ?>
            <tr>
                <td><strong><?= $r['code'] ?></strong></td>
                <td><?= date('d/m/Y', strtotime($r['import_date'])) ?></td>
                <td><?= $r['item_count'] ?></td>
                <td style="font-weight:700"><?= formatPrice($r['total_value'] ?? 0) ?></td>
                <td><span class="badge <?= $r['status']==='draft'?'badge-warning':'badge-success' ?>"><?= $r['status']==='draft'?'Nháp':'Hoàn thành' ?></span></td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <a href="/shop/admin/import.php?edit=<?= $r['id'] ?>" class="btn btn-primary btn-sm">
                            <?= $r['status']==='draft'?'✏️ Sửa':'👁️ Xem' ?>
                        </a>
                        <?php if ($r['status']==='draft'): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="delete_receipt">
                            <input type="hidden" name="receipt_id" value="<?= $r['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Xóa phiếu nhập này?')">🗑</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal tạo phiếu -->
<div id="modal-create" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:16px;padding:32px;width:100%;max-width:440px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:20px;">
            <h3 style="font-size:20px;font-weight:700;">📥 Tạo phiếu nhập mới</h3>
            <button onclick="document.getElementById('modal-create').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-group"><label>Ngày nhập</label><input type="date" name="import_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
            <div class="form-group"><label>Ghi chú</label><textarea name="note" class="form-control"></textarea></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('modal-create').style.display='none'" class="btn btn-secondary">Hủy</button>
                <button type="submit" class="btn btn-accent">✅ Tạo phiếu</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>