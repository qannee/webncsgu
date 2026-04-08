<?php
$page_title = 'Quản Lý Loại Sản Phẩm';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name   = trim($_POST['name'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $profit = (float)($_POST['profit_rate'] ?? 20);
        if ($name) {
            $slug = strtolower(preg_replace('/\s+/', '-', $name));
            $pdo->prepare("INSERT INTO categories (name, slug, description, profit_rate) VALUES (?,?,?,?)")
                ->execute([$name, $slug, $desc, $profit]);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'✅ Thêm loại sản phẩm thành công!'];
        }
    }

    if ($action === 'edit') {
        $id     = (int)$_POST['id'];
        $name   = trim($_POST['name'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $profit = (float)($_POST['profit_rate'] ?? 20);
        if ($id && $name) {
            $pdo->prepare("UPDATE categories SET name=?,description=?,profit_rate=? WHERE id=?")
                ->execute([$name, $desc, $profit, $id]);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'✅ Cập nhật thành công!'];
        }
    }

    if ($action === 'toggle') {
        $id  = (int)$_POST['id'];
        $cur = $pdo->prepare("SELECT status FROM categories WHERE id=?");
        $cur->execute([$id]); $cur = $cur->fetchColumn();
        $new = $cur === 'show' ? 'hide' : 'show';
        $pdo->prepare("UPDATE categories SET status=? WHERE id=?")->execute([$new, $id]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'✅ Cập nhật trạng thái!'];
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $count = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id=?");
        $count->execute([$id]);
        if ($count->fetchColumn() > 0) {
            $_SESSION['flash'] = ['type'=>'danger','msg'=>'❌ Không thể xóa! Loại này đang có sản phẩm.'];
        } else {
            $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'🗑️ Đã xóa loại sản phẩm!'];
        }
    }

    redirect('/shop/admin/categories.php');
}

$categories = $pdo->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id ORDER BY c.id DESC")->fetchAll();
?>

<div style="display:flex;justify-content:flex-end;margin-bottom:20px;">
    <button class="btn btn-accent" onclick="document.getElementById('modal-add').style.display='flex'">➕ Thêm loại sản phẩm</button>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr>
                <th>#</th><th>Tên loại</th><th>Mô tả</th><th>Tỉ lệ LN (%)</th><th>Số SP</th><th>Trạng thái</th><th>Thao tác</th>
            </tr></thead>
            <tbody>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?= $cat['id'] ?></td>
                <td><strong><?= sanitize($cat['name']) ?></strong></td>
                <td style="max-width:200px;font-size:13px;color:var(--text-muted)"><?= sanitize($cat['description']) ?></td>
                <td><span class="badge badge-info"><?= $cat['profit_rate'] ?>%</span></td>
                <td><?= $cat['product_count'] ?></td>
                <td><span class="badge <?= $cat['status']==='show'?'badge-success':'badge-secondary' ?>"><?= $cat['status']==='show'?'Hiển thị':'Ẩn' ?></span></td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <button class="btn btn-primary btn-sm" onclick="editCat(<?= htmlspecialchars(json_encode($cat)) ?>)">✏️ Sửa</button>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                            <button type="submit" class="btn btn-secondary btn-sm"><?= $cat['status']==='show'?'🙈 Ẩn':'👁️ Hiện' ?></button>
                        </form>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Xóa loại sản phẩm này?')">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Thêm -->
<div id="modal-add" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:16px;padding:32px;width:100%;max-width:440px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:20px;">
            <h3 style="font-size:20px;font-weight:700;">➕ Thêm loại sản phẩm</h3>
            <button onclick="document.getElementById('modal-add').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group"><label>Tên loại *</label><input type="text" name="name" class="form-control" required></div>
            <div class="form-group"><label>Mô tả</label><textarea name="description" class="form-control" rows="3"></textarea></div>
            <div class="form-group"><label>Tỉ lệ lợi nhuận mặc định (%)</label><input type="number" name="profit_rate" class="form-control" value="20" min="0" max="1000" step="0.5"></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('modal-add').style.display='none'" class="btn btn-secondary">Hủy</button>
                <button type="submit" class="btn btn-primary">✅ Lưu</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sửa -->
<div id="modal-edit" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:16px;padding:32px;width:100%;max-width:440px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:20px;">
            <h3 style="font-size:20px;font-weight:700;">✏️ Sửa loại sản phẩm</h3>
            <button onclick="document.getElementById('modal-edit').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group"><label>Tên loại *</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
            <div class="form-group"><label>Mô tả</label><textarea name="description" id="edit_desc" class="form-control" rows="3"></textarea></div>
            <div class="form-group"><label>Tỉ lệ lợi nhuận (%)</label><input type="number" name="profit_rate" id="edit_profit" class="form-control" step="0.5"></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('modal-edit').style.display='none'" class="btn btn-secondary">Hủy</button>
                <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCat(cat) {
    document.getElementById('edit_id').value = cat.id;
    document.getElementById('edit_name').value = cat.name;
    document.getElementById('edit_desc').value = cat.description || '';
    document.getElementById('edit_profit').value = cat.profit_rate;
    document.getElementById('modal-edit').style.display = 'flex';
}
</script>

<?php require_once 'includes/footer.php'; ?>
