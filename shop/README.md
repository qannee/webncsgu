# 🌿 FoodShop - Web Bán Thực Phẩm Sạch

## 📋 Yêu cầu hệ thống
- XAMPP (PHP 7.4+ và MySQL 5.7+)
- Trình duyệt: Chrome / Firefox

---

## 🚀 Hướng dẫn cài đặt

### Bước 1: Copy project vào XAMPP
Giải nén và copy thư mục `shop` vào:
```
C:\xampp\htdocs\shop
```

### Bước 2: Khởi động XAMPP
Mở XAMPP Control Panel → Start **Apache** và **MySQL**

### Bước 3: Import Database
1. Mở trình duyệt → truy cập: `http://localhost/phpmyadmin`
2. Tạo database mới tên: `shop_db`
3. Chọn database `shop_db` → tab **Import**
4. Chọn file `shop_db.sql` trong thư mục gốc → nhấn **Go**

### Bước 4: Chạy website
- **Trang khách hàng:** `http://localhost/shop`
- **Trang admin:** `http://localhost/shop/admin/login.php`

---

## 🔑 Tài khoản mặc định

| Loại | Username | Password |
|------|----------|----------|
| Admin | `admin` | `password` |
| Khách hàng | `nguyen_a` | `123456` |
| Khách hàng | `tran_b` | `123456` |

> ⚠️ **Lưu ý:** Đổi mật khẩu admin sau khi cài đặt!
> Password admin trong DB được hash bằng `password_hash('password', PASSWORD_DEFAULT)`
> Nếu cần đổi, chạy: `UPDATE users SET password='[hash]' WHERE username='admin'`

---

## 📁 Cấu trúc thư mục

```
shop/
├── admin/                  # Trang quản trị
│   ├── includes/           # Header/footer admin
│   ├── index.php           # Dashboard
│   ├── users.php           # Quản lý người dùng
│   ├── categories.php      # Quản lý loại SP
│   ├── products.php        # Quản lý sản phẩm
│   ├── import.php          # Nhập hàng
│   ├── pricing.php         # Quản lý giá
│   ├── orders.php          # Đơn hàng
│   ├── inventory.php       # Tồn kho
│   └── login.php           # Đăng nhập admin
├── assets/
│   ├── css/style.css       # CSS chính
│   └── js/main.js          # JavaScript
├── config/
│   ├── db.php              # Kết nối DB
│   └── functions.php       # Hàm tiện ích
├── includes/
│   ├── header.php          # Header chung
│   └── footer.php          # Footer chung
├── uploads/products/       # Ảnh sản phẩm upload
├── index.php               # Trang chủ
├── products.php            # Danh sách SP + tìm kiếm
├── product.php             # Chi tiết SP
├── cart.php                # Giỏ hàng
├── cart_action.php         # Xử lý giỏ hàng
├── checkout.php            # Đặt hàng
├── orders.php              # Lịch sử đơn hàng
├── profile.php             # Thông tin cá nhân
├── login.php               # Đăng nhập KH
├── register.php            # Đăng ký KH
├── logout.php              # Đăng xuất
└── shop_db.sql             # File SQL database
```

---

## ✅ Tính năng đã hoàn thành

### 👤 Khách hàng
- [x] Đăng ký / Đăng nhập / Đăng xuất
- [x] Xem / Sửa thông tin cá nhân + đổi mật khẩu
- [x] Hiển thị sản phẩm theo phân loại (có phân trang)
- [x] Xem chi tiết sản phẩm
- [x] Tìm kiếm cơ bản (theo tên)
- [x] Tìm kiếm nâng cao (tên + loại + khoảng giá)
- [x] Giỏ hàng: thêm, sửa số lượng, xóa
- [x] Đặt hàng: chọn địa chỉ từ TK hoặc nhập mới
- [x] Thanh toán: COD / Chuyển khoản / Trực tuyến
- [x] Xem tóm tắt đơn sau khi đặt
- [x] Xem lịch sử đơn hàng

### 🛡️ Admin
- [x] Đăng nhập riêng (URL khác khách hàng)
- [x] Dashboard thống kê
- [x] Quản lý người dùng: thêm, khóa/mở, reset mật khẩu
- [x] Quản lý loại sản phẩm: thêm, sửa, ẩn/hiện, xóa
- [x] Quản lý sản phẩm: thêm, sửa, ẩn/xóa, upload ảnh
- [x] Nhập hàng: tạo phiếu, thêm SP, hoàn thành phiếu
- [x] Giá vốn bình quân tự động tính khi hoàn thành phiếu nhập
- [x] Quản lý giá bán: cập nhật % lợi nhuận từng SP
- [x] Quản lý đơn hàng: lọc theo thời gian, tình trạng, phường
- [x] Xem chi tiết + cập nhật trạng thái đơn
- [x] Tồn kho: tra cứu, cảnh báo hết hàng
- [x] Báo cáo nhập-xuất theo kỳ

---

## 🔒 Bảo mật
- Mật khẩu mã hóa `password_hash()`
- Chống SQL Injection bằng PDO Prepared Statements
- Chống XSS bằng `htmlspecialchars()`
- Kiểm tra session trước mọi trang cần đăng nhập
- Admin và khách hàng có session riêng biệt

---

## 📞 Hỗ trợ
Nếu gặp lỗi kết nối DB, kiểm tra file `config/db.php` và đảm bảo:
- MySQL đang chạy trong XAMPP
- Database `shop_db` đã được tạo và import SQL
- Username/password MySQL đúng (mặc định: root / không có mật khẩu)
