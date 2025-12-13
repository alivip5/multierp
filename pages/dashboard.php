<?php
/**
 * لوحة التحكم الرئيسية
 * Main Dashboard
 */

session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../api/config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../includes/SidebarHelper.php';

// الحصول على بيانات المستخدم
$db = Database::getInstance();
$user = $db->fetch("SELECT u.*, r.name as role_slug, r.name_ar as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?", [$_SESSION['user_id']]);
$company = $db->fetch("SELECT * FROM companies WHERE id = ?", [$_SESSION['company_id'] ?? 1]);

// التأكد من وجود بيانات الدور في الجلسة
if (!isset($_SESSION['role_id']) && $user) {
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_slug'];
}

// الحصول على الموديولات المتاحة بناءً على الصلاحيات
$modules = getSidebarItems($company['id'] ?? 1, $_SESSION['user_id']);

// إحصائيات سريعة
$stats = [
    'sales_today' => $db->fetch("SELECT COALESCE(SUM(total), 0) as total FROM sales_invoices WHERE company_id = ? AND DATE(created_at) = CURDATE()", [$company['id'] ?? 1])['total'] ?? 0,
    'sales_month' => $db->fetch("SELECT COALESCE(SUM(total), 0) as total FROM sales_invoices WHERE company_id = ? AND MONTH(created_at) = MONTH(CURDATE())", [$company['id'] ?? 1])['total'] ?? 0,
    'customers' => $db->fetch("SELECT COUNT(*) as count FROM customers WHERE company_id = ? AND status = 'active'", [$company['id'] ?? 1])['count'] ?? 0,
    'products' => $db->fetch("SELECT COUNT(*) as count FROM products WHERE company_id = ? AND is_active = 1", [$company['id'] ?? 1])['count'] ?? 0,
    'low_stock' => $db->fetch("SELECT COUNT(*) as count FROM products p WHERE p.company_id = ? AND p.track_inventory = 1 AND (SELECT COALESCE(SUM(ps.quantity), 0) FROM product_stock ps WHERE ps.product_id = p.id) <= p.min_stock", [$company['id'] ?? 1])['count'] ?? 0,
    'invoices_pending' => $db->fetch("SELECT COUNT(*) as count FROM sales_invoices WHERE company_id = ? AND payment_status = 'unpaid'", [$company['id'] ?? 1])['count'] ?? 0,
];

// آخر الفواتير
$recentInvoices = $db->fetchAll(
    "SELECT si.*, c.name as customer_name FROM sales_invoices si 
     LEFT JOIN customers c ON si.customer_id = c.id 
     WHERE si.company_id = ? ORDER BY si.created_at DESC LIMIT 5",
    [$company['id'] ?? 1]
);

$pageTitle = 'لوحة التحكم';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="<?= $user['theme'] ?? 'dark' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= htmlspecialchars($company['name'] ?? 'نظام ERP') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-building"></i>
                </div>
                <span class="sidebar-brand"><?= htmlspecialchars($company['name'] ?? 'نظام ERP') ?></span>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">القائمة الرئيسية</div>
                    <?php foreach ($modules as $module): ?>
                    <div class="nav-item">
                        <a href="<?= $module['slug'] === 'dashboard' ? 'dashboard.php' : '../modules/' . $module['slug'] . '/index.php' ?>" 
                           class="nav-link <?= $module['slug'] === 'dashboard' ? 'active' : '' ?>">
                            <i class="<?= $module['icon'] ?>"></i>
                            <span><?= htmlspecialchars($module['name_ar']) ?></span>
                            <?php if ($module['slug'] === 'inventory' && $stats['low_stock'] > 0): ?>
                            <span class="nav-badge"><?= $stats['low_stock'] ?></span>
                            <?php endif; ?>
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
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-title">
                    <h1><?= $pageTitle ?></h1>
                    <p>مرحباً بك، <?= htmlspecialchars($user['full_name']) ?></p>
                </div>
                
                <div class="header-actions">
                    <button class="menu-toggle-btn" onclick="toggleSidebar()" title="القائمة">
                        <i class="fas fa-bars"></i>
                    </button>
                    <button class="header-btn" onclick="toggleTheme()" title="تبديل الثيم">
                        <i class="fas fa-moon" id="themeIcon"></i>
                    </button>
                    
                    <button class="header-btn" title="الإشعارات">
                        <i class="fas fa-bell"></i>
                        <span class="badge">3</span>
                    </button>
                    
                    <div class="user-menu" onclick="toggleUserMenu()">
                        <div class="user-avatar">
                            <?= mb_substr($user['full_name'], 0, 1, 'UTF-8') ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
                            <div class="user-role"><?= htmlspecialchars($user['role_name']) ?></div>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                        
                        <!-- Dropdown Menu -->
                        <div class="user-dropdown" id="userDropdown">
                            <a href="../modules/settings/profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i> الملف الشخصي
                            </a>
                            <a href="../modules/settings/index.php" class="dropdown-item">
                                <i class="fas fa-cog"></i> الإعدادات
                            </a>
                            <a href="../modules/settings/backup.php" class="dropdown-item">
                                <i class="fas fa-database"></i> النسخ الاحتياطي
                            </a>
                            <hr style="margin: 8px 0; border-color: var(--border);">
                            <a href="logout.php" class="dropdown-item text-danger">
                                <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <div class="page-content">
                <!-- Stats Cards -->
                <div class="row mb-3">
                    <div class="col-3">
                        <div class="stat-card animate-slide" style="animation-delay: 0.1s">
                            <div class="stat-icon primary">
                                <i class="fas fa-coins"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">مبيعات اليوم</div>
                                <div class="stat-value"><?= number_format($stats['sales_today'], 2) ?></div>
                                <div class="stat-change up">
                                    <i class="fas fa-arrow-up"></i>
                                    <span><?= $company['currency_symbol'] ?? 'ر.س' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-3">
                        <div class="stat-card animate-slide" style="animation-delay: 0.2s">
                            <div class="stat-icon success">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">مبيعات الشهر</div>
                                <div class="stat-value"><?= number_format($stats['sales_month'], 2) ?></div>
                                <div class="stat-change up">
                                    <i class="fas fa-arrow-up"></i>
                                    <span><?= $company['currency_symbol'] ?? 'ر.س' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-3">
                        <div class="stat-card animate-slide" style="animation-delay: 0.3s">
                            <div class="stat-icon info">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">العملاء</div>
                                <div class="stat-value"><?= number_format($stats['customers']) ?></div>
                                <div class="stat-change">
                                    <span>عميل نشط</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-3">
                        <div class="stat-card animate-slide" style="animation-delay: 0.4s">
                            <div class="stat-icon warning">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">المنتجات</div>
                                <div class="stat-value"><?= number_format($stats['products']) ?></div>
                                <?php if ($stats['low_stock'] > 0): ?>
                                <div class="stat-change down">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span><?= $stats['low_stock'] ?> منخفض المخزون</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card animate-slide" style="animation-delay: 0.5s">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-bolt text-warning"></i>
                                    إجراءات سريعة
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="d-flex gap-2 flex-wrap">
                                    <?php if (can('sales.create')): ?>
                                    <a href="../modules/sales/add.php" class="btn btn-primary">
                                        <i class="fas fa-plus"></i>
                                        فاتورة جديدة
                                    </a>
                                    <?php endif; ?>
                                    <?php if (can('inventory.create')): ?>
                                    <a href="../modules/inventory/products.php?action=add" class="btn btn-outline">
                                        <i class="fas fa-box"></i>
                                        إضافة منتج
                                    </a>
                                    <?php endif; ?>
                                    <?php if (can('sales.create')): ?>
                                    <a href="../modules/sales/customers.php?action=add" class="btn btn-outline">
                                        <i class="fas fa-user-plus"></i>
                                        إضافة عميل
                                    </a>
                                    <?php endif; ?>
                                    <?php if (can('purchases.create')): ?>
                                    <a href="../modules/purchases/add.php" class="btn btn-outline">
                                        <i class="fas fa-truck"></i>
                                        فاتورة شراء
                                    </a>
                                    <?php endif; ?>
                                    <?php if (can('accounting.create')): ?>
                                    <a href="../modules/accounting/entries.php?action=add" class="btn btn-outline">
                                        <i class="fas fa-file-invoice"></i>
                                        قيد يومية
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Invoices & Alerts -->
                <div class="row">
                    <div class="col-6">
                        <div class="card animate-slide" style="animation-delay: 0.6s">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-file-invoice text-primary"></i>
                                    آخر الفواتير
                                </h3>
                                <a href="../modules/sales/index.php" class="btn btn-sm btn-outline">
                                    عرض الكل
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>رقم الفاتورة</th>
                                                <th>العميل</th>
                                                <th>المبلغ</th>
                                                <th>الحالة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recentInvoices)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted p-3">
                                                    لا توجد فواتير حتى الآن
                                                </td>
                                            </tr>
                                            <?php else: ?>
                                            <?php foreach ($recentInvoices as $invoice): ?>
                                            <tr>
                                                <td>
                                                    <a href="../modules/sales/view.php?id=<?= $invoice['id'] ?>">
                                                        <?= htmlspecialchars($invoice['invoice_number']) ?>
                                                    </a>
                                                </td>
                                                <td><?= htmlspecialchars($invoice['customer_name'] ?? 'عميل نقدي') ?></td>
                                                <td><?= number_format($invoice['total'], 2) ?> <?= $company['currency_symbol'] ?? 'ر.س' ?></td>
                                                <td>
                                                    <?php 
                                                    $statusClass = match($invoice['payment_status']) {
                                                        'paid' => 'success',
                                                        'partial' => 'warning',
                                                        default => 'danger'
                                                    };
                                                    $statusText = match($invoice['payment_status']) {
                                                        'paid' => 'مدفوعة',
                                                        'partial' => 'جزئي',
                                                        default => 'غير مدفوعة'
                                                    };
                                                    ?>
                                                    <span class="badge badge-<?= $statusClass ?>"><?= $statusText ?></span>
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
                    
                    <div class="col-6">
                        <div class="card animate-slide" style="animation-delay: 0.7s">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-bell text-warning"></i>
                                    التنبيهات
                                </h3>
                            </div>
                            <div class="card-body">
                                <?php if ($stats['low_stock'] > 0): ?>
                                <div class="d-flex align-center gap-2 mb-2 p-2" style="background: rgba(239, 68, 68, 0.1); border-radius: var(--radius-md);">
                                    <i class="fas fa-exclamation-circle text-danger"></i>
                                    <span><?= $stats['low_stock'] ?> منتج بحاجة لإعادة طلب</span>
                                    <a href="../modules/inventory/low-stock.php" class="btn btn-sm btn-danger" style="margin-right: auto;">عرض</a>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($stats['invoices_pending'] > 0): ?>
                                <div class="d-flex align-center gap-2 mb-2 p-2" style="background: rgba(245, 158, 11, 0.1); border-radius: var(--radius-md);">
                                    <i class="fas fa-clock text-warning"></i>
                                    <span><?= $stats['invoices_pending'] ?> فاتورة غير مدفوعة</span>
                                    <a href="../modules/sales/index.php?status=unpaid" class="btn btn-sm btn-warning" style="margin-right: auto;">عرض</a>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($stats['low_stock'] == 0 && $stats['invoices_pending'] == 0): ?>
                                <div class="text-center text-muted p-3">
                                    <i class="fas fa-check-circle text-success" style="font-size: 2rem;"></i>
                                    <p class="mt-2">لا توجد تنبيهات حالياً</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Toggle Sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (window.innerWidth < 992) {
                sidebar.classList.toggle('show');
                if (overlay) overlay.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('sidebarCollapsed', 
                    sidebar.classList.contains('collapsed'));
            }
        }
        
        // Toggle Theme
        function toggleTheme() {
            const html = document.documentElement;
            const icon = document.getElementById('themeIcon');
            const newTheme = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            icon.className = newTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
            localStorage.setItem('theme', newTheme);
            
            // Update server
            fetch('../api/v1/settings/theme.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ theme: newTheme })
            });
        }
        
        // Toggle User Menu
        function toggleUserMenu() {
            event.stopPropagation();
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('userDropdown');
            const userMenu = document.querySelector('.user-menu');
            if (dropdown && !userMenu.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
        
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            // Restore sidebar state
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                document.getElementById('sidebar').classList.add('collapsed');
            }
            
            // Update theme icon
            const theme = document.documentElement.getAttribute('data-theme');
            document.getElementById('themeIcon').className = theme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
        });
    </script>
</body>
</html>
