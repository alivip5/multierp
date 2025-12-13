<?php
/**
 * إضافة / تعديل منتج
 * Inventory Module - Product Form
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/SidebarHelper.php';

$db = Database::getInstance();
$company_id = $_SESSION['company_id'] ?? 1;
// تحديث جلب بيانات المستخدم لجلب الدور
$user = $db->fetch("SELECT u.*, r.name_ar as role_name, r.name as role_slug FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?", [$_SESSION['user_id']]);
$company = $db->fetch("SELECT * FROM companies WHERE id = ?", [$company_id]);

// التأكد من وجود بيانات الدور في الجلسة
if (!isset($_SESSION['role_id']) && $user) {
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_slug'];
}

// الموديولات للقائمة الجانبية
$enabledModules = getSidebarItems($company['id'], $_SESSION['user_id']);

$id = $_GET['id'] ?? null;
$product = null;
$pageTitle = 'إضافة منتج جديد';

if ($id) {
    $product = $db->fetch("SELECT * FROM products WHERE id = ? AND company_id = ?", [$id, $company_id]);
    if ($product) {
        $pageTitle = 'تعديل منتج: ' . htmlspecialchars($product['name']);
    } else {
        die('المنتج غير موجود');
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        die('خطأ في التحقق من الأمان');
    }
    $name = $_POST['name'];
    $code = $_POST['code']; // barcode
    $price = $_POST['price'];
    $cost = $_POST['cost'] ?? 0;
    $min_stock = $_POST['min_stock'] ?? 0;
    $track_inventory = isset($_POST['track_inventory']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($id) {
        // Update
        $db->update('products', [
            'name' => $name,
            'code' => $code,
            'selling_price' => $price,
            'purchase_price' => $cost,
            'min_stock' => $min_stock,
            'track_inventory' => $track_inventory,
            'is_active' => $is_active
        ], "id = ? AND company_id = ?", [(int)$id, (int)$company_id]);
    } else {
        // Insert
        // Ensure products table has these columns. If not, setup_db might need to add them.
        // Assuming standard columns exist.
        $conn = $db->getConnection();
        $stmt = $conn->prepare("INSERT INTO products (company_id, name, code, selling_price, purchase_price, min_stock, track_inventory, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$company_id, $name, $code, $price, $cost, $min_stock, $track_inventory, $is_active]);
    }

    header('Location: products.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="<?= $user['theme'] ?? 'dark' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .module-submenu {
            background: var(--bg-surface);
            border-bottom: 1px solid var(--border);
            padding: 0 20px;
            margin: -20px -20px 20px -20px;
        }
        .submenu-container {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 12px 0;
        }
        .submenu-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: var(--radius-md);
            text-decoration: none;
            color: var(--text-muted);
            white-space: nowrap;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        .submenu-item:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }
        .submenu-item.active {
            background: var(--primary);
            color: white;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo"><i class="fas fa-building"></i></div>
                <span class="sidebar-brand"><?= htmlspecialchars($company['name']) ?></span>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">القائمة الرئيسية</div>
                    <?php foreach ($enabledModules as $module): ?>
                    <div class="nav-item">
                        <a href="<?= $module['slug'] === 'dashboard' ? '../../pages/dashboard.php' : ($module['slug'] === 'settings' ? '../settings/branches.php' : '../' . $module['slug'] . '/index.php') ?>" 
                           class="nav-link <?= $module['slug'] === 'inventory' ? 'active' : '' ?>">
                            <i class="<?= $module['icon'] ?>"></i>
                            <span><?= htmlspecialchars($module['name_ar']) ?></span>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </nav>
            <div class="sidebar-footer">
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-chevron-right"></i>
                    <span>طي القائمة</span>
                </button>
            </div>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1><?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <button class="menu-toggle-btn" onclick="toggleSidebar()" title="القائمة">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="products.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item"><i class="fas fa-boxes"></i><span>لوحة المخزون</span></a>
                        <a href="products.php" class="submenu-item"><i class="fas fa-box"></i><span>المنتجات</span></a>
                        <a href="opening_stock.php" class="submenu-item"><i class="fas fa-box-open"></i><span>أرصدة أول المدة</span></a>
                        <a href="stock_transfers.php" class="submenu-item"><i class="fas fa-exchange-alt"></i><span>نقل مخزون</span></a>
                        <a href="low-stock.php" class="submenu-item"><i class="fas fa-exclamation-triangle"></i><span>نواقص المخزون</span></a>
                    </div>
                </div>

                <form method="POST" class="card">
                    <?= csrf_field() ?>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label>اسم المنتج</label>
                                <input type="text" name="name" class="form-control" value="<?= $product['name'] ?? '' ?>" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label>الباركود / الكود</label>
                                <input type="text" name="code" class="form-control" value="<?= $product['code'] ?? '' ?>">
                            </div>
                            
                            <div class="col-6 mb-3">
                                <label>سعر البيع</label>
                                <input type="number" name="price" class="form-control" step="0.01" value="<?= $product['selling_price'] ?? '0.00' ?>" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label>سعر التكلفة</label>
                                <input type="number" name="cost" class="form-control" step="0.01" value="<?= $product['purchase_price'] ?? '0.00' ?>">
                            </div>

                            <div class="col-6 mb-3">
                                <label>الحد الأدنى للمخزون</label>
                                <input type="number" name="min_stock" class="form-control" value="<?= $product['min_stock'] ?? '0' ?>">
                            </div>

                            <div class="col-12 mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="track_inventory" id="track" <?= (!$product || $product['track_inventory']) ? 'checked' : '' ?>>
                                    <label for="track">تتبع المخزون</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" id="active" <?= (!$product || $product['is_active']) ? 'checked' : '' ?>>
                                    <label for="active">نشط</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">حفظ البيانات</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            const overlay = document.getElementById("sidebarOverlay");
            
            if (window.innerWidth < 992) {
                sidebar.classList.toggle("show");
                if (overlay) overlay.classList.toggle("show");
            } else {
                sidebar.classList.toggle("collapsed");
                localStorage.setItem("sidebarCollapsed", 
                    sidebar.classList.contains("collapsed"));
            }
        }
    </script>
</body>
</html>
