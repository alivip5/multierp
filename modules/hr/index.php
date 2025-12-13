<?php
/**
 * لوحة تحكم الموارد البشرية
 * HR Module - Dashboard
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../api/config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/SidebarHelper.php';
require_once __DIR__ . '/../../includes/Auth.php';

$db = Database::getInstance();
$company_id = $_SESSION['company_id'] ?? 1;
$user = $db->fetch("SELECT u.*, r.name_ar as role_name, r.name as role_slug FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?", [$_SESSION['user_id']]);
$company = $db->fetch("SELECT * FROM companies WHERE id = ?", [$company_id]);

if (!isset($_SESSION['role_id']) && $user) {
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_slug'];
}

$enabledModules = getSidebarItems($company['id'], $_SESSION['user_id']);
require_module($company['id'], 'hr');

$pageTitle = 'الموارد البشرية';

// إحصائيات سريعة
$stats = [
    'employees' => $db->fetchColumn("SELECT COUNT(*) FROM employees WHERE company_id = ? AND status = 'active'", [$company['id']]),
    'departments' => $db->fetchColumn("SELECT COUNT(*) FROM departments WHERE company_id = ?", [$company['id']]),
    // افتراض وجود جدول للإجازات، إذا لم يوجد نعرض 0
    'on_leave' => $db->fetchColumn("SELECT COUNT(*) FROM employees WHERE company_id = ? AND status = 'on_leave'", [$company['id']]),
];

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
        .stat-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .action-card {
            background: var(--surface);
            padding: 20px;
            border-radius: var(--radius-lg);
            text-align: center;
            text-decoration: none;
            color: var(--text);
            transition: all 0.3s;
            border: 1px solid var(--border);
        }
        .action-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .action-icon {
            font-size: 2rem;
            margin-bottom: 15px;
            color: var(--primary);
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
                           class="nav-link <?= $module['slug'] === 'hr' ? 'active' : '' ?>">
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
                    <h1><i class="fas fa-users-cog"></i> <?= $pageTitle ?></h1>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item active"><i class="fas fa-home"></i><span>الرئيسية</span></a>
                        <a href="employees.php" class="submenu-item"><i class="fas fa-users"></i><span>الموظفين</span></a>
                        <a href="departments.php" class="submenu-item"><i class="fas fa-sitemap"></i><span>الأقسام</span></a>
                    </div>
                </div>

                <!-- الإحصائيات -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon bg-primary-soft text-primary">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <h3 class="h2 mb-0"><?= number_format($stats['employees']) ?></h3>
                                <div class="text-muted">موظف نشط</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon bg-info-soft text-info">
                                <i class="fas fa-sitemap"></i>
                            </div>
                            <div>
                                <h3 class="h2 mb-0"><?= number_format($stats['departments']) ?></h3>
                                <div class="text-muted">قسم</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon bg-warning-soft text-warning">
                                <i class="fas fa-plane-departure"></i>
                            </div>
                            <div>
                                <h3 class="h2 mb-0"><?= number_format($stats['on_leave']) ?></h3>
                                <div class="text-muted">في إجازة</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- روابط سريعة -->
                <h3 class="mb-3">وصول سريع</h3>
                <div class="quick-actions">
                    <a href="add_employee.php" class="action-card">
                        <div class="action-icon"><i class="fas fa-user-plus"></i></div>
                        <h4>إضافة موظف</h4>
                        <p class="text-muted text-sm">تسجيل موظف جديد في النظام</p>
                    </a>
                    <a href="employees.php" class="action-card">
                        <div class="action-icon"><i class="fas fa-users"></i></div>
                        <h4>دليل الموظفين</h4>
                        <p class="text-muted text-sm">عرض وإدارة بيانات الموظفين</p>
                    </a>
                    <a href="departments.php" class="action-card">
                        <div class="action-icon"><i class="fas fa-network-wired"></i></div>
                        <h4>هيكل الشركة</h4>
                        <p class="text-muted text-sm">إدارة الأقسام والهيكل التنظيمي</p>
                    </a>
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
