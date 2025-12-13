<?php
/**
 * صفحة إضافة أمر إنتاج
 * Production Module - Add Order
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

$pageTitle = 'أمر إنتاج جديد';
$success = '';
$error = '';

// جلب المنتجات
$products = $db->fetchAll("SELECT id, name FROM products WHERE company_id = ? AND is_active = 1 ORDER BY name", [$company_id]);

// جلب قوائم المواد
$boms = [];
try {
    $boms = $db->fetchAll("SELECT id, name FROM production_bom WHERE company_id = ? AND is_active = 1 ORDER BY name", [$company_id]);
} catch (Exception $e) {}

// معالجة الإضافة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)$_POST['product_id'];
    $bom_id = !empty($_POST['bom_id']) ? (int)$_POST['bom_id'] : null;
    $quantity = (float)$_POST['quantity'];
    $due_date = $_POST['due_date'] ?: null;
    $priority = $_POST['priority'] ?? 'normal';
    $notes = $_POST['notes'] ?? '';
    
    if ($product_id && $quantity > 0) {
        try {
            // توليد رقم الأمر
            $orderNumber = 'PO-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
            
            $conn = $db->getConnection();
            $stmt = $conn->prepare("INSERT INTO production_orders 
                (company_id, order_number, bom_id, product_id, quantity, due_date, priority, notes, status, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->execute([
                $company_id, $orderNumber, $bom_id, $product_id, $quantity, $due_date, $priority, $notes, $user['id']
            ]);
            
            $success = "تم إنشاء أمر الإنتاج بنجاح: $orderNumber";
        } catch (Exception $e) {
            $error = "خطأ: " . $e->getMessage();
        }
    } else {
        $error = "يرجى اختيار المنتج وتحديد الكمية";
    }
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
                           class="nav-link <?= $module['slug'] === 'production' ? 'active' : '' ?>">
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
                    <h1><i class="fas fa-plus-circle"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <button class="menu-toggle-btn" onclick="toggleSidebar()" title="القائمة">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="orders.php" class="btn btn-outline">عودة للقائمة</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item"><i class="fas fa-industry"></i><span>لوحة الإنتاج</span></a>
                        <a href="orders.php" class="submenu-item active"><i class="fas fa-clipboard-list"></i><span>أوامر الإنتاج</span></a>
                        <a href="bom.php" class="submenu-item"><i class="fas fa-sitemap"></i><span>قوائم المواد</span></a>
                    </div>
                </div>

                <?php if ($success): ?><div class="alert alert-success mb-3"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger mb-3"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">بيانات أمر الإنتاج</h3></div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="form-label">المنتج المراد إنتاجه *</label>
                                        <select name="product_id" class="form-control" required>
                                            <option value="">اختر المنتج</option>
                                            <?php foreach ($products as $p): ?>
                                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="form-label">قائمة المواد (BOM)</label>
                                        <select name="bom_id" class="form-control">
                                            <option value="">بدون قائمة مواد</option>
                                            <?php foreach ($boms as $b): ?>
                                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-4">
                                    <div class="form-group">
                                        <label class="form-label">الكمية المطلوبة *</label>
                                        <input type="number" name="quantity" class="form-control" min="1" step="1" required>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="form-group">
                                        <label class="form-label">تاريخ الاستحقاق</label>
                                        <input type="date" name="due_date" class="form-control">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="form-group">
                                        <label class="form-label">الأولوية</label>
                                        <select name="priority" class="form-control">
                                            <option value="low">منخفضة</option>
                                            <option value="normal" selected>عادية</option>
                                            <option value="high">عالية</option>
                                            <option value="urgent">عاجلة</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">ملاحظات</label>
                                <textarea name="notes" class="form-control" rows="3"></textarea>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ أمر الإنتاج</button>
                                <a href="orders.php" class="btn btn-outline">إلغاء</a>
                            </div>
                        </form>
                    </div>
                </div>
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
