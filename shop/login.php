<?php
$page_title = 'Đăng Nhập';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'config/functions.php';

if (isLoggedIn()) redirect('/shop/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username=? AND role='customer'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'locked') {
                $error = 'Tài khoản đã bị khóa. Vui lòng liên hệ hỗ trợ!';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];
                $redirect = $_GET['redirect'] ?? '/shop/index.php';
                redirect($redirect);
            }
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
<title>Đăng nhập - FoodShop</title>
<link rel="stylesheet" href="/shop/assets/css/style.css">
<style>
body { background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 50%, #388e3c 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; }
.login-box { background:white; border-radius:20px; padding:48px 40px; width:100%; max-width:420px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
.login-logo { text-align:center; margin-bottom:32px; }
.login-logo .icon { font-size:56px; display:block; }
.login-logo h1 { font-size:28px; font-weight:800; color:var(--primary); }
.login-logo p { color:var(--text-muted); font-size:14px; }
.divider { text-align:center; margin:20px 0; color:var(--text-muted); font-size:13px; position:relative; }
.divider::before, .divider::after { content:''; position:absolute; top:50%; width:42%; border-top:1px solid var(--border); }
.divider::before { left:0; }
.divider::after { right:0; }
</style>
</head>
<body>
<div class="login-box">
    <div class="login-logo">
        <span class="icon">🌿</span>
        <h1>FoodShop</h1>
        <p>Đăng nhập tài khoản</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger">⚠️ <?= sanitize($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>👤 Tên đăng nhập</label>
            <input type="text" name="username" class="form-control" placeholder="Nhập tên đăng nhập" value="<?= sanitize($_POST['username'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>🔒 Mật khẩu</label>
            <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu" required>
        </div>
        <button type="submit" class="btn btn-primary btn-full btn-lg">Đăng nhập →</button>
    </form>

    <div class="divider">hoặc</div>
    <a href="/shop/register.php" class="btn btn-outline btn-full">📝 Tạo tài khoản mới</a>

    <div style="text-align:center; margin-top:20px; font-size:13px; color:var(--text-muted);">
        <p>Tài khoản demo: <strong>nguyen_a</strong> / <strong>123456</strong></p>
    </div>
</div>
</body>
</html>
