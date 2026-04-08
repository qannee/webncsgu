<?php
$page_title = 'Quản Lý Sản Phẩm';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $cat_id  = (int)$_POST['category_id'];
        $code    = trim($_POST['code'] ?? '');
        $name    = trim($_POST['name'] ?? '');
        $desc    = trim($_POST['description'] ?? '');
        $unit    = trim($_POST['unit'] ?? 'kg');
        $stock   = (int)$_POST['stock'];
        $cost    = (float)$_POST['cost_price'];
        $profit  = (float)$_POST['profit_rate'];
        $status  = $_POST['status'] ?? 'show';
        $sell    = round($cost * (1 + $profit / 100), 0);

        if ($code && $name && $cat_id) {
            $img = uploadImage($_FILES['image'] ?? null, UPLOAD_DIR);
            $pdo->prepare("INSERT INTO products (category_id,code,name,description,unit,image,cost_price,profit_rate,sell_price,stock,status) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$cat_id,$code,$name,$desc,$unit,$img,$cost,$profit,$sell,$stock,$status]);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'✅ Thêm sản phẩm thành công!'];
        } else {
            $_SESSION['flash'] = ['type'=>'danger','msg'=>'❌ Vui lòng nhập đầy đủ thông tin bắt buộc!'];
        }
    }

    if ($action === 'edit') {
        $id      = (int)$_POST['id'];
        $cat_id  = (int)$_POST['category_id'];
        $code    = trim($_POST['code'] ?? '');
        $name    = trim($_POST['name'] ?? '');
        $desc    = trim($_POST['description'] ?? '');
        $unit    = trim($_POST['unit'] ?? 'kg');
        $profit  = (float)$_POST['profit_rate'];
        $status  = $_POST['status'] ?? 'show';

        // Get current cost price
        $cur = $pdo->prepare("SELECT cost_price, image FROM products WHERE id=?");
        $cur->execute([$id]); $cur = $cur->fetch();
        $sell = round($cur['cost_price'] * (1 + $profit / 100), 0);

        $img = $cur['image'];
        if (!empty($_FILES['image']['name'])) {
            $new_img = uploadImage($_FILES['image'] ?? null, UPLOAD_DIR);
            if ($new_img) {
                // Delete old
                if ($img && file_exists(UPLOAD_DIR . $img)) unlink(UPLOAD_DIR . $img);
                $img = $new_img;
            }
        }
        // Remove image
        if (isset($_POST['remove_image']) && $img) {
            if (file_exists(UPLOAD_DIR . $img)) unlink(UPLOAD_DIR . $img);
            $img = null;
        }

        $pdo->prepare("UPDATE products SET category_id=?,code=?,name=?,description=?,unit=?,image=?,profit_rate=?,sell_price=?,status=? WHERE id=?")
            ->execute([$cat_id,$code,$name,$desc,$unit,$img,$profit,$sell,$status,$id]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'✅ Cập nhật sản phẩm thành công!'];
    }

    if ($action === 'toggle') {
        $id  = (int)$_POST['id'];
        $cur = $pdo->prepare("SELECT status FROM products WHERE id=?");
        $cur->execute([$id]); $cur = $cur->fetchColumn();
        $new = $cur === 'show' ? 'hide' : 'show';
        $pdo->prepare("UPDATE products SET status=? WHERE id=?")->execute([$new, $id]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'✅ Cập nhật trạng thái!'];
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        // Check if product has been imported
        $has_import = $pdo->prepare("SELECT COUNT(*) FROM import_details WHERE product_id=?");
        $has_import->execute([$id]);
        if ($has_import->fetchColumn() > 0) {
            // Ẩn thay vì xóa
            $pdo->prepare("UPDATE products SET status='hide' WHERE id=?")->execute([$id]);
            $_SESSION['flash'] = ['type'=>'warning','msg'=>'⚠️ Sản phẩm đã được nhập hàng → đã ẩn thay vì xóa.'];
        } else {
            $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'🗑️ Đã xóa sản phẩm!'];
        }
    }

    redirect('/shop/admin/products.php');
}

$q      = trim($_GET['q'] ?? '');
$cat_id = (int)($_GET['cat'] ?? 0);
$where  = []; $params = [];
if ($q) { $where[] = "p.name LIKE ?"; $params[] = "%$q%"; }
if ($cat_id) { $where[] = "p.category_id = ?"; $params[] = $cat_id; }
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id=c.id $where_sql ORDER BY p.id DESC");
$stmt->execute($params);
$products = $stmt->fetchAll();
$all_cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <form style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <input type="text" name="q" class="form-control" placeholder="Tìm tên sản phẩm..." value="<?= sanitize($q) ?>" style="width:220px">
        <select name="cat" class="form-control" style="width:180px">
            <option value="">-- Tất cả loại --</option>
            <?php foreach ($all_cats as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $cat_id==$c['id']?'selected':'' ?>><?= sanitize($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">🔍 Lọc</button>
        <?php if ($q||$cat_id): ?><a href="/shop/admin/products.php" class="btn btn-secondary">✕</a><?php endif; ?>
    </form>
    <button class="btn btn-accent" onclick="document.getElementById('modal-add').style.display='flex'">➕ Thêm sản phẩm</button>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr>
                <th>Hình</th><th>Mã SP</th><th>Tên sản phẩm</th><th>Loại</th><th>Đơn vị</th><th>Giá vốn</th><th>LN%</th><th>Giá bán</th><th>Tồn</th><th>Trạng thái</th><th>Thao tác</th>
            </tr></thead>
            <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td>
                    <?php if ($p['image'] && file_exists(__DIR__.'/../uploads/products/'.$p['image'])): ?>
                        <img src="/shop/uploads/products/<?= $p['image'] ?>" alt="">
                    <?php else: ?>
                        <div style="width:50px;height:50px;background:var(--bg);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:20px;">🥦</div>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px;font-weight:600;"><?= sanitize($p['code']) ?></td>
                <td style="font-weight:600;max-width:160px"><?= sanitize($p['name']) ?></td>
                <td style="font-size:13px"><?= sanitize($p['cat_name']) ?></td>
                <td><?= sanitize($p['unit']) ?></td>
                <td><?= formatPrice($p['cost_price']) ?></td>
                <td><span class="badge badge-info"><?= $p['profit_rate'] ?>%</span></td>
                <td style="font-weight:700;color:var(--accent)"><?= formatPrice($p['sell_price']) ?></td>
                <td>
                    <span class="badge <?= $p['stock'] <= LOW_STOCK_THRESHOLD ? 'badge-danger' : 'badge-success' ?>">
                        <?= $p['stock'] ?>
                    </span>
                </td>
                <td><span class="badge <?= $p['status']==='show'?'badge-success':'badge-secondary' ?>"><?= $p['status']==='show'?'Đang bán':'Đã ẩn' ?></span></td>
                <td>
                    <div style="display:flex;gap:4px;">
                        <button class="btn btn-primary btn-sm" onclick='editProduct(<?= htmlspecialchars(json_encode($p)) ?>)'>✏️</button>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-secondary btn-sm"><?= $p['status']==='show'?'🙈':'👁️' ?></button>
                        </form>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Xóa sản phẩm này?')">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
            <tr><td colspan="11" class="text-center" style="padding:30px;color:var(--text-muted)">Không có sản phẩm</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Thêm -->
<div id="modal-add" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
<div style="background:white;border-radius:16px;padding:32px;width:100%;max-width:600px;max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;margin-bottom:20px;">
        <h3 style="font-size:20px;font-weight:700;">➕ Thêm sản phẩm mới</h3>
        <button onclick="document.getElementById('modal-add').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;">✕</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
            <div class="form-group">
                <label>Mã sản phẩm *</label>
                <input type="text" name="code" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Loại sản phẩm *</label>
                <select name="category_id" class="form-control" required>
                    <option value="">-- Chọn loại --</option>
                    <?php foreach ($all_cats as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group"><label>Tên sản phẩm *</label><input type="text" name="name" class="form-control" required></div>
        <div class="form-group"><label>Mô tả</label><textarea name="description" class="form-control"></textarea></div>
        <div class="form-row">
            <div class="form-group"><label>Đơn vị tính</label><input type="text" name="unit" class="form-control" value="kg"></div>
        </div>
        <div class="form-group">
            <label>Hình ảnh sản phẩm</label>
            <div id="add_drop_zone" onclick="document.getElementById('add_img_input').click()"
                style="border:2px dashed var(--border);border-radius:10px;padding:20px;text-align:center;cursor:pointer;transition:border-color .2s;background:var(--bg);"
                ondragover="event.preventDefault();this.style.borderColor='var(--primary)'"
                ondragleave="this.style.borderColor='var(--border)'"
                ondrop="handleDrop(event,'add_img_input','add_img_preview_wrap','add_drop_zone')">
                <div id="add_img_preview_wrap">
                    <div style="font-size:36px;margin-bottom:6px;">🖼️</div>
                    <div style="font-size:13px;color:var(--text-muted);">Nhấn để chọn ảnh hoặc kéo thả vào đây</div>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">JPG, PNG, WEBP — tối đa 5MB</div>
                </div>
            </div>
            <input type="file" id="add_img_input" name="image" accept="image/*" style="display:none" onchange="previewImg(this,'add_img_preview_wrap','add_drop_zone')">
        </div>
        <div class="form-group">
            <label>Trạng thái</label>
            <select name="status" class="form-control">
                <option value="show">Đang bán</option>
                <option value="hide">Ẩn</option>
            </select>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button type="button" onclick="document.getElementById('modal-add').style.display='none'" class="btn btn-secondary">Hủy</button>
            <button type="submit" class="btn btn-accent">✅ Thêm sản phẩm</button>
        </div>
    </form>
</div>
</div>

<!-- Modal Sửa -->
<div id="modal-edit" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
<div style="background:white;border-radius:16px;padding:32px;width:100%;max-width:600px;max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;margin-bottom:20px;">
        <h3 style="font-size:20px;font-weight:700;">✏️ Sửa sản phẩm</h3>
        <button onclick="document.getElementById('modal-edit').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;">✕</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="ep_id">
        <div class="form-row">
            <div class="form-group"><label>Mã sản phẩm *</label><input type="text" name="code" id="ep_code" class="form-control" required></div>
            <div class="form-group">
                <label>Loại sản phẩm *</label>
                <select name="category_id" id="ep_cat" class="form-control" required>
                    <?php foreach ($all_cats as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group"><label>Tên sản phẩm *</label><input type="text" name="name" id="ep_name" class="form-control" required></div>
        <div class="form-group"><label>Mô tả</label><textarea name="description" id="ep_desc" class="form-control"></textarea></div>
        <div class="form-row">
            <div class="form-group"><label>Đơn vị</label><input type="text" name="unit" id="ep_unit" class="form-control"></div>
            <div class="form-group"><label>Tỉ lệ lợi nhuận (%)</label><input type="number" name="profit_rate" id="ep_profit" class="form-control" min="0"></div>
        </div>
        <div class="form-group">
            <label>Hình ảnh sản phẩm</label>
            <div id="edit_drop_zone" onclick="document.getElementById('edit_img_input').click()"
                style="border:2px dashed var(--border);border-radius:10px;padding:16px;text-align:center;cursor:pointer;transition:border-color .2s;background:var(--bg);"
                ondragover="event.preventDefault();this.style.borderColor='var(--primary)'"
                ondragleave="this.style.borderColor='var(--border)'"
                ondrop="handleDrop(event,'edit_img_input','ep_img_preview_wrap','edit_drop_zone')">
                <div id="ep_img_preview_wrap">
                    <div id="ep_img_preview" style="margin-bottom:6px;"></div>
                    <div style="font-size:13px;color:var(--text-muted);">Nhấn để thay ảnh hoặc kéo thả vào đây</div>
                </div>
            </div>
            <input type="file" id="edit_img_input" name="image" accept="image/*" style="display:none" onchange="previewImg(this,'ep_img_preview_wrap','edit_drop_zone')">
            <label style="display:flex;gap:6px;align-items:center;margin-top:8px;font-weight:400;font-size:13px;cursor:pointer;">
                <input type="checkbox" name="remove_image" value="1"> 🗑️ Xóa ảnh hiện tại
            </label>
        </div>
        <div class="form-group">
            <label>Trạng thái</label>
            <select name="status" id="ep_status" class="form-control">
                <option value="show">Đang bán</option>
                <option value="hide">Ẩn</option>
            </select>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button type="button" onclick="document.getElementById('modal-edit').style.display='none'" class="btn btn-secondary">Hủy</button>
            <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
        </div>
    </form>
</div>
</div>

<script>
function previewImg(input, wrapId, zoneId) {
    const wrap = document.getElementById(wrapId);
    const zone = document.getElementById(zoneId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            wrap.innerHTML = `<img src="${e.target.result}" style="max-width:180px;max-height:140px;object-fit:cover;border-radius:8px;margin-bottom:6px;"><br><span style="font-size:12px;color:var(--text-muted);">${input.files[0].name}</span>`;
            if (zone) zone.style.borderColor = 'var(--primary)';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function handleDrop(event, inputId, wrapId, zoneId) {
    event.preventDefault();
    const input = document.getElementById(inputId);
    const zone  = document.getElementById(zoneId);
    if (zone) zone.style.borderColor = 'var(--border)';
    const file = event.dataTransfer.files[0];
    if (!file || !file.type.startsWith('image/')) return;
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    previewImg(input, wrapId, zoneId);
}

function editProduct(p) {
    document.getElementById('ep_id').value = p.id;
    document.getElementById('ep_code').value = p.code;
    document.getElementById('ep_cat').value = p.category_id;
    document.getElementById('ep_name').value = p.name;
    document.getElementById('ep_desc').value = p.description || '';
    document.getElementById('ep_unit').value = p.unit;
    document.getElementById('ep_profit').value = p.profit_rate;
    document.getElementById('ep_status').value = p.status;

    // Reset edit file input
    document.getElementById('edit_img_input').value = '';
    document.getElementById('edit_drop_zone').style.borderColor = 'var(--border)';

    const prev = document.getElementById('ep_img_preview');
    const wrap = document.getElementById('ep_img_preview_wrap');
    if (p.image) {
        prev.innerHTML = `<img src="/shop/uploads/products/${p.image}" style="max-width:160px;max-height:120px;object-fit:cover;border-radius:8px;margin-bottom:4px;">`;
        wrap.querySelector('div:last-child') && (wrap.querySelector('div:last-child').style.display='none');
    } else {
        prev.innerHTML = '';
        const hint = wrap.querySelector('div');
        if (hint) hint.style.display = '';
    }
    document.getElementById('modal-edit').style.display = 'flex';
}
</script>

<?php require_once 'includes/footer.php'; ?>