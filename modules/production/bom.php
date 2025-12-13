<?php
/**
 * صفحة قوائم المواد (BOM)
 * Production Module - Bill of Materials
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

$pageTitle = 'قوائم المواد (BOM)';
$success = '';
$error = '';

// جلب المنتجات
$products = $db->fetchAll("SELECT id, name FROM products WHERE company_id = ? AND is_active = 1 ORDER BY name", [$company_id]);

// معالجة الإضافة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['name']);
        $product_id = (int)$_POST['product_id'];
        $description = trim($_POST['description'] ?? '');
        
        if ($name && $product_id) {
            try {
                $conn = $db->getConnection();
                $stmt = $conn->prepare("INSERT INTO production_bom (company_id, product_id, name, description, created_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$company_id, $product_id, $name, $description, $user['id']]);
                $success = "تم إضافة قائمة المواد بنجاح";
            } catch (Exception $e) {
                $error = "خطأ: " . $e->getMessage();
            }
        } else {
            $error = "يرجى إدخال الاسم واختيار المنتج";
        }
    }
    
    if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        try {
            $db->delete('production_bom', "id = ? AND company_id = ?", [(int)$_POST['id'], $company_id]);
            $success = "تم حذف قائمة المواد بنجاح";
        } catch (Exception $e) {
            $error = "خطأ في الحذف";
        }
    }
}

// جلب قوائم المواد
$boms = [];
try {
    $boms = $db->fetchAll(
        "SELECT b.*, p.name as product_name,
                (SELECT COUNT(*) FROM production_bom_items WHERE bom_id = b.id) as items_count
         FROM production_bom b 
         LEFT JOIN products p ON b.product_id = p.id 
         WHERE b.company_id = ? 
         ORDER BY b.name",
        [$company_id]
    );
} catch (Exception $e) {}
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
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: var(--bg-card); padding: 24px; border-radius: var(--radius-lg); width: 100%; max-width: 500px; }
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
                    <h1><i class="fas fa-sitemap"></i> <?= $pageTitle ?></h1>
                    <p>إدارة المكونات والمواد الخام للمنتجات</p>
                </div>
                <div class="header-actions">
                    <button class="menu-toggle-btn" onclick="toggleSidebar()" title="القائمة">
                        <i class="fas fa-bars"></i>
                    </button>
                    <button onclick="openModal()" class="btn btn-primary"><i class="fas fa-plus"></i> قائمة مواد جديدة</button>
                    <a href="index.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item"><i class="fas fa-industry"></i><span>لوحة الإنتاج</span></a>
                        <a href="orders.php" class="submenu-item"><i class="fas fa-clipboard-list"></i><span>أوامر الإنتاج</span></a>
                        <a href="bom.php" class="submenu-item active"><i class="fas fa-sitemap"></i><span>قوائم المواد</span></a>
                    </div>
                </div>

                <?php if ($success): ?><div class="alert alert-success mb-3"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger mb-3"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>الاسم</th>
                                        <th>المنتج النهائي</th>
                                        <th>عدد المكونات</th>
                                        <th>الحالة</th>
                                        <th>إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($boms)): ?>
                                    <tr><td colspan="5" class="text-center text-muted p-3">لا توجد قوائم مواد</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($boms as $bom): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($bom['name']) ?></td>
                                        <td><?= htmlspecialchars($bom['product_name'] ?? '-') ?></td>
                                        <td><?= $bom['items_count'] ?> مكون</td>
                                        <td><span class="badge badge-<?= $bom['is_active'] ? 'success' : 'secondary' ?>"><?= $bom['is_active'] ? 'فعال' : 'معطل' ?></span></td>
                                        <td>
                                            <a href="bom_items.php?id=<?= $bom['id'] ?>" class="btn btn-sm btn-outline" title="المكونات"><i class="fas fa-list"></i></a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $bom['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal -->
    <div id="bomModal" class="modal">
        <div class="modal-content">
            <h3 class="mb-3">قائمة مواد جديدة</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label class="form-label">اسم القائمة *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">المنتج النهائي *</label>
                    <select name="product_id" class="form-control" required>
                        <option value="">اختر المنتج</option>
                        <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
                <div class="d-flex gap-2 justify-end">
                    <button type="button" onclick="closeModal()" class="btn btn-outline">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() { document.getElementById('bomModal').classList.add('active'); }
        function closeModal() { document.getElementById('bomModal').classList.remove('active'); }
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
