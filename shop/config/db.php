<?php
// config/db.php - Kết nối database
define('DB_HOST', 'localhost');
define('DB_NAME', 'shop_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("<div style='padding:20px;background:#fee;border:1px solid red;margin:20px;border-radius:8px;font-family:Arial'>
        <b>❌ Lỗi kết nối Database!</b><br>
        Hãy đảm bảo XAMPP đang chạy MySQL và đã import file <code>shop_db.sql</code><br>
        <small>Chi tiết: " . $e->getMessage() . "</small>
    </div>");
}

// Cấu hình site
define('SITE_URL', 'http://localhost/shop');
define('UPLOAD_DIR', __DIR__ . '/../uploads/products/');
define('UPLOAD_URL', SITE_URL . '/uploads/products/');
define('LOW_STOCK_THRESHOLD', 10); // Ngưỡng cảnh báo hết hàng
