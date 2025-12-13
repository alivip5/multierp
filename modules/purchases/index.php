<?php
/**
 * قائمة فواتير المشتريات
 * Purchase Invoices List
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

// Fetch invoices
$invoices = $db->fetchAll(
    "SELECT pi.*, s.name as supplier_name 
     FROM purchase_invoices pi 
     LEFT JOIN suppliers s ON pi.supplier_id = s.id 
     WHERE pi.company_id = ? 
     ORDER BY pi.created_at DESC", 
    [$company_id]
);

$pageTitle = 'فواتير المشتريات';
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
                        <a href="<?= $module['slug'] === 'dashboard' ? '../../pages/dashboard.php' : ($module['slug'] === 'settings' ? '../settings/branches.php' : '../' . $module['slug'] . '/index.php') ?>" 
                           class="nav-link <?= $module['slug'] === 'purchases' ? 'active' : '' ?>">
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
                    <h1><i class="fas fa-file-invoice-dollar"></i> <?= $pageTitle ?></h1>
                    <p>إدارة فواتير المشتريات</p>
                </div>
                <div class="header-actions">
                    <button class="menu-toggle-btn" onclick="toggleSidebar()" title="القائمة">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> فاتورة جديدة
                    </a>
                    <a href="suppliers.php?action=add" class="btn btn-outline">
                        <i class="fas fa-truck"></i> إضافة مورد
                    </a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item active"><i class="fas fa-list"></i><span>الفواتير</span></a>
                        <a href="add.php" class="submenu-item"><i class="fas fa-plus"></i><span>فاتورة جديدة</span></a>
                        <a href="suppliers.php" class="submenu-item"><i class="fas fa-truck"></i><span>الموردين</span></a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>رقم الفاتورة</th>
                                        <th>المورد</th>
                                        <th>التاريخ</th>
                                        <th>الإجمالي</th>
                                        <th>الحالة</th>
                                        <th>إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($invoices)): ?>
                                    <tr><td colspan="6" class="text-center p-3 text-muted">لا توجد فواتير</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($invoices as $i): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($i['invoice_number']) ?></td>
                                        <td><?= htmlspecialchars($i['supplier_name'] ?? '-') ?></td>
                                        <td><?= $i['invoice_date'] ?></td>
                                        <td><?= number_format($i['total'], 2) ?> <?= $company['currency_symbol'] ?? 'ر.س' ?></td>
                                        <td>
                                            <span class="badge badge-<?= match($i['status']) { 'received' => 'success', 'pending' => 'warning', default => 'secondary' } ?>">
                                                <?= match($i['status']) { 'received' => 'مستلم', 'pending' => 'معلق', 'draft' => 'مسودة', default => 'ملغي' } ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view.php?id=<?= $i['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
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
