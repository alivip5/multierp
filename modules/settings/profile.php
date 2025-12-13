<?php
/**
 * صفحة الملف الشخصي
 * Settings Module - User Profile
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

// الموديولات للقائمة الجانبية
$enabledModules = getSidebarItems($company['id'], $_SESSION['user_id']);

$pageTitle = 'الملف الشخصي';
$success = '';
$error = '';

// معالجة تحديث البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone'] ?? '');
        
        if ($full_name && $email) {
            try {
                $conn = $db->getConnection();
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $phone, $user['id']]);
                $success = "تم تحديث البيانات بنجاح";
                $user = $db->fetch("SELECT u.*, r.name_ar as role_name, r.name as role_slug FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?", [$_SESSION['user_id']]);
            } catch (Exception $e) {
                $error = "خطأ في التحديث: " . $e->getMessage();
            }
        } else {
            $error = "يرجى إدخال الاسم والبريد الإلكتروني";
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password && strlen($new_password) >= 6) {
                try {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $conn = $db->getConnection();
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed, $user['id']]);
                    $success = "تم تغيير كلمة المرور بنجاح";
                } catch (Exception $e) {
                    $error = "خطأ في تغيير كلمة المرور";
                }
            } else {
                $error = "كلمة المرور الجديدة غير متطابقة أو أقل من 6 أحرف";
            }
        } else {
            $error = "كلمة المرور الحالية غير صحيحة";
        }
    }
    
    if (isset($_POST['update_theme'])) {
        $theme = $_POST['theme'] === 'light' ? 'light' : 'dark';
        try {
            $conn = $db->getConnection();
            $stmt = $conn->prepare("UPDATE users SET theme = ? WHERE id = ?");
            $stmt->execute([$theme, $user['id']]);
            $success = "تم تحديث الثيم بنجاح";
            $user['theme'] = $theme;
        } catch (Exception $e) {
            $error = "خطأ في تحديث الثيم";
        }
    }
}
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
                    <h1><i class="fas fa-user"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <a href="../../pages/dashboard.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="../../pages/settings.php" class="submenu-item"><i class="fas fa-cog"></i><span>الإعدادات العامة</span></a>
                        <a href="branches.php" class="submenu-item"><i class="fas fa-code-branch"></i><span>الفروع والمخازن</span></a>
                        <a href="users.php" class="submenu-item"><i class="fas fa-users"></i><span>المستخدمين</span></a>
                        <a href="roles.php" class="submenu-item"><i class="fas fa-user-shield"></i><span>الأدوار</span></a>
                        <a href="profile.php" class="submenu-item active"><i class="fas fa-user"></i><span>الملف الشخصي</span></a>
                    </div>
                </div>

                <?php if ($success): ?><div class="alert alert-success mb-3"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger mb-3"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>

                <div class="row">
                    <div class="col-6">
                        <!-- معلومات الحساب -->
                        <div class="card mb-3">
                            <div class="card-header"><h3 class="card-title"><i class="fas fa-id-card"></i> معلومات الحساب</h3></div>
                            <div class="card-body">
                                <form method="POST">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="update_profile" value="1">
                                    <div class="form-group">
                                        <label class="form-label">اسم المستخدم</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">الاسم الكامل *</label>
                                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">البريد الإلكتروني *</label>
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">الهاتف</label>
                                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">الدور</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['role_name']) ?>" disabled>
                                    </div>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ التغييرات</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <!-- تغيير كلمة المرور -->
                        <div class="card mb-3">
                            <div class="card-header"><h3 class="card-title"><i class="fas fa-key"></i> تغيير كلمة المرور</h3></div>
                            <div class="card-body">
                                <form method="POST">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="change_password" value="1">
                                    <div class="form-group">
                                        <label class="form-label">كلمة المرور الحالية *</label>
                                        <input type="password" name="current_password" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">كلمة المرور الجديدة *</label>
                                        <input type="password" name="new_password" class="form-control" minlength="6" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">تأكيد كلمة المرور *</label>
                                        <input type="password" name="confirm_password" class="form-control" required>
                                    </div>
                                    <button type="submit" class="btn btn-warning"><i class="fas fa-key"></i> تغيير كلمة المرور</button>
                                </form>
                            </div>
                        </div>

                        <!-- إعدادات المظهر -->
                        <div class="card">
                            <div class="card-header"><h3 class="card-title"><i class="fas fa-palette"></i> المظهر</h3></div>
                            <div class="card-body">
                                <form method="POST">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="update_theme" value="1">
                                    <div class="form-group">
                                        <label class="form-label">الثيم</label>
                                        <select name="theme" class="form-control">
                                            <option value="dark" <?= ($user['theme'] ?? 'dark') === 'dark' ? 'selected' : '' ?>>داكن</option>
                                            <option value="light" <?= ($user['theme'] ?? 'dark') === 'light' ? 'selected' : '' ?>>فاتح</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-outline"><i class="fas fa-save"></i> حفظ</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>
</body>
</html>
