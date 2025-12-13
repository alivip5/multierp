<?php
/**
 * صفحة إدارة المستخدمين
 * Settings Module - Users Management
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

// التحقق من الصلاحيات (admin فقط)
if (!in_array($user['role_slug'], ['super_admin', 'manager'])) {
    header('Location: ../../pages/dashboard.php?error=not_authorized');
    exit;
}

// الموديولات للقائمة الجانبية
$enabledModules = getSidebarItems($company['id'], $_SESSION['user_id']);

$pageTitle = 'إدارة المستخدمين';
$success = '';
$error = '';

// جلب الأدوار
$roles = $db->fetchAll("SELECT * FROM roles ORDER BY id");

// التحقق من CSRF لطلبات POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'خطأ في التحقق من الأمان - يرجى إعادة المحاولة';
    } elseif (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $full_name = trim($_POST['full_name']);
            $password = $_POST['password'];
            $role_id = (int)$_POST['role_id'];
            $phone = trim($_POST['phone'] ?? '');
            
            if ($username && $email && $full_name && $password && $role_id) {
                // التحقق من عدم وجود المستخدم
                $exists = $db->fetch("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
                if ($exists) {
                    $error = "اسم المستخدم أو البريد الإلكتروني مستخدم بالفعل";
                } else {
                    try {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $conn = $db->getConnection();
                        $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, role_id, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
                        $stmt->execute([$username, $email, $hashed, $full_name, $phone, $role_id]);
                        
                        $newUserId = $conn->lastInsertId();
                        // ربط المستخدم بالشركة
                        $stmt2 = $conn->prepare("INSERT INTO user_companies (user_id, company_id, is_default) VALUES (?, ?, 1)");
                        $stmt2->execute([$newUserId, $company_id]);
                        
                        $success = "تم إضافة المستخدم بنجاح";
                        log_audit($company_id, $user['id'], 'user_created', 'users', $newUserId, null, ['username' => $username, 'email' => $email, 'role_id' => $role_id]);
                    } catch (Exception $e) {
                        $error = "خطأ: " . $e->getMessage();
                    }
                }
            } else {
                $error = "يرجى ملء جميع الحقول المطلوبة";
            }
        }
        
        if ($_POST['action'] === 'toggle' && isset($_POST['user_id'])) {
            $userId = (int)$_POST['user_id'];
            $isActive = (int)$_POST['is_active'];
            $db->update('users', ['is_active' => $isActive], 'id = ?', [$userId]);
            $success = "تم تحديث حالة المستخدم";
            log_audit($company_id, $user['id'], $isActive ? 'user_activated' : 'user_deactivated', 'users', $userId);
        }
        
        if ($_POST['action'] === 'delete' && isset($_POST['user_id'])) {
            $userId = (int)$_POST['user_id'];
            if ($userId != $user['id']) {
                $db->delete('user_companies', 'user_id = ?', [$userId]);
                $db->delete('users', 'id = ?', [$userId]);
                $success = "تم حذف المستخدم";
                log_audit($company_id, $user['id'], 'user_deleted', 'users', $userId);
            } else {
                $error = "لا يمكنك حذف حسابك الخاص";
            }
        }
        
        if ($_POST['action'] === 'reset_password' && isset($_POST['user_id'])) {
            $userId = (int)$_POST['user_id'];
            $newPassword = $_POST['new_password'];
            if ($newPassword && strlen($newPassword) >= 6) {
                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $db->update('users', ['password' => $hashed], 'id = ?', [$userId]);
                $success = "تم إعادة تعيين كلمة المرور";
            } else {
                $error = "كلمة المرور يجب أن تكون 6 أحرف على الأقل";
            }
        }
    }
}

// جلب المستخدمين
$users = $db->fetchAll(
    "SELECT u.*, r.name_ar as role_name 
     FROM users u 
     JOIN roles r ON u.role_id = r.id 
     JOIN user_companies uc ON u.id = uc.user_id 
     WHERE uc.company_id = ? 
     ORDER BY u.created_at DESC",
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
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: var(--bg-card); padding: 24px; border-radius: var(--radius-lg); width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
        .user-status { display: inline-flex; align-items: center; gap: 6px; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; }
        .status-dot.active { background: var(--success); }
        .status-dot.inactive { background: var(--danger); }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
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
                    <h1><i class="fas fa-users"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <button onclick="openAddModal()" class="btn btn-primary"><i class="fas fa-plus"></i> إضافة مستخدم</button>
                    <a href="../../pages/settings.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="../../pages/settings.php" class="submenu-item"><i class="fas fa-cog"></i><span>الإعدادات العامة</span></a>
                        <a href="branches.php" class="submenu-item"><i class="fas fa-code-branch"></i><span>الفروع والمخازن</span></a>
                        <a href="users.php" class="submenu-item active"><i class="fas fa-users"></i><span>المستخدمين</span></a>
                        <a href="roles.php" class="submenu-item"><i class="fas fa-user-shield"></i><span>الأدوار</span></a>
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
                                        <th>المستخدم</th>
                                        <th>البريد الإلكتروني</th>
                                        <th>الدور</th>
                                        <th>الحالة</th>
                                        <th>آخر دخول</th>
                                        <th>إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-center gap-2">
                                                <div class="user-avatar" style="width: 36px; height: 36px; font-size: 0.9rem;">
                                                    <?= mb_substr($u['full_name'], 0, 1, 'UTF-8') ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600;"><?= htmlspecialchars($u['full_name']) ?></div>
                                                    <div class="text-muted" style="font-size: 0.8rem;">@<?= htmlspecialchars($u['username']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td><span class="badge badge-primary"><?= htmlspecialchars($u['role_name']) ?></span></td>
                                        <td>
                                            <span class="user-status">
                                                <span class="status-dot <?= $u['is_active'] ? 'active' : 'inactive' ?>"></span>
                                                <?= $u['is_active'] ? 'نشط' : 'معطل' ?>
                                            </span>
                                        </td>
                                        <td><?= $u['last_login'] ? date('Y-m-d H:i', strtotime($u['last_login'])) : 'لم يسجل دخول' ?></td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <form method="POST" style="display: inline;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="toggle">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="is_active" value="<?= $u['is_active'] ? 0 : 1 ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline" title="<?= $u['is_active'] ? 'تعطيل' : 'تفعيل' ?>">
                                                        <i class="fas fa-<?= $u['is_active'] ? 'ban' : 'check' ?>"></i>
                                                    </button>
                                                </form>
                                                <button onclick="openPasswordModal(<?= $u['id'] ?>)" class="btn btn-sm btn-outline" title="إعادة تعيين كلمة المرور">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <?php if ($u['id'] != $user['id']): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add User Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h3 class="mb-3"><i class="fas fa-user-plus"></i> إضافة مستخدم جديد</h3>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">اسم المستخدم *</label>
                            <input type="text" name="username" class="form-control" required pattern="[a-zA-Z0-9_]+" title="حروف إنجليزية وأرقام فقط">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">الاسم الكامل *</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">البريد الإلكتروني *</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">كلمة المرور *</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">الهاتف</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">الدور *</label>
                    <select name="role_id" class="form-control" required>
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name_ar']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-flex gap-2 justify-end">
                    <button type="button" onclick="closeAddModal()" class="btn btn-outline">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إضافة</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Password Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <h3 class="mb-3"><i class="fas fa-key"></i> إعادة تعيين كلمة المرور</h3>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="passwordUserId">
                <div class="form-group">
                    <label class="form-label">كلمة المرور الجديدة *</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                </div>
                <div class="d-flex gap-2 justify-end">
                    <button type="button" onclick="closePasswordModal()" class="btn btn-outline">إلغاء</button>
                    <button type="submit" class="btn btn-warning">تغيير</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
        function openAddModal() { document.getElementById('addModal').classList.add('active'); }
        function closeAddModal() { document.getElementById('addModal').classList.remove('active'); }
        function openPasswordModal(id) { 
            document.getElementById('passwordUserId').value = id;
            document.getElementById('passwordModal').classList.add('active'); 
        }
        function closePasswordModal() { document.getElementById('passwordModal').classList.remove('active'); }
    </script>
</body>
</html>
