<?php
/**
 * صفحة التقارير الرئيسية
 * Reports Module - Main Page
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../api/config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Security.php';
require_once __DIR__ . '/../../includes/SidebarHelper.php';

$db = Database::getInstance();
$company_id = $_SESSION['company_id'] ?? 1;
$user = $db->fetch("SELECT u.*, r.name as role_slug, r.name_ar as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?", [$_SESSION['user_id']]);
$company = $db->fetch("SELECT * FROM companies WHERE id = ?", [$company_id]);

// التأكد من وجود بيانات الدور في الجلسة
if (!isset($_SESSION['role_id']) && $user) {
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_slug'];
}

// الموديولات للقائمة الجانبية
$enabledModules = getSidebarItems($company_id, $_SESSION['user_id']);

$pageTitle = 'التقارير';
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
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .report-card {
            background: var(--bg-surface);
            border-radius: var(--radius-lg);
            padding: 24px;
            border: 1px solid var(--border);
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .report-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border-color: var(--primary);
        }
        .report-icon {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 16px;
        }
        .report-icon.sales { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .report-icon.purchases { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .report-icon.inventory { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .report-icon.accounting { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .report-icon.hr { background: linear-gradient(135deg, #ec4899, #db2777); }
        .report-icon.customers { background: linear-gradient(135deg, #06b6d4, #0891b2); }
        .report-title { font-size: 1.1rem; font-weight: 600; margin-bottom: 8px; }
        .report-desc { color: var(--text-muted); font-size: 0.9rem; }
        .report-section { margin-bottom: 30px; }
        .report-section-title { font-size: 1.2rem; font-weight: 600; margin-bottom: 16px; color: var(--text-primary); }
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
                           class="nav-link <?= $module['slug'] === 'reports' ? 'active' : '' ?>">
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
                    <h1><i class="fas fa-chart-bar"></i> <?= $pageTitle ?></h1>
                    <p>اختر التقرير المطلوب</p>
                </div>
            </header>

            <div class="page-content">
                <!-- تقارير المبيعات -->
                <div class="report-section">
                    <div class="report-section-title"><i class="fas fa-shopping-cart text-success"></i> تقارير المبيعات</div>
                    <div class="report-grid">
                        <a href="sales_report.php" class="report-card">
                            <div class="report-icon sales"><i class="fas fa-shopping-cart"></i></div>
                            <div class="report-title">تقرير المبيعات</div>
                            <div class="report-desc">ملخص المبيعات والإيرادات حسب الفترة</div>
                        </a>

                        <a href="customers_report.php" class="report-card">
                            <div class="report-icon customers"><i class="fas fa-users"></i></div>
                            <div class="report-title">تقرير العملاء</div>
                            <div class="report-desc">تحليل العملاء والمبالغ المستحقة</div>
                        </a>

                        <a href="customer_debt_report.php" class="report-card">
                            <div class="report-icon accounting"><i class="fas fa-user-clock"></i></div>
                            <div class="report-title">مديونية العملاء</div>
                            <div class="report-desc">تقرير المديونية مع عمر الدين وتصنيف المتأخرات</div>
                        </a>

                        <a href="sales_agent_report.php" class="report-card">
                            <div class="report-icon hr"><i class="fas fa-user-tie"></i></div>
                            <div class="report-title">مبيعات المندوبين</div>
                            <div class="report-desc">أداء مندوبي التعاقد والعمولات المستحقة</div>
                        </a>
                    </div>
                </div>

                <!-- تقارير المخزون -->
                <div class="report-section">
                    <div class="report-section-title"><i class="fas fa-boxes text-warning"></i> تقارير المخزون</div>
                    <div class="report-grid">
                        <a href="inventory_report.php" class="report-card">
                            <div class="report-icon inventory"><i class="fas fa-boxes"></i></div>
                            <div class="report-title">تقرير المخزون</div>
                            <div class="report-desc">حالة المخزون والمنتجات منخفضة الكمية</div>
                        </a>
                    </div>
                </div>

                <!-- تقارير المشتريات -->
                <div class="report-section">
                    <div class="report-section-title"><i class="fas fa-truck text-info"></i> تقارير المشتريات</div>
                    <div class="report-grid">
                        <a href="purchases_report.php" class="report-card">
                            <div class="report-icon purchases"><i class="fas fa-truck"></i></div>
                            <div class="report-title">تقرير المشتريات</div>
                            <div class="report-desc">ملخص المشتريات حسب الفترة والمورد</div>
                        </a>
                    </div>
                </div>

                <!-- تقارير مالية -->
                <div class="report-section">
                    <div class="report-section-title"><i class="fas fa-balance-scale text-purple"></i> التقارير المالية</div>
                    <div class="report-grid">
                        <a href="profit_loss_report.php" class="report-card">
                            <div class="report-icon accounting"><i class="fas fa-balance-scale"></i></div>
                            <div class="report-title">تقرير الأرباح والخسائر</div>
                            <div class="report-desc">قائمة الدخل والتحليل المالي</div>
                        </a>
                    </div>
                </div>

                <!-- تقارير الموظفين -->
                <div class="report-section">
                    <div class="report-section-title"><i class="fas fa-user-tie text-pink"></i> تقارير الموظفين</div>
                    <div class="report-grid">
                        <a href="employees_report.php" class="report-card">
                            <div class="report-icon hr"><i class="fas fa-user-tie"></i></div>
                            <div class="report-title">تقرير الموظفين</div>
                            <div class="report-desc">إحصائيات الموظفين والرواتب</div>
                        </a>
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
