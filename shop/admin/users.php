<?php
$page_title = 'Quản Lý Người Dùng';
require_once 'includes/header.php';

// Xử lý actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['uid'] ?? 0);

    if ($action === 'toggle_lock' && $uid) {
        $cur = $pdo->prepare("SELECT status FROM users WHERE id=? AND role='customer'");
        $cur->execute([$uid]); $cur = $cur->fetchColumn();
        $new = $cur === 'active' ? 'locked' : 'active';
        $pdo->prepare("UPDATE users SET status=? WHERE id=?")->execute([$new, $uid]);
        $_SESSION['flash'] = ['type'=>'success','msg'=> ($new==='locked'?'🔒 Đã khóa':'🔓 Đã mở khóa').' tài khoản!'];
    }

    if ($action === 'reset_password' && $uid) {
        $new_pass = password_hash('123456', PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password=? WHERE id=? AND role='customer'")->execute([$new_pass, $uid]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'🔑 Đã reset mật khẩu về <strong>123456</strong>!'];
    }

    if ($action === 'add_user') {
        $username = trim($_POST['username'] ?? '');
        $fullname = trim($_POST['fullname'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $address  = trim($_POST['address'] ?? '');

        if ($username && $fullname) {
            $check = $pdo->prepare("SELECT id FROM users WHERE username=?");
            $check->execute([$username]);
            if ($check->fetch()) {
                $_SESSION['flash'] = ['type'=>'danger','msg'=>'❌ Tên đăng nhập đã tồn tại!'];
            } else {
                $pdo->prepare("INSERT INTO users (username,password,fullname,email,phone,address,role) VALUES (?,?,?,?,?,?,'customer')")
                    ->execute([$username, password_hash('123456', PASSWORD_DEFAULT), $fullname, $email, $phone, $address]);
                $_SESSION['flash'] = ['type'=>'success','msg'=>'✅ Thêm tài khoản thành công! Mật khẩu mặc định: 123456'];
            }
        }
    }

    redirect('/shop/admin/users.php');
}

$q = trim($_GET['q'] ?? '');
$where = "WHERE role='customer'";
$params = [];
if ($q) { $where .= " AND (username LIKE ? OR fullname LIKE ? OR email LIKE ?)"; $params = ["%$q%","%$q%","%$q%"]; }

$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY id DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
    <form style="display:flex;gap:10px;">
        <input type="text" name="q" class="form-control" placeholder="Tìm theo tên, email..." value="<?= sanitize($q) ?>" style="width:280px">
        <button type="submit" class="btn btn-primary">🔍 Tìm</button>
        <?php if ($q): ?><a href="/shop/admin/users.php" class="btn btn-secondary">✕</a><?php endif; ?>
    </form>
    <button class="btn btn-accent" onclick="document.getElementById('modal-add').style.display='flex'">➕ Thêm tài khoản</button>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr>
                <th>#</th><th>Tên đăng nhập</th><th>Họ tên</th><th>Email</th><th>Điện thoại</th><th>Địa chỉ</th><th>Trạng thái</th><th>Ngày tạo</th><th>Thao tác</th>
            </tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><strong><?= sanitize($u['username']) ?></strong></td>
                <td><?= sanitize($u['fullname']) ?></td>
                <td><?= sanitize($u['email']) ?></td>
                <td><?= sanitize($u['phone']) ?></td>
                <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:12px;"><?= sanitize($u['address']) ?></td>
                <td><span class="badge <?= $u['status']==='active'?'badge-success':'badge-danger' ?>"><?= $u['status']==='active'?'Hoạt động':'Bị khóa' ?></span></td>
                <td style="font-size:12px;color:var(--text-muted)"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="toggle_lock">
                            <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn <?= $u['status']==='active'?'btn-danger':'btn-primary' ?> btn-sm"
                                onclick="return confirm('<?= $u['status']==='active'?'Khóa':'Mở khóa' ?> tài khoản này?')">
                                <?= $u['status']==='active'?'🔒':'🔓' ?>
                            </button>
                        </form>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="reset_password">
                            <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Reset mật khẩu về 123456?')">🔑 Reset</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
            <tr><td colspan="9" class="text-center" style="padding:30px;color:var(--text-muted)">Không có dữ liệu</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal thêm tài khoản -->
<div id="modal-add" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:16px;padding:32px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;margin-bottom:20px;">
            <h3 style="font-size:20px;font-weight:700;">➕ Thêm tài khoản khách hàng</h3>
            <button onclick="document.getElementById('modal-add').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_user">
            <div class="form-row">
                <div class="form-group">
                    <label>Tên đăng nhập *</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Họ tên *</label>
                    <input type="text" name="fullname" class="form-control" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="form-group">
                    <label>Điện thoại</label>
                    <input type="tel" name="phone" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label>Địa chỉ</label>
                <input type="text" name="address" class="form-control">
            </div>
            <div class="alert alert-info" style="font-size:13px;">💡 Mật khẩu mặc định: <strong>123456</strong></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('modal-add').style.display='none'" class="btn btn-secondary">Hủy</button>
                <button type="submit" class="btn btn-primary">✅ Thêm tài khoản</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
