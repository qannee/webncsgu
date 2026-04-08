<?php
$page_title = 'Đặt Hàng';
require_once 'includes/header.php';
requireLogin('/shop/login.php?redirect=/shop/checkout.php');

$user_id = $_SESSION['user_id'];

// Lấy giỏ hàng
$stmt = $pdo->prepare("SELECT c.*, p.name, p.image, p.sell_price, p.unit, p.stock
    FROM cart c JOIN products p ON c.product_id=p.id WHERE c.user_id=? AND p.status='show'");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

if (empty($cart_items)) {
    $_SESSION['flash'] = ['type'=>'warning', 'msg'=>'Giỏ hàng trống!'];
    redirect('/shop/cart.php');
}

// Thông tin user
$user = $pdo->prepare("SELECT * FROM users WHERE id=?")->execute([$user_id]) ? null : null;
$stmt2 = $pdo->prepare("SELECT * FROM users WHERE id=?"); $stmt2->execute([$user_id]);
$user = $stmt2->fetch();

$subtotal = 0;
foreach ($cart_items as $item) $subtotal += $item['sell_price'] * $item['quantity'];
$ship_fee = $subtotal >= 200000 ? 0 : 20000;
$total = $subtotal + $ship_fee;

$error = '';
$order_placed = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ship_name    = trim($_POST['ship_name'] ?? '');
    $ship_phone   = trim($_POST['ship_phone'] ?? '');
    $ship_address = trim($_POST['ship_address'] ?? '');
    $ship_ward    = trim($_POST['ship_ward'] ?? '');
    $ship_district= trim($_POST['ship_district'] ?? '');
    $ship_city    = trim($_POST['ship_city'] ?? '');
    $payment      = $_POST['payment'] ?? 'cash';
    $note         = trim($_POST['note'] ?? '');

    if (!$ship_name || !$ship_phone || !$ship_address || !$ship_ward || !$ship_district || !$ship_city) {
        $error = 'Vui lòng nhập đầy đủ thông tin giao hàng!';
    } elseif (!preg_match('/^[0-9]{9,11}$/', $ship_phone)) {
        $error = 'Số điện thoại không hợp lệ (9-11 chữ số)!';
    } else {
        try {
            $pdo->beginTransaction();
            $order_code = generateCode('DH');

            // Tạo đơn hàng
            $ins = $pdo->prepare("INSERT INTO orders (user_id, order_code, total, ship_name, ship_phone, ship_address, ship_ward, ship_district, ship_city, payment_method, note) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $ins->execute([$user_id, $order_code, $total, $ship_name, $ship_phone, $ship_address, $ship_ward, $ship_district, $ship_city, $payment, $note]);
            $order_id = $pdo->lastInsertId();

            // Thêm chi tiết & trừ stock
            foreach ($cart_items as $item) {
                $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)")
                    ->execute([$order_id, $item['product_id'], $item['quantity'], $item['sell_price']]);
                $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id=?")
                    ->execute([$item['quantity'], $item['product_id']]);
            }

            // Xóa giỏ hàng
            $pdo->prepare("DELETE FROM cart WHERE user_id=?")->execute([$user_id]);
            $pdo->commit();
            $order_placed = $order_code;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Đặt hàng thất bại, vui lòng thử lại!';
        }
    }
}
?>

<div class="breadcrumb">
    <a href="/shop/index.php">🏠 Trang chủ</a>
    <span class="sep">›</span>
    <a href="/shop/cart.php">Giỏ hàng</a>
    <span class="sep">›</span>
    <span>Đặt hàng</span>
</div>

<div style="padding:32px 40px;">

<?php if ($order_placed): ?>
<!-- Đặt hàng thành công -->
<div class="card" style="max-width:600px;margin:0 auto;text-align:center;">
    <div class="card-body" style="padding:50px 40px;">
        <div style="font-size:72px;margin-bottom:16px;">🎉</div>
        <h2 style="font-size:28px;font-weight:800;color:var(--primary);margin-bottom:8px;">Đặt hàng thành công!</h2>
        <p style="color:var(--text-muted);margin-bottom:20px;">Cảm ơn bạn đã mua hàng tại FoodShop</p>
        <div style="background:var(--bg);border-radius:12px;padding:20px;margin-bottom:24px;">
            <div style="font-size:14px;color:var(--text-muted);margin-bottom:6px;">Mã đơn hàng</div>
            <div style="font-size:24px;font-weight:800;color:var(--primary);"><?= $order_placed ?></div>
        </div>
        <p style="font-size:14px;color:var(--text-muted);margin-bottom:24px;">
            Chúng tôi sẽ liên hệ với bạn để xác nhận đơn hàng trong thời gian sớm nhất.
        </p>
        <div style="display:flex;gap:12px;justify-content:center;">
            <a href="/shop/orders.php" class="btn btn-primary">📦 Xem đơn hàng</a>
            <a href="/shop/products.php" class="btn btn-outline">← Tiếp tục mua sắm</a>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Form đặt hàng -->
<h1 style="font-size:26px;font-weight:800;margin-bottom:24px;">✅ Xác nhận đặt hàng</h1>

<?php if ($error): ?><div class="alert alert-danger">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

<div class="cart-layout">
    <form method="POST">
        <div class="card mb-3">
            <div class="card-header"><h3>📍 Thông tin giao hàng</h3></div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Tên người nhận *</label>
                        <input type="text" name="ship_name" class="form-control" value="<?= sanitize($user['fullname']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại *</label>
                        <input type="tel" name="ship_phone" class="form-control" value="<?= sanitize($user['phone']) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Địa chỉ *</label>
                    <input type="text" name="ship_address" id="f_address" class="form-control" value="<?= sanitize($user['address']) ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Phường/Xã *</label>
                        <input type="text" name="ship_ward" id="f_ward" class="form-control" value="<?= sanitize($user['ward']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Quận/Huyện *</label>
                        <input type="text" name="ship_district" id="f_district" class="form-control" value="<?= sanitize($user['district']) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Tỉnh/Thành phố *</label>
                    <input type="text" name="ship_city" id="f_city" class="form-control" value="<?= sanitize($user['city']) ?>" required>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h3>💳 Phương thức thanh toán</h3></div>
            <div class="card-body">
                <label style="display:flex;gap:10px;align-items:center;padding:14px;border:2px solid var(--primary);border-radius:8px;cursor:pointer;margin-bottom:10px;background:#f1f8e1;">
                    <input type="radio" name="payment" value="cash" checked>
                    <span>💵 Thanh toán tiền mặt khi nhận hàng (COD)</span>
                </label>
                <label style="display:flex;gap:10px;align-items:center;padding:14px;border:2px solid var(--border);border-radius:8px;cursor:pointer;margin-bottom:10px;" id="pay-transfer-lbl">
                    <input type="radio" name="payment" value="transfer" onchange="document.getElementById('bank-info').style.display='block'">
                    <span>🏦 Chuyển khoản ngân hàng</span>
                </label>
                <div id="bank-info" style="display:none;background:var(--bg);border-radius:8px;padding:14px;margin-bottom:10px;font-size:13px;">
                    <strong>Thông tin chuyển khoản:</strong><br>
                    🏦 Ngân hàng: Vietcombank<br>
                    💳 Số TK: 1234567890<br>
                    👤 Chủ TK: FOODSHOP CO., LTD<br>
                    📝 Nội dung: [Mã đơn hàng của bạn]
                </div>
                <label style="display:flex;gap:10px;align-items:center;padding:14px;border:2px solid var(--border);border-radius:8px;cursor:pointer;">
                    <input type="radio" name="payment" value="online">
                    <span>💳 Thanh toán trực tuyến (VNPay, Momo...)</span>
                </label>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>📝 Ghi chú đơn hàng</h3></div>
            <div class="card-body">
                <textarea name="note" class="form-control" placeholder="VD: Giao buổi sáng, gọi trước khi giao..."><?= sanitize($_POST['note'] ?? '') ?></textarea>
            </div>
        </div>
    </form>

    <!-- Tóm tắt -->
    <div>
        <div class="card">
            <div class="card-header"><h3>🧾 Đơn hàng của bạn</h3></div>
            <div class="card-body" style="padding:0">
                <table class="table">
                    <tbody>
                    <?php foreach ($cart_items as $item): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;font-size:13px;"><?= sanitize($item['name']) ?></div>
                            <div style="font-size:12px;color:var(--text-muted);">x<?= $item['quantity'] ?> <?= sanitize($item['unit']) ?></div>
                        </td>
                        <td style="text-align:right;font-weight:700;white-space:nowrap;"><?= formatPrice($item['sell_price'] * $item['quantity']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="padding:16px;border-top:1px solid var(--border);">
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:14px;">
                        <span>Tạm tính</span><strong><?= formatPrice($subtotal) ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:16px;font-size:14px;">
                        <span>Phí ship</span>
                        <strong><?= $ship_fee == 0 ? '<span style="color:var(--primary)">Miễn phí</span>' : formatPrice($ship_fee) ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:20px;font-weight:800;color:var(--accent);padding-top:12px;border-top:2px solid var(--border);">
                        <span>Tổng</span><span><?= formatPrice($total) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" style="margin-top:16px;">
            <!-- Copy all fields via JS -->
            <div id="hidden-fields"></div>
            <button type="submit" onclick="copyFormFields(event)" class="btn btn-accent btn-full btn-lg">🛒 Xác nhận đặt hàng</button>
        </form>
        <a href="/shop/cart.php" class="btn btn-outline btn-full mt-1">← Quay lại giỏ hàng</a>
    </div>
</div>

<script>
function copyFormFields(e) {
    e.preventDefault();
    const mainForm = document.querySelector('form[method="POST"]');
    const submitForm = e.target.closest('form');
    const formData = new FormData(mainForm);
    let html = '';
    for (const [key, val] of formData.entries()) {
        html += `<input type="hidden" name="${key}" value="${val.replace(/"/g,'&quot;')}">`;
    }
    document.getElementById('hidden-fields').innerHTML = html;
    submitForm.submit();
}
</script>
<?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>