<?php
$page_title = 'Sản Phẩm';
require_once 'includes/header.php';

$per_page = 12;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$q = trim($_GET['q'] ?? '');
$cat_id = (int)($_GET['cat'] ?? 0);
$min_price = (int)($_GET['min'] ?? 0);
$max_price = (int)($_GET['max'] ?? 0);

// Build query
$where = ["p.status = 'show'"];
$params = [];

if ($q) { $where[] = "p.name LIKE ?"; $params[] = "%$q%"; }
if ($cat_id) { $where[] = "p.category_id = ?"; $params[] = $cat_id; }
if ($min_price > 0) { $where[] = "p.sell_price >= ?"; $params[] = $min_price; }
if ($max_price > 0) { $where[] = "p.sell_price <= ?"; $params[] = $max_price; }

$where_sql = 'WHERE ' . implode(' AND ', $where);

// Count total
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id=c.id $where_sql");
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();

// Get products
$params_page = array_merge($params, [$per_page, $offset]);
$stmt = $pdo->prepare("SELECT p.*, c.name as cat_name FROM products p 
    JOIN categories c ON p.category_id=c.id 
    $where_sql ORDER BY p.id DESC LIMIT ? OFFSET ?");
$stmt->execute($params_page);
$products = $stmt->fetchAll();

// Build URL pattern for pagination
$url_params = http_build_query(array_filter(['q'=>$q,'cat'=>$cat_id,'min'=>$min_price,'max'=>$max_price]));
$url_pattern = '/shop/products.php?' . ($url_params ? $url_params . '&' : '') . 'page=%d';

$current_cat = $cat_id ? $pdo->prepare("SELECT name FROM categories WHERE id=?") : null;
if ($current_cat) { $current_cat->execute([$cat_id]); $cat_name = $current_cat->fetchColumn(); }

$prod_icons = ['🥦','🍅','🌽','🥕','🍎','🍋','🥩','🥚','🧅','🫛','🥑','🍊'];
?>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="/shop/index.php">🏠 Trang chủ</a>
    <span class="sep">›</span>
    <span>Sản phẩm<?= $cat_id ? ' / ' . sanitize($cat_name ?? '') : '' ?></span>
    <?php if ($q): ?>
    <span class="sep">›</span>
    <span>Tìm: "<?= sanitize($q) ?>"</span>
    <?php endif; ?>
</div>

<div class="page-layout">
    <!-- Sidebar -->
    <aside>
        <div class="card">
            <div class="card-body">
                <!-- Tìm kiếm nâng cao -->
                <form action="/shop/products.php" method="GET">
                    <div class="sidebar-title">🔍 Tìm kiếm nâng cao</div>
                    <div class="form-group">
                        <input type="text" name="q" class="form-control" placeholder="Tên sản phẩm..." value="<?= sanitize($q) ?>">
                    </div>
                    <div class="form-group">
                        <label>Danh mục</label>
                        <select name="cat" class="form-control">
                            <option value="">-- Tất cả --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $cat_id==$cat['id']?'selected':'' ?>>
                                <?= sanitize($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Giá từ (₫)</label>
                        <input type="number" name="min" class="form-control" placeholder="0" value="<?= $min_price ?: '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Đến (₫)</label>
                        <input type="number" name="max" class="form-control" placeholder="Không giới hạn" value="<?= $max_price ?: '' ?>">
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">🔍 Tìm kiếm</button>
                    <a href="/shop/products.php" class="btn btn-outline btn-full mt-1">↩ Xóa bộ lọc</a>
                </form>

                <hr style="margin:20px 0; border:none; border-top:1px solid var(--border)">

                <!-- Danh mục nhanh -->
                <div class="sidebar-title">🏷️ Danh mục</div>
                <a href="/shop/products.php" class="cat-link <?= !$cat_id?'active':'' ?>">🛍️ Tất cả sản phẩm</a>
                <?php 
                $cat_icons = [1=>'🥦', 2=>'🍎', 3=>'🥩', 4=>'🌾', 5=>'🥛'];
                foreach ($categories as $cat): ?>
                <a href="/shop/products.php?cat=<?= $cat['id'] ?>" 
                   class="cat-link <?= $cat_id==$cat['id']?'active':'' ?>">
                    <?= ($cat_icons[$cat['id']] ?? '🌿') . ' ' . sanitize($cat['name']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>

    <!-- Products -->
    <div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="font-size:20px; font-weight:700;">
                <?= $q ? "Kết quả tìm \"" . sanitize($q) . "\"" : ($cat_id ? sanitize($cat_name??'Sản phẩm') : 'Tất cả sản phẩm') ?>
                <span style="font-size:14px; font-weight:400; color:var(--text-muted); margin-left:8px;">(<?= $total ?> sản phẩm)</span>
            </h2>
        </div>

        <?php if (empty($products)): ?>
        <div class="card">
            <div class="empty-state">
                <div class="icon">😔</div>
                <h3>Không tìm thấy sản phẩm</h3>
                <p>Hãy thử từ khóa khác hoặc xóa bộ lọc</p>
                <a href="/shop/products.php" class="btn btn-primary mt-2">Xem tất cả</a>
            </div>
        </div>
        <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $i => $p): ?>
            <div class="product-card">
                <a href="/shop/product.php?id=<?= $p['id'] ?>">
                    <div class="img-wrap">
                        <?php if ($p['image'] && file_exists(__DIR__.'/uploads/products/'.$p['image'])): ?>
                            <img src="/shop/uploads/products/<?= $p['image'] ?>" alt="<?= sanitize($p['name']) ?>">
                        <?php else: ?>
                            <?= $prod_icons[$i % count($prod_icons)] ?>
                        <?php endif; ?>
                    </div>
                </a>
                <div class="card-body">
                    <div class="prod-cat"><?= sanitize($p['cat_name']) ?></div>
                    <a href="/shop/product.php?id=<?= $p['id'] ?>">
                        <div class="prod-name"><?= sanitize($p['name']) ?></div>
                    </a>
                    <div class="prod-unit">📦 <?= sanitize($p['unit']) ?> | 🏪 Còn <?= $p['stock'] ?></div>
                    <div class="prod-price"><?= formatPrice($p['sell_price']) ?></div>
                    <?php if ($p['stock'] > 0): ?>
                    <div class="card-actions">
                        <form action="/shop/cart_action.php" method="POST" style="flex:1">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-accent btn-sm btn-full">🛒 Thêm giỏ</button>
                        </form>
                        <a href="/shop/product.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">👁</a>
                    </div>
                    <?php else: ?>
                    <div class="badge badge-danger" style="width:100%;text-align:center;padding:8px;">Hết hàng</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?= paginate($total, $per_page, $page, $url_pattern) ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>