-- ============================================
-- DATABASE: shop_db (Web Bán Thực Phẩm)
-- ============================================
CREATE DATABASE IF NOT EXISTS shop_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shop_db;

-- Bảng người dùng
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(200) NOT NULL,
    email VARCHAR(200) UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    ward VARCHAR(100),
    district VARCHAR(100),
    city VARCHAR(100),
    role ENUM('admin','customer') DEFAULT 'customer',
    status ENUM('active','locked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng loại sản phẩm
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200),
    description TEXT,
    status ENUM('show','hide') DEFAULT 'show',
    profit_rate DECIMAL(5,2) DEFAULT 20.00
);

-- Bảng sản phẩm
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(300) NOT NULL,
    description TEXT,
    unit VARCHAR(50) DEFAULT 'kg',
    image VARCHAR(300),
    cost_price DECIMAL(12,2) DEFAULT 0,
    profit_rate DECIMAL(5,2) DEFAULT 20.00,
    sell_price DECIMAL(12,2) DEFAULT 0,
    stock INT DEFAULT 0,
    status ENUM('show','hide') DEFAULT 'show',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Bảng phiếu nhập hàng
CREATE TABLE import_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    import_date DATE NOT NULL,
    note TEXT,
    status ENUM('draft','completed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    admin_id INT,
    FOREIGN KEY (admin_id) REFERENCES users(id)
);

-- Chi tiết phiếu nhập
CREATE TABLE import_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    import_price DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (receipt_id) REFERENCES import_receipts(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Bảng đơn hàng
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_code VARCHAR(50) UNIQUE NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    ship_name VARCHAR(200),
    ship_phone VARCHAR(20),
    ship_address TEXT,
    ship_ward VARCHAR(100),
    ship_district VARCHAR(100),
    ship_city VARCHAR(100),
    payment_method ENUM('cash','transfer','online') DEFAULT 'cash',
    status ENUM('pending','confirmed','shipping','done','cancelled') DEFAULT 'pending',
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Chi tiết đơn hàng
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Giỏ hàng
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    UNIQUE KEY(user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ============================================
-- DỮ LIỆU MẪU
-- ============================================

-- Tài khoản admin (password: admin123)
-- Mật khẩu admin: password (hash bcrypt)
INSERT INTO users (username, password, fullname, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Quản Trị Viên', 'admin@shop.com', 'admin');
-- Nếu đăng nhập admin không được, chạy lệnh sau trong phpMyAdmin:
-- UPDATE users SET password='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username='admin';

-- Tài khoản khách hàng mẫu (password: 123456)
INSERT INTO users (username, password, fullname, email, phone, address, ward, district, city, role) VALUES
('nguyen_a', '$2y$10$TKh8H1.PyfcAZgtz.hJRdOxKaJlf8E6k3Z.1Fs3EwU7z8VNvw1Z2', 'Nguyễn Văn A', 'a@gmail.com', '0901234567', '123 Lê Lợi', 'Bến Nghé', 'Quận 1', 'TP.HCM', 'customer'),
('tran_b', '$2y$10$TKh8H1.PyfcAZgtz.hJRdOxKaJlf8E6k3Z.1Fs3EwU7z8VNvw1Z2', 'Trần Thị B', 'b@gmail.com', '0907654321', '456 Nguyễn Huệ', 'Bến Thành', 'Quận 1', 'TP.HCM', 'customer');

-- Loại sản phẩm
INSERT INTO categories (name, slug, description, profit_rate) VALUES
('Rau củ quả', 'rau-cu-qua', 'Rau củ quả tươi sạch', 25.00),
('Trái cây', 'trai-cay', 'Trái cây tươi nhập khẩu và trong nước', 30.00),
('Thịt & Hải sản', 'thit-hai-san', 'Thịt tươi và hải sản các loại', 20.00),
('Đồ khô & Gia vị', 'do-kho-gia-vi', 'Gạo, nước mắm, gia vị các loại', 15.00),
('Sữa & Trứng', 'sua-trung', 'Sữa tươi, sữa hộp, trứng gà', 18.00);

-- Sản phẩm mẫu
INSERT INTO products (category_id, code, name, description, unit, cost_price, profit_rate, sell_price, stock) VALUES
(1, 'RAU001', 'Cải xanh', 'Cải xanh tươi sạch, không thuốc trừ sâu', 'kg', 8000, 25, 10000, 50),
(1, 'RAU002', 'Rau muống', 'Rau muống nước tươi', 'bó', 5000, 20, 6000, 80),
(1, 'RAU003', 'Cà chua bi', 'Cà chua bi đỏ tươi ngon', 'kg', 15000, 30, 19500, 30),
(2, 'TRAI001', 'Chuối tiêu', 'Chuối tiêu vàng chín ngọt', 'nải', 20000, 25, 25000, 40),
(2, 'TRAI002', 'Xoài cát Hòa Lộc', 'Xoài cát Hòa Lộc thơm ngon', 'kg', 45000, 33, 60000, 20),
(3, 'THIT001', 'Thịt heo ba chỉ', 'Thịt ba chỉ tươi heo sạch', 'kg', 90000, 20, 108000, 25),
(3, 'THIT002', 'Cá lóc', 'Cá lóc tươi sống đồng quê', 'kg', 60000, 25, 75000, 15),
(4, 'KHO001', 'Gạo Jasmine', 'Gạo Jasmine thơm dẻo 5kg', 'túi 5kg', 65000, 15, 74750, 100),
(5, 'TRUNG001', 'Trứng gà ta', 'Trứng gà ta tươi, vỏ nâu', 'vỉ 10 trứng', 35000, 20, 42000, 60);
