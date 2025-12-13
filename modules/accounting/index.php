<?php
/**
 * لوحة تحكم المحاسبة
 * Accounting Module - Dashboard
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

// التحقق من تفعيل الموديول
require_module($company['id'], 'accounting');

$pageTitle = 'المحاسبة';

// إحصائيات مالية سريعة
$stats = [
    'assets' => 0,
    'liabilities' => 0,
    'equity' => 0,
    'revenue' => 0,
    'expenses' => 0
];

try {
    // التأكد من وجود جدول الحسابات
    $db->getConnection()->exec("CREATE TABLE IF NOT EXISTS accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        code VARCHAR(20) NOT NULL,
        name VARCHAR(100) NOT NULL,
        type ENUM('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL,
        balance DECIMAL(15,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(company_id, code)
    )");

    $rows = $db->fetchAll("SELECT type, SUM(balance) as total FROM accounts WHERE company_id = ? GROUP BY type", [$company_id]);
    foreach ($rows as $row) {
        $stats[$row['type']] = $row['total'];
    }
} catch (Exception $e) {
    // ignore error if table doesn't exist
}

// Net Income = Revenue - Expenses
$netIncome = $stats['revenue'] - $stats['expenses'];

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
            background: var(--bg);
            border-radius: var(--radius-md);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid var(--border);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        .stat-info h3 { margin: 0; font-size: 0.9rem; color: var(--text-muted); }
        .stat-info p { margin: 4px 0 0; font-size: 1.5rem; font-weight: 700; color: var(--text); }
        
        .bg-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .bg-green { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .bg-red { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .bg-orange { background: linear-gradient(135deg, #f97316, #ea580c); }
        .bg-purple { background: linear-gradient(135deg, #a855f7, #9333ea); }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }
        .action-card {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 20px;
            text-align: center;
            color: var(--text);
            text-decoration: none;
            transition: all 0.2s;
        }
        .action-card:hover {
            border-color: var(--primary);
            background: var(--bg-hover);
        }
        .action-card i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 12px;
        }
        .action-card h3 { margin: 0; font-size: 1.1rem; }
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
                           class="nav-link <?= $module['slug'] === 'accounting' ? 'active' : '' ?>">
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
                    <h1><i class="fas fa-calculator"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <button class="menu-toggle-btn" onclick="toggleSidebar()" title="القائمة">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="add_entry.php" class="btn btn-primary"><i class="fas fa-plus"></i> إضافة قيد يومي</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item active"><i class="fas fa-home"></i><span>الرئيسية</span></a>
                        <a href="accounts.php" class="submenu-item"><i class="fas fa-sitemap"></i><span>دليل الحسابات</span></a>
                        <a href="entries.php" class="submenu-item"><i class="fas fa-book"></i><span>قيود اليومية</span></a>
                        <a href="reports.php" class="submenu-item"><i class="fas fa-file-invoice-dollar"></i><span>التقارير المالية</span></a>
                    </div>
                </div>

                <!-- البطاقات الإحصائية -->
                <div class="row">
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-blue"><i class="fas fa-building"></i></div>
                            <div class="stat-info">
                                <h3>إجمالي الأصول</h3>
                                <p><?= number_format($stats['assets'], 2) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-red"><i class="fas fa-hand-holding-usd"></i></div>
                            <div class="stat-info">
                                <h3>إجمالي الالتزامات</h3>
                                <p><?= number_format($stats['liabilities'], 2) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-green"><i class="fas fa-coins"></i></div>
                            <div class="stat-info">
                                <h3>الإيرادات</h3>
                                <p><?= number_format($stats['revenue'], 2) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-orange"><i class="fas fa-receipt"></i></div>
                            <div class="stat-info">
                                <h3>المصروفات</h3>
                                <p><?= number_format($stats['expenses'], 2) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-rocket"></i> إجراءات سريعة</h3>
                            </div>
                            <div class="card-body">
                                <div class="quick-actions">
                                    <a href="add_entry.php" class="action-card">
                                        <i class="fas fa-plus-circle"></i>
                                        <h3>إضافة قيد يومي</h3>
                                    </a>
                                    <a href="accounts.php" class="action-card">
                                        <i class="fas fa-sitemap"></i>
                                        <h3>إدارة الدليل المحاسبي</h3>
                                    </a>
                                    <a href="reports.php?type=trial_balance" class="action-card">
                                        <i class="fas fa-balance-scale"></i>
                                        <h3>ميزان المراجعة</h3>
                                    </a>
                                    <a href="reports.php?type=income_statement" class="action-card">
                                        <i class="fas fa-chart-line"></i>
                                        <h3>قائمة الدخل</h3>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card bg-surface text-center h-100" style="display: flex; flex-direction: column; justify-content: center; align-items: center;">
                            <div style="font-size: 3rem; color: var(--primary); margin-bottom: 20px;">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <h3 class="mb-2">صافي الدخل</h3>
                            <h2 class="<?= $netIncome >= 0 ? 'text-success' : 'text-danger' ?>" style="font-size: 2.5rem; font-weight: bold;">
                                <?= number_format($netIncome, 2) ?>
                            </h2>
                            <p class="text-muted mt-2">الإيرادات - المصروفات</p>
                        </div>
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
