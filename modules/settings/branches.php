<?php
/**
 * صفحة إدارة الفروع والمخازن
 * Branches & Warehouses Management
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../api/config/config.php';
require_once __DIR__ . '/../../includes/SidebarHelper.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Security.php';

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

// التحقق من الصلاحيات
if (!Auth::can('settings.view') && !in_array($user['role_slug'], ['super_admin', 'manager'])) {
    header('Location: ../../pages/dashboard.php');
    exit;
}

// الموديولات للقائمة الجانبية
$enabledModules = getSidebarItems($company['id'], $_SESSION['user_id']);

$pageTitle = 'إدارة الفروع والمخازن';
$success = '';
$error = '';

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['action'] ?? '';
    
    // إضافة فرع
    if ($action === 'add_branch') {
        $name = trim($_POST['branch_name'] ?? '');
        if ($name) {
            $count = $db->fetch("SELECT COUNT(*) as c FROM branches WHERE company_id = ?", [$company_id])['c'];
            $code = 'BR-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            
            $db->insert('branches', [
                'company_id' => $company_id,
                'code' => $code,
                'name' => $name,
                'address' => $_POST['branch_address'] ?? null,
                'city' => $_POST['branch_city'] ?? null,
                'phone' => $_POST['branch_phone'] ?? null,
                'is_main' => $_POST['is_main'] ?? 0,
                'is_active' => 1,
                'created_by' => $_SESSION['user_id']
            ]);
            $success = 'تم إضافة الفرع بنجاح';
        }
    }
    
    // حذف فرع
    if ($action === 'delete_branch') {
        $branchId = (int)($_POST['branch_id'] ?? 0);
        $branch = $db->fetch("SELECT * FROM branches WHERE id = ? AND company_id = ?", [$branchId, $company_id]);
        if ($branch && !$branch['is_main']) {
            $db->delete('branches', 'id = ?', [$branchId]);
            $success = 'تم حذف الفرع';
        } else {
            $error = 'لا يمكن حذف الفرع الرئيسي';
        }
    }
    
    // إضافة مخزن
    if ($action === 'add_warehouse') {
        $name = trim($_POST['warehouse_name'] ?? '');
        $branchId = (int)($_POST['warehouse_branch'] ?? 0);
        if ($name) {
            $db->insert('warehouses', [
                'company_id' => $company_id,
                'branch_id' => $branchId ?: null,
                'name' => $name,
                'address' => $_POST['warehouse_location'] ?? null,
                'status' => 'active',
                'is_default' => $_POST['is_default'] ?? 0
            ]);
            $success = 'تم إضافة المخزن بنجاح';
        }
    }
    
    // حذف مخزن
    if ($action === 'delete_warehouse') {
        $warehouseId = (int)($_POST['warehouse_id'] ?? 0);
        $stockCount = $db->fetch("SELECT COUNT(*) as c FROM product_stock WHERE warehouse_id = ? AND quantity > 0", [$warehouseId])['c'];
        if ($stockCount == 0) {
            $db->delete('warehouses', 'id = ? AND company_id = ?', [$warehouseId, $company_id]);
            $success = 'تم حذف المخزن';
        } else {
            $error = 'لا يمكن حذف المخزن لأنه يحتوي على منتجات';
        }
    }
}

// جلب البيانات
$branches = $db->fetchAll("SELECT * FROM branches WHERE company_id = ? ORDER BY is_main DESC, name", [$company_id]);
$warehouses = $db->fetchAll(
    "SELECT w.*, b.name as branch_name,
     (SELECT COALESCE(SUM(quantity), 0) FROM product_stock WHERE warehouse_id = w.id) as total_stock
     FROM warehouses w 
     LEFT JOIN branches b ON w.branch_id = b.id 
     WHERE w.company_id = ? 
     ORDER BY w.name",
    [$company_id]
);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="<?= $user['theme'] ?? 'dark' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= htmlspecialchars($company['name']) ?></title>
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
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal.active { display: flex; }
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
                        <a href="<?= $module['slug'] === 'dashboard' ? '../../pages/dashboard.php' : ($module['slug'] === 'settings' ? '../../pages/settings.php' : '../' . $module['slug'] . '/index.php') ?>" 
                           class="nav-link <?= $module['slug'] === 'settings' ? 'active' : '' ?>">
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
                    <h1><i class="fas fa-code-branch"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <button class="menu-toggle-btn" onclick="toggleSidebar()" title="القائمة">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="../../pages/settings.php" class="btn btn-outline">عودة للإعدادات</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="../../pages/settings.php" class="submenu-item"><i class="fas fa-cog"></i><span>الإعدادات العامة</span></a>
                        <a href="branches.php" class="submenu-item active"><i class="fas fa-code-branch"></i><span>الفروع والمخازن</span></a>
                        <a href="users.php" class="submenu-item"><i class="fas fa-users"></i><span>المستخدمين</span></a>
                        <a href="roles.php" class="submenu-item"><i class="fas fa-user-shield"></i><span>الأدوار</span></a>
                    </div>
                </div>

                <?php if ($success): ?><div class="alert alert-success mb-3"><?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger mb-3"><?= $error ?></div><?php endif; ?>
                
                <div class="row">
                    <!-- الفروع -->
                    <div class="col-6">
                        <div class="card">
                            <div class="card-header d-flex justify-between align-center">
                                <h3 class="card-title"><i class="fas fa-building"></i> الفروع</h3>
                                <button class="btn btn-primary btn-sm" onclick="showModal('addBranchModal')">
                                    <i class="fas fa-plus"></i> فرع جديد
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>الكود</th>
                                            <th>الاسم</th>
                                            <th>المدينة</th>
                                            <th>الحالة</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($branches)): ?>
                                        <tr><td colspan="5" class="text-center text-muted p-3">لا توجد فروع</td></tr>
                                        <?php else: ?>
                                        <?php foreach ($branches as $branch): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($branch['code']) ?></td>
                                            <td>
                                                <?= htmlspecialchars($branch['name']) ?>
                                                <?php if ($branch['is_main']): ?>
                                                <span class="badge badge-primary">رئيسي</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($branch['city'] ?? '-') ?></td>
                                            <td>
                                                <span class="badge badge-<?= $branch['is_active'] ? 'success' : 'danger' ?>">
                                                    <?= $branch['is_active'] ? 'نشط' : 'معطل' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!$branch['is_main']): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('حذف هذا الفرع؟')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete_branch">
                                                    <input type="hidden" name="branch_id" value="<?= $branch['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                                </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- المخازن -->
                    <div class="col-6">
                        <div class="card">
                            <div class="card-header d-flex justify-between align-center">
                                <h3 class="card-title"><i class="fas fa-warehouse"></i> المخازن</h3>
                                <button class="btn btn-primary btn-sm" onclick="showModal('addWarehouseModal')">
                                    <i class="fas fa-plus"></i> مخزن جديد
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>الاسم</th>
                                            <th>الفرع</th>
                                            <th>المخزون</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($warehouses)): ?>
                                        <tr><td colspan="4" class="text-center text-muted p-3">لا توجد مخازن</td></tr>
                                        <?php else: ?>
                                        <?php foreach ($warehouses as $wh): ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($wh['name']) ?>
                                                <?php if ($wh['is_default']): ?>
                                                <span class="badge badge-info">افتراضي</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($wh['branch_name'] ?? '-') ?></td>
                                            <td><?= number_format($wh['total_stock']) ?></td>
                                            <td>
                                                <?php if ($wh['total_stock'] == 0): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('حذف هذا المخزن؟')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete_warehouse">
                                                    <input type="hidden" name="warehouse_id" value="<?= $wh['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                                </form>
                                                <?php endif; ?>
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
            </div>
        </main>
    </div>

    <!-- Modal إضافة فرع -->
    <div id="addBranchModal" class="modal">
        <div class="card" style="width:500px; max-width:90%;">
            <div class="card-header d-flex justify-between">
                <h3 class="card-title">إضافة فرع جديد</h3>
                <button onclick="hideModal('addBranchModal')" class="btn btn-sm btn-outline">&times;</button>
            </div>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_branch">
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label>اسم الفرع *</label>
                        <input type="text" name="branch_name" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label>المدينة</label>
                        <input type="text" name="branch_city" class="form-control">
                    </div>
                    <div class="form-group mb-3">
                        <label>العنوان</label>
                        <input type="text" name="branch_address" class="form-control">
                    </div>
                    <div class="form-group mb-3">
                        <label>الهاتف</label>
                        <input type="text" name="branch_phone" class="form-control">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_main" value="1" id="isMain">
                        <label for="isMain">فرع رئيسي</label>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal إضافة مخزن -->
    <div id="addWarehouseModal" class="modal">
        <div class="card" style="width:500px; max-width:90%;">
            <div class="card-header d-flex justify-between">
                <h3 class="card-title">إضافة مخزن جديد</h3>
                <button onclick="hideModal('addWarehouseModal')" class="btn btn-sm btn-outline">&times;</button>
            </div>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_warehouse">
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label>اسم المخزن *</label>
                        <input type="text" name="warehouse_name" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label>الفرع</label>
                        <select name="warehouse_branch" class="form-control">
                            <option value="">-- بدون فرع --</option>
                            <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label>الموقع</label>
                        <input type="text" name="warehouse_location" class="form-control">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_default" value="1" id="isDefault">
                        <label for="isDefault">مخزن افتراضي</label>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
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
        function showModal(id) {
            document.getElementById(id).style.display = 'flex';
        }
        function hideModal(id) {
            document.getElementById(id).style.display = 'none';
        }
    </script>
</body>
</html>
