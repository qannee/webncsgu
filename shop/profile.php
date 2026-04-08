<?php
$page_title = 'Thông Tin Cá Nhân';
require_once 'includes/header.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?"); $stmt->execute([$user_id]);
$user = $stmt->fetch();

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'info';

    if ($action === 'info') {
        $fullname = trim($_POST['fullname'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $ward     = trim($_POST['ward'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $city     = trim($_POST['city'] ?? '');

        if (!$fullname || !$address || !$ward || !$district || !$city) {
            $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc!';
        } elseif ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email không hợp lệ!';
        } else {
            $pdo->prepare("UPDATE users SET fullname=?,email=?,phone=?,address=?,ward=?,district=?,city=? WHERE id=?")
                ->execute([$fullname,$email,$phone,$address,$ward,$district,$city,$user_id]);
            $_SESSION['fullname'] = $fullname;
            $success = 'Cập nhật thông tin thành công!';
            $stmt->execute([$user_id]); $user = $stmt->fetch();
        }

    } elseif ($action === 'password') {
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$old || !$new || !$confirm) {
            $error = 'Vui lòng nhập đầy đủ!';
        } elseif (!password_verify($old, $user['password'])) {
            $error = 'Mật khẩu hiện tại không đúng!';
        } elseif (strlen($new) < 6) {
            $error = 'Mật khẩu mới phải từ 6 ký tự!';
        } elseif ($new !== $confirm) {
            $error = 'Mật khẩu mới không khớp!';
        } else {
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new, PASSWORD_DEFAULT), $user_id]);
            $success = 'Đổi mật khẩu thành công!';
        }
    }
}
?>

<div class="breadcrumb">
    <a href="/shop/index.php">🏠 Trang chủ</a>
    <span class="sep">›</span>
    <span>⚙️ Thông tin cá nhân</span>
</div>

<div style="padding:32px 40px; max-width:700px;">
    <h1 style="font-size:26px;font-weight:800;margin-bottom:24px;">⚙️ Thông tin cá nhân</h1>

    <?php if ($error): ?><div class="alert alert-danger">⚠️ <?= sanitize($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success">✅ <?= sanitize($success) ?></div><?php endif; ?>

    <!-- Cập nhật thông tin -->
    <div class="card mb-3">
        <div class="card-header"><h3>👤 Thông tin cơ bản</h3></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="info">
                <div class="form-row">
                    <div class="form-group">
                        <label>Tên đăng nhập</label>
                        <input type="text" class="form-control" value="<?= sanitize($user['username']) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Họ tên đầy đủ *</label>
                        <input type="text" name="fullname" class="form-control" value="<?= sanitize($user['fullname']) ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= sanitize($user['email']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="tel" name="phone" class="form-control" value="<?= sanitize($user['phone']) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Địa chỉ *</label>
                    <input type="text" name="address" class="form-control" value="<?= sanitize($user['address']) ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Phường/Xã *</label>
                        <input type="text" name="ward" class="form-control" value="<?= sanitize($user['ward']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Quận/Huyện *</label>
                        <input type="text" name="district" class="form-control" value="<?= sanitize($user['district']) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Tỉnh/Thành phố *</label>
                    <input type="text" name="city" class="form-control" value="<?= sanitize($user['city']) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
            </form>
        </div>
    </div>

    <!-- Đổi mật khẩu -->
    <div class="card">
        <div class="card-header"><h3>🔒 Đổi mật khẩu</h3></div>
        <div class="card-body">
            <form method="POST" style="max-width:400px;">
                <input type="hidden" name="action" value="password">
                <div class="form-group">
                    <label>Mật khẩu hiện tại</label>
                    <input type="password" name="old_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Mật khẩu mới (≥6 ký tự)</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Nhập lại mật khẩu mới</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-accent">🔑 Đổi mật khẩu</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
