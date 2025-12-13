<?php
/**
 * التقارير المالية
 * Accounting Module - Financial Reports
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/SidebarHelper.php';

$db = Database::getInstance();
$company_id = $_SESSION['company_id'] ?? 1;
$user = $db->fetch("SELECT u.*, r.name_ar as role_name, r.name as role_slug FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?", [$_SESSION['user_id']]);
$company = $db->fetch("SELECT * FROM companies WHERE id = ?", [$company_id]);

// التأكد من وجود بيانات الدور في الجلسة
if (!isset($_SESSION['role_id']) && $user) {
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_slug'];
}

$enabledModules = getSidebarItems($company['id'], $_SESSION['user_id']);
require_module($company['id'], 'accounting');

$pageTitle = 'التقارير المالية';

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
        .report-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--text);
        }
        .report-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .report-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 15px;
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
                    <h1><i class="fas fa-chart-line"></i> <?= $pageTitle ?></h1>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item"><i class="fas fa-home"></i><span>الرئيسية</span></a>
                        <a href="accounts.php" class="submenu-item"><i class="fas fa-sitemap"></i><span>دليل الحسابات</span></a>
                        <a href="entries.php" class="submenu-item"><i class="fas fa-book"></i><span>قيود اليومية</span></a>
                        <a href="reports.php" class="submenu-item active"><i class="fas fa-file-invoice-dollar"></i><span>التقارير المالية</span></a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 mb-4">
                        <a href="../reports/profit_loss_report.php" class="report-card">
                            <div class="report-icon"><i class="fas fa-file-contract"></i></div>
                            <h3>قائمة الدخل</h3>
                            <p class="text-sm text-muted">تقرير الأرباح والخسائر خلال فترة زمنية</p>
                        </a>
                    </div>
                    <div class="col-md-3 mb-4">
                        <a href="../reports/balance_sheet.php" class="report-card">
                            <div class="report-icon"><i class="fas fa-balance-scale"></i></div>
                            <h3>الميزانية العمومية</h3>
                            <p class="text-sm text-muted">المركز المالي للشركة (أصول، خصوم، حقوق ملكية)</p>
                        </a>
                    </div>
                    <div class="col-md-3 mb-4">
                        <a href="../reports/trial_balance.php" class="report-card">
                            <div class="report-icon"><i class="fas fa-table"></i></div>
                            <h3>ميزان المراجعة</h3>
                            <p class="text-sm text-muted">كشف أرصدة جميع الحسابات (مدين/دائن)</p>
                        </a>
                    </div>
                    <div class="col-md-3 mb-4">
                        <a href="../reports/tax_report.php" class="report-card">
                            <div class="report-icon"><i class="fas fa-hand-holding-usd"></i></div>
                            <h3>الإقرار الضريبي</h3>
                            <p class="text-sm text-muted">تقرير ضريبة القيمة المضافة</p>
                        </a>
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
