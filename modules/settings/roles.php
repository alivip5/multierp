<?php
/**
 * صفحة إدارة الأدوار والصلاحيات
 * Settings Module - Roles & Permissions Management
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../api/config/config.php';
require_once __DIR__ . '/../../includes/SidebarHelper.php';
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

// التحقق من الصلاحيات (super_admin فقط)
if ($user['role_slug'] !== 'super_admin') {
    header('Location: ../../pages/dashboard.php?error=not_authorized');
    exit;
}

// الموديولات للقائمة الجانبية
$enabledModules = getSidebarItems($company['id'], $_SESSION['user_id']);

$pageTitle = 'الأدوار والصلاحيات';
$success = '';
$error = '';

// جلب الموديولات للصلاحيات
$modules = $db->fetchAll("SELECT * FROM modules ORDER BY sort_order");

// الصلاحيات المتاحة
$permissions = [
    'view' => 'عرض',
    'create' => 'إضافة',
    'edit' => 'تعديل',
    'delete' => 'حذف'
];

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'خطأ في التحقق من الأمان - يرجى إعادة المحاولة';
    } elseif (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_role') {
            $name = trim($_POST['name']);
            $name_ar = trim($_POST['name_ar']);
            $description = trim($_POST['description'] ?? '');
            
            if ($name && $name_ar) {
                try {
                    $conn = $db->getConnection();
                    $stmt = $conn->prepare("INSERT INTO roles (name, name_ar, description, is_system) VALUES (?, ?, ?, 0)");
                    $stmt->execute([$name, $name_ar, $description]);
                    $success = "تم إضافة الدور بنجاح";
                } catch (Exception $e) {
                    $error = "خطأ: اسم الدور موجود بالفعل";
                }
            } else {
                $error = "يرجى إدخال اسم الدور";
            }
        }
        
        if ($_POST['action'] === 'delete_role' && isset($_POST['role_id'])) {
            $roleId = (int)$_POST['role_id'];
            $role = $db->fetch("SELECT * FROM roles WHERE id = ?", [$roleId]);
            if ($role && !$role['is_system']) {
                // التحقق من عدم وجود مستخدمين بهذا الدور
                $usersCount = $db->fetch("SELECT COUNT(*) as c FROM users WHERE role_id = ?", [$roleId])['c'];
                if ($usersCount > 0) {
                    $error = "لا يمكن حذف الدور لأنه مرتبط بمستخدمين";
                } else {
                    $db->delete('role_permissions', 'role_id = ?', [$roleId]);
                    $db->delete('roles', 'id = ?', [$roleId]);
                    $success = "تم حذف الدور";
                }
            } else {
                $error = "لا يمكن حذف الأدوار الأساسية";
            }
        }
        
        if ($_POST['action'] === 'save_permissions' && isset($_POST['role_id'])) {
            $roleId = (int)$_POST['role_id'];
            $perms = $_POST['permissions'] ?? [];
            
            try {
                // حذف الصلاحيات القديمة
                $db->delete('role_permissions', 'role_id = ?', [$roleId]);
                
                // إضافة الصلاحيات الجديدة
                $conn = $db->getConnection();
                $stmt = $conn->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                
                foreach ($perms as $permId) {
                    $stmt->execute([$roleId, (int)$permId]);
                }
                
                $success = "تم حفظ الصلاحيات بنجاح";
            } catch (Exception $e) {
                $error = "خطأ في حفظ الصلاحيات";
            }
        }
    }
}

// جلب الأدوار
$roles = $db->fetchAll("SELECT r.*, (SELECT COUNT(*) FROM users WHERE role_id = r.id) as users_count FROM roles r ORDER BY r.id");

// جلب جميع الصلاحيات
$allPermissions = [];
try {
    $allPermissions = $db->fetchAll("SELECT * FROM permissions ORDER BY id");
} catch (Exception $e) {}

// جلب صلاحيات كل دور
$rolePermissions = [];
try {
    $rp = $db->fetchAll("SELECT role_id, permission_id FROM role_permissions");
    foreach ($rp as $r) {
        $rolePermissions[$r['role_id']][] = $r['permission_id'];
    }
} catch (Exception $e) {}

$selectedRole = isset($_GET['role']) ? (int)$_GET['role'] : null;
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
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: var(--bg-card); padding: 24px; border-radius: var(--radius-lg); width: 100%; max-width: 500px; }
        .role-card { background: var(--bg); padding: 16px; border-radius: var(--radius-md); margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; border: 2px solid transparent; cursor: pointer; transition: all 0.2s; }
        .role-card:hover { border-color: var(--primary); }
        .role-card.active { border-color: var(--primary); background: rgba(var(--primary-rgb), 0.1); }
        .role-card.system { opacity: 0.7; }
        .perm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
        .perm-item { background: var(--bg); padding: 12px; border-radius: var(--radius-md); display: flex; align-items: center; gap: 10px; }
        .perm-item input { width: 18px; height: 18px; accent-color: var(--primary); }
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
                    <h1><i class="fas fa-user-shield"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <button class="menu-toggle-btn" onclick="toggleSidebar()" title="القائمة">
                        <i class="fas fa-bars"></i>
                    </button>
                    <button onclick="openAddModal()" class="btn btn-primary"><i class="fas fa-plus"></i> إضافة دور</button>
                    <a href="../../pages/settings.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="../../pages/settings.php" class="submenu-item"><i class="fas fa-cog"></i><span>الإعدادات العامة</span></a>
                        <a href="branches.php" class="submenu-item"><i class="fas fa-code-branch"></i><span>الفروع والمخازن</span></a>
                        <a href="users.php" class="submenu-item"><i class="fas fa-users"></i><span>المستخدمين</span></a>
                        <a href="roles.php" class="submenu-item active"><i class="fas fa-user-shield"></i><span>الأدوار</span></a>
                        <a href="sales_agents.php" class="submenu-item"><i class="fas fa-user-tie"></i><span>المندوبين</span></a>
                    </div>
                </div>

                <?php if ($success): ?><div class="alert alert-success mb-3"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger mb-3"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>

                <div class="row">
                    <!-- قائمة الأدوار -->
                    <div class="col-4">
                        <div class="card">
                            <div class="card-header"><h3 class="card-title">الأدوار</h3></div>
                            <div class="card-body">
                                <?php foreach ($roles as $r): ?>
                                <a href="?role=<?= $r['id'] ?>" class="role-card <?= $selectedRole == $r['id'] ? 'active' : '' ?> <?= $r['is_system'] ? 'system' : '' ?>" style="text-decoration: none; color: inherit;">
                                    <div>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($r['name_ar']) ?></div>
                                        <div class="text-muted" style="font-size: 0.8rem;"><?= $r['users_count'] ?> مستخدم</div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <?php if ($r['is_system']): ?>
                                        <span class="badge badge-info">أساسي</span>
                                        <?php else: ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد؟');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete_role">
                                            <input type="hidden" name="role_id" value="<?= $r['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- صلاحيات الدور المختار -->
                    <div class="col-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <?php if ($selectedRole): ?>
                                    صلاحيات: <?= htmlspecialchars($db->fetch("SELECT name_ar FROM roles WHERE id = ?", [$selectedRole])['name_ar'] ?? '') ?>
                                    <?php else: ?>
                                    اختر دوراً لعرض الصلاحيات
                                    <?php endif; ?>
                                </h3>
                            </div>
                            <div class="card-body">
                                <?php if ($selectedRole && !empty($allPermissions)): ?>
                                <form method="POST">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="save_permissions">
                                    <input type="hidden" name="role_id" value="<?= $selectedRole ?>">
                                    
                                    <div class="perm-grid">
                                        <?php foreach ($allPermissions as $p): ?>
                                        <label class="perm-item">
                                            <input type="checkbox" name="permissions[]" value="<?= $p['id'] ?>"
                                                   <?= in_array($p['id'], $rolePermissions[$selectedRole] ?? []) ? 'checked' : '' ?>>
                                            <span><?= htmlspecialchars($p['name_ar']) ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-save"></i> حفظ الصلاحيات</button>
                                </form>
                                <?php elseif ($selectedRole): ?>
                                <div class="text-center text-muted p-3">
                                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                                    <p>لم يتم إعداد جدول الصلاحيات بعد</p>
                                    <p style="font-size: 0.85rem;">يمكنك إضافة صلاحيات في جدول permissions</p>
                                </div>
                                <?php else: ?>
                                <div class="text-center text-muted p-3">
                                    <i class="fas fa-hand-point-right fa-2x mb-2"></i>
                                    <p>اختر دوراً من القائمة لعرض وتعديل صلاحياته</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Role Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h3 class="mb-3"><i class="fas fa-plus-circle"></i> إضافة دور جديد</h3>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_role">
                <div class="form-group">
                    <label class="form-label">اسم الدور (إنجليزي) *</label>
                    <input type="text" name="name" class="form-control" required pattern="[a-z_]+" placeholder="مثال: sales_manager">
                </div>
                <div class="form-group">
                    <label class="form-label">اسم الدور (عربي) *</label>
                    <input type="text" name="name_ar" class="form-control" required placeholder="مثال: مدير المبيعات">
                </div>
                <div class="form-group">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
                <div class="d-flex gap-2 justify-end">
                    <button type="button" onclick="closeAddModal()" class="btn btn-outline">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إضافة</button>
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
        function openAddModal() { document.getElementById('addModal').classList.add('active'); }
        function closeAddModal() { document.getElementById('addModal').classList.remove('active'); }
    </script>
</body>
</html>
