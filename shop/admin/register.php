<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

// Nếu đã đăng nhập với quyền admin thì chuyển về dashboard
if (isAdmin()) redirect('/shop/admin/index.php');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    // ---- Validation ----
    if (!$username || !$fullname || !$password || !$confirm) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc!';
    } elseif (strlen($username) < 3) {
        $error = 'Tên đăng nhập phải từ 3 ký tự trở lên!';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Tên đăng nhập chỉ gồm chữ cái, số và dấu gạch dưới!';
    } elseif (strlen($password) <= 6) {
        $error = 'Mật khẩu phải từ 6 ký tự trở lên!';
    } elseif ($password !== $confirm) {
        $error = 'Mật khẩu nhập lại không khớp!';
    } elseif ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Địa chỉ email không hợp lệ!';
    } else {
        // Kiểm tra username đã tồn tại chưa
        $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $chk->execute([$username]);
        if ($chk->fetch()) {
            $error = 'Tên đăng nhập đã tồn tại, vui lòng chọn tên khác!';
        } elseif ($email) {
            $chk2 = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $chk2->execute([$email]);
            if ($chk2->fetch()) {
                $error = 'Email này đã được sử dụng!';
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare(
                "INSERT INTO users (username, password, fullname, email, phone, role, status, created_at)
                 VALUES (?, ?, ?, ?, ?, 'admin', 'active', NOW())"
            );
            $stmt->execute([
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $fullname,
                $email,
                $phone,
            ]);
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng Ký Admin - FoodShop</title>
<link rel="stylesheet" href="/shop/assets/css/style.css">
<style>
    body {
        background: linear-gradient(135deg, #1b5e20, #2e7d32, #388e3c);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 30px 20px;
        font-family: inherit;
    }

    .register-box {
        background: #fff;
        border-radius: 20px;
        padding: 44px 40px;
        width: 100%;
        max-width: 520px;
        box-shadow: 0 24px 64px rgba(0,0,0,0.25);
    }

    .register-logo {
        text-align: center;
        margin-bottom: 28px;
    }

    .register-logo .icon  { font-size: 52px; display: block; }
    .register-logo h1     { font-size: 24px; font-weight: 800; color: var(--primary-dark); margin: 8px 0 4px; }
    .register-logo p      { color: var(--text-muted); font-size: 13px; margin: 0; }

    .divider {
        text-align: center;
        font-size: 12px;
        color: var(--text-muted);
        margin: 18px 0 14px;
        position: relative;
    }
    .divider::before, .divider::after {
        content: '';
        position: absolute;
        top: 50%;
        width: 38%;
        height: 1px;
        background: var(--border);
    }
    .divider::before { left: 0; }
    .divider::after  { right: 0; }

    .success-card {
        text-align: center;
        padding: 16px 0 8px;
    }
    .success-card .big-icon { font-size: 64px; margin-bottom: 12px; display: block; }
    .success-card h2  { font-size: 22px; font-weight: 800; color: var(--primary-dark); margin-bottom: 8px; }
    .success-card p   { color: var(--text-muted); font-size: 14px; margin-bottom: 24px; }

    .hint-box {
        background: #f1f8e9;
        border: 1px solid #c5e1a5;
        border-radius: 10px;
        padding: 12px 16px;
        font-size: 13px;
        color: #33691e;
        margin-bottom: 20px;
    }

    .password-wrap {
        position: relative;
    }
    .password-wrap input { padding-right: 44px; }
    .toggle-pw {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        font-size: 18px;
        background: none;
        border: none;
        padding: 0;
        line-height: 1;
    }
</style>
</head>
<body>

<div class="register-box">

    <!-- Logo -->
    <div class="register-logo">
        <span class="icon">🛡️</span>
        <h1>Đăng Ký Tài Khoản Admin</h1>
        <p>FoodShop &mdash; Hệ thống quản trị</p>
    </div>

    <?php if ($success): ?>
    <!-- ── Thành công ── -->
    <div class="success-card">
        <span class="big-icon">🎉</span>
        <h2>Đăng ký thành công!</h2>
        <p>Tài khoản admin của bạn đã được tạo.<br>Hãy đăng nhập để bắt đầu quản lý hệ thống.</p>
        <a href="/shop/admin/login.php" class="btn btn-primary btn-full btn-lg">🔓 Đăng nhập ngay</a>
    </div>

    <?php else: ?>
    <!-- ── Form ── -->

    <?php if ($error): ?>
    <div class="alert alert-danger">⚠️ <?= sanitize($error) ?></div>
    <?php endif; ?>

    <div class="hint-box">
        🔒 Chỉ nhân viên được cấp phép mới được đăng ký tài khoản admin.<br>
        Nhập <strong>Mã xác nhận</strong> do quản trị viên cung cấp.
    </div>

    <form method="POST" novalidate>

        <!-- Thông tin đăng nhập -->
        <div class="divider">Thông tin đăng nhập</div>

        <div class="form-row">
            <div class="form-group">
                <label>👤 Tên đăng nhập *</label>
                <input type="text" name="username" class="form-control"
                       placeholder="vd: admin_nam"
                       value="<?= sanitize($_POST['username'] ?? '') ?>"
                       required autofocus>
            </div>
            <div class="form-group">
                <label>📛 Họ và tên *</label>
                <input type="text" name="fullname" class="form-control"
                       placeholder="Nguyễn Văn Nam"
                       value="<?= sanitize($_POST['fullname'] ?? '') ?>"
                       required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>🔒 Mật khẩu * <small style="color:var(--text-muted)">(≥ 6 ký tự)</small></label>
                <div class="password-wrap">
                    <input type="password" name="password" id="pw1" class="form-control"
                           placeholder="••••••••" required>
                    <button type="button" class="toggle-pw" onclick="togglePw('pw1',this)">👁️</button>
                </div>
            </div>
            <div class="form-group">
                <label>🔒 Nhập lại mật khẩu *</label>
                <div class="password-wrap">
                    <input type="password" name="confirm" id="pw2" class="form-control"
                           placeholder="••••••••" required>
                    <button type="button" class="toggle-pw" onclick="togglePw('pw2',this)">👁️</button>
                </div>
            </div>
        </div>

        <!-- Thông tin liên hệ -->
        <div class="divider">Thông tin liên hệ <small>(tùy chọn)</small></div>

        <div class="form-row">
            <div class="form-group">
                <label>📧 Email</label>
                <input type="email" name="email" class="form-control"
                       placeholder="admin@example.com"
                       value="<?= sanitize($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>📱 Số điện thoại</label>
                <input type="tel" name="phone" class="form-control"
                       placeholder="09xxxxxxxx"
                       value="<?= sanitize($_POST['phone'] ?? '') ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:8px;">
            📝 Tạo tài khoản Admin
        </button>
    </form>

    <div style="text-align:center; margin-top:20px; font-size:14px;">
        Đã có tài khoản?
        <a href="/shop/admin/login.php" style="color:var(--primary); font-weight:600;">Đăng nhập</a>
    </div>

    <?php endif; ?>
</div>

<script>
function togglePw(id, btn) {
    const input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '🙈';
    } else {
        input.type = 'password';
        btn.textContent = '👁️';
    }
}

// Client-side password match feedback
document.querySelector('form') && document.querySelector('form').addEventListener('submit', function(e) {
    const pw  = document.getElementById('pw1');
    const pw2 = document.getElementById('pw2');
    if (pw && pw2 && pw.value !== pw2.value) {
        e.preventDefault();
        pw2.style.borderColor = 'var(--danger, red)';
        pw2.focus();
        alert('Mật khẩu nhập lại không khớp!');
    }
});
</script>

</body>
</html>