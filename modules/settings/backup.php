<?php
/**
 * صفحة النسخ الاحتياطي والاستعادة
 * Settings Module - Backup & Restore
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
if (!in_array($user['role_slug'], ['super_admin', 'manager', 'admin'])) {
    header('Location: ../../pages/dashboard.php?error=not_authorized');
    exit;
}

// الموديولات للقائمة الجانبية
$enabledModules = getSidebarItems($company['id'], $_SESSION['user_id']);

$pageTitle = 'النسخ الاحتياطي';
$backupDir = __DIR__ . '/../../backups';
$success = '';
$error = '';

// إنشاء مجلد النسخ الاحتياطي إذا لم يكن موجوداً
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// معالجة إنشاء نسخة احتياطية
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && verify_csrf()) {
    if ($_POST['action'] === 'backup') {
        try {
            $filename = 'backup_' . $company_id . '_' . date('Y-m-d_His') . '.sql';
            $filepath = $backupDir . '/' . $filename;
            
            // الحصول على جميع الجداول
            $tables = $db->fetchAll("SHOW TABLES");
            $sql = "-- MultiERP Database Backup\n";
            $sql .= "-- Company ID: $company_id\n";
            $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            foreach ($tables as $table) {
                $tableName = array_values($table)[0];
                
                // الحصول على بنية الجدول
                $createTable = $db->fetch("SHOW CREATE TABLE `$tableName`");
                $sql .= "DROP TABLE IF EXISTS `$tableName`;\n";
                $sql .= $createTable['Create Table'] . ";\n\n";
                
                // الحصول على البيانات
                $rows = $db->fetchAll("SELECT * FROM `$tableName`");
                if (!empty($rows)) {
                    $columns = array_keys($rows[0]);
                    foreach ($rows as $row) {
                        $values = array_map(function($v) use ($db) {
                            if ($v === null) return 'NULL';
                            return $db->getConnection()->quote($v);
                        }, array_values($row));
                        $sql .= "INSERT INTO `$tableName` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $sql .= "\n";
                }
            }
            
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            file_put_contents($filepath, $sql);
            $success = "تم إنشاء النسخة الاحتياطية بنجاح: $filename";
        } catch (Exception $e) {
            $error = "خطأ في إنشاء النسخة الاحتياطية: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'delete' && isset($_POST['file'])) {
        $file = basename($_POST['file']);
        $filepath = $backupDir . '/' . $file;
        if (file_exists($filepath) && strpos($file, 'backup_') === 0) {
            unlink($filepath);
            $success = "تم حذف النسخة الاحتياطية بنجاح";
        }
    }
}

// معالجة التنزيل
if (isset($_GET['download'])) {
    $file = basename($_GET['download']);
    $filepath = $backupDir . '/' . $file;
    if (file_exists($filepath) && strpos($file, 'backup_') === 0) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
}

// الحصول على قائمة النسخ الاحتياطية
$backups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if (strpos($file, 'backup_') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $filepath = $backupDir . '/' . $file;
            $backups[] = [
                'name' => $file,
                'size' => filesize($filepath),
                'date' => filemtime($filepath)
            ];
        }
    }
    // ترتيب حسب التاريخ (الأحدث أولاً)
    usort($backups, fn($a, $b) => $b['date'] - $a['date']);
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
                    <h1><i class="fas fa-database"></i> <?= $pageTitle ?></h1>
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
                        <a href="branches.php" class="submenu-item"><i class="fas fa-code-branch"></i><span>الفروع والمخازن</span></a>
                        <a href="users.php" class="submenu-item"><i class="fas fa-users"></i><span>المستخدمين</span></a>
                        <a href="roles.php" class="submenu-item"><i class="fas fa-user-shield"></i><span>الأدوار</span></a>
                        <a href="backup.php" class="submenu-item active"><i class="fas fa-database"></i><span>النسخ الاحتياطي</span></a>
                    </div>
                </div>

                <?php if ($success): ?><div class="alert alert-success mb-3"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger mb-3"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>

                <!-- إنشاء نسخة جديدة -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-plus-circle"></i> إنشاء نسخة احتياطية</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">سيتم إنشاء نسخة احتياطية كاملة من قاعدة البيانات بما في ذلك جميع الجداول والبيانات.</p>
                        <form method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="backup">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-download"></i> إنشاء نسخة احتياطية الآن
                            </button>
                        </form>
                    </div>
                </div>

                <!-- قائمة النسخ الاحتياطية -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history"></i> النسخ الاحتياطية السابقة</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>اسم الملف</th>
                                        <th>الحجم</th>
                                        <th>التاريخ</th>
                                        <th>إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($backups)): ?>
                                    <tr><td colspan="4" class="text-center text-muted p-3">لا توجد نسخ احتياطية</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($backups as $backup): ?>
                                    <tr>
                                        <td><i class="fas fa-file-code text-primary"></i> <?= htmlspecialchars($backup['name']) ?></td>
                                        <td><?= number_format($backup['size'] / 1024, 2) ?> KB</td>
                                        <td><?= date('Y-m-d H:i:s', $backup['date']) ?></td>
                                        <td>
                                            <a href="?download=<?= urlencode($backup['name']) ?>" class="btn btn-sm btn-outline" title="تنزيل">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذه النسخة؟');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="file" value="<?= htmlspecialchars($backup['name']) ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="حذف">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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

                <!-- ملاحظات -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h4><i class="fas fa-info-circle text-info"></i> ملاحظات هامة</h4>
                        <ul class="text-muted" style="margin-top: 10px;">
                            <li>يُنصح بإنشاء نسخة احتياطية يومياً أو أسبوعياً حسب حجم العمل</li>
                            <li>احتفظ بنسخ احتياطية في مكان آمن خارج الخادم</li>
                            <li>لاستعادة النسخة الاحتياطية، قم باستيراد ملف SQL في phpMyAdmin</li>
                        </ul>
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
