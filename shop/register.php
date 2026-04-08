<?php
$page_title = 'Đăng Ký';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'config/functions.php';

if (isLoggedIn()) redirect('/shop/index.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => trim($_POST['username'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm'  => $_POST['confirm'] ?? '',
        'fullname' => trim($_POST['fullname'] ?? ''),
        'email'    => trim($_POST['email'] ?? ''),
        'phone'    => trim($_POST['phone'] ?? ''),
        'address'  => trim($_POST['address'] ?? ''),
        'ward'     => trim($_POST['ward'] ?? ''),
        'district' => trim($_POST['district'] ?? ''),
        'city'     => trim($_POST['city'] ?? ''),
    ];

    if (!$data['username'] || !$data['password'] || !$data['fullname'] || !$data['address'] || !$data['ward'] || !$data['district'] || !$data['city']) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc!';
    } elseif (strlen($data['username']) < 4) {
        $error = 'Tên đăng nhập phải từ 4 ký tự!';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
        $error = 'Tên đăng nhập chỉ gồm chữ, số, dấu gạch dưới!';
    } elseif (strlen($data['password']) < 6) {
        $error = 'Mật khẩu phải từ 6 ký tự!';
    } elseif ($data['password'] !== $data['confirm']) {
        $error = 'Mật khẩu nhập lại không khớp!';
    } elseif ($data['email'] && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ!';
    } else {
        // Check username exists
        $check = $pdo->prepare("SELECT id FROM users WHERE username=?");
        $check->execute([$data['username']]);
        if ($check->fetch()) {
            $error = 'Tên đăng nhập đã được sử dụng!';
        } elseif ($data['email']) {
            $check2 = $pdo->prepare("SELECT id FROM users WHERE email=?");
            $check2->execute([$data['email']]);
            if ($check2->fetch()) $error = 'Email đã được sử dụng!';
        }

        if (!$error) {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, email, phone, address, ward, district, city) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $data['username'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['fullname'], $data['email'], $data['phone'],
                $data['address'], $data['ward'], $data['district'], $data['city']
            ]);
            $success = 'Đăng ký thành công! <a href="/shop/login.php">Đăng nhập ngay</a>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Đăng ký - FoodShop</title>
<link rel="stylesheet" href="/shop/assets/css/style.css">
<style>
body { background: linear-gradient(135deg, #1b5e20, #388e3c); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:30px 20px; }
.register-box { background:white; border-radius:20px; padding:40px; width:100%; max-width:560px; box-shadow:0 20px 60px rgba(0,0,0,0.2); }
.register-logo { text-align:center; margin-bottom:28px; }
</style>
</head>
<body>
<div class="register-box">
    <div class="register-logo">
        <span style="font-size:48px">🌿</span>
        <h1 style="font-size:24px; font-weight:800; color:var(--primary); margin:6px 0 4px;">Tạo tài khoản</h1>
        <p style="color:var(--text-muted); font-size:13px;">Đăng ký để mua sắm tiện lợi hơn</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger">⚠️ <?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success">✅ <?= $success ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label>👤 Tên đăng nhập *</label>
                <input type="text" name="username" class="form-control" value="<?= sanitize($_POST['username'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>📛 Họ tên đầy đủ *</label>
                <input type="text" name="fullname" class="form-control" value="<?= sanitize($_POST['fullname'] ?? '') ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>🔒 Mật khẩu * (≥6 ký tự)</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>🔒 Nhập lại mật khẩu *</label>
                <input type="password" name="confirm" class="form-control" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>📧 Email</label>
                <input type="email" name="email" class="form-control" value="<?= sanitize($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>📱 Số điện thoại</label>
                <input type="tel" name="phone" class="form-control" value="<?= sanitize($_POST['phone'] ?? '') ?>">
            </div>
        </div>

        <h4 style="font-size:14px;font-weight:700;color:var(--primary);margin:16px 0 12px;">📍 Địa chỉ giao hàng mặc định</h4>
        <div class="form-group">
            <label>Số nhà, tên đường *</label>
            <input type="text" name="address" class="form-control" value="<?= sanitize($_POST['address'] ?? '') ?>" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Phường/Xã *</label>
                <input type="text" name="ward" class="form-control" value="<?= sanitize($_POST['ward'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Quận/Huyện *</label>
                <input type="text" name="district" class="form-control" value="<?= sanitize($_POST['district'] ?? '') ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label>Tỉnh/Thành phố *</label>
            <input type="text" name="city" class="form-control" value="<?= sanitize($_POST['city'] ?? '') ?>" required>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg">📝 Đăng ký ngay</button>
    </form>
    <?php endif; ?>

    <div style="text-align:center;margin-top:20px;font-size:14px;">
        Đã có tài khoản? <a href="/shop/login.php" style="color:var(--primary);font-weight:600;">Đăng nhập</a>
    </div>
</div>
</body>
</html>
