<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';
require_once '../config/functions.php';

if (isAdmin()) redirect('/shop/admin/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username=? AND role='admin'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role']     = $user['role'];
            redirect('/shop/admin/index.php');
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login - FoodShop</title>
<link rel="stylesheet" href="/shop/assets/css/style.css">
<style>
body { background: linear-gradient(135deg, #1b5e20, #2e7d32); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; }
.login-box { background:white; border-radius:20px; padding:48px 40px; width:100%; max-width:400px; box-shadow:0 20px 60px rgba(0,0,0,0.3); }
</style>
</head>
<body>
<div class="login-box">
    <div style="text-align:center; margin-bottom:32px;">
        <span style="font-size:56px">🛡️</span>
        <h1 style="font-size:26px; font-weight:800; color:var(--primary-dark); margin:8px 0 4px;">Admin Panel</h1>
        <p style="color:var(--text-muted); font-size:14px;">FoodShop - Quản trị hệ thống</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger">⚠️ <?= sanitize($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>👤 Tên đăng nhập</label>
            <input type="text" name="username" class="form-control" placeholder="admin" value="<?= sanitize($_POST['username'] ?? '') ?>" required autofocus>
        </div>
        <div class="form-group">
            <label>🔒 Mật khẩu</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-primary btn-full btn-lg">🔓 Đăng nhập</button>
    </form>

    <div style="text-align:center; margin-top:20px; font-size:13px; color:var(--text-muted);">
        <p>Demo: <strong>admin</strong> / <strong>password</strong></p>
        <a href="/shop/admin/register.php" style="color:var(--primary);">đăng ký</a>
    </div>
</div>
</body>
</html>
