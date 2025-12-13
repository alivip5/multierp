<?php
/**
 * صفحة المبيعات - قائمة الفواتير
 * Sales Module - Invoices List
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
$user = $db->fetch("SELECT u.*, r.name as role_slug, r.name_ar as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?", [$_SESSION['user_id']]);
$company = $db->fetch("SELECT * FROM companies WHERE id = ?", [$_SESSION['company_id'] ?? 1]);

// التأكد من وجود بيانات الدور في الجلسة
if (!isset($_SESSION['role_id']) && $user) {
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_slug'];
}

// التحقق من صلاحية الوصول للموديول
requireModuleAccess('sales');

// التحقق من تفعيل الموديول
$moduleEnabled = $db->fetch(
    "SELECT cm.status FROM company_modules cm JOIN modules m ON cm.module_id = m.id 
     WHERE cm.company_id = ? AND m.slug = 'sales'",
    [$company['id']]
);

if (!$moduleEnabled || $moduleEnabled['status'] !== 'enabled') {
    header('Location: ../../pages/dashboard.php?error=module_disabled');
    exit;
}

// الفلترة
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

$where = "si.company_id = ?";
$params = [$company['id']];

if (!empty($_GET['status'])) {
    $where .= " AND si.payment_status = ?";
    $params[] = $_GET['status'];
}

if (!empty($_GET['search'])) {
    $where .= " AND (si.invoice_number LIKE ? OR c.name LIKE ?)";
    $search = "%{$_GET['search']}%";
    $params[] = $search;
    $params[] = $search;
}

$total = $db->fetch("SELECT COUNT(*) as count FROM sales_invoices si LEFT JOIN customers c ON si.customer_id = c.id WHERE $where", $params)['count'];
$invoices = $db->fetchAll(
    "SELECT si.*, c.name as customer_name 
     FROM sales_invoices si 
     LEFT JOIN customers c ON si.customer_id = c.id 
     WHERE $where 
     ORDER BY si.created_at DESC 
     LIMIT $limit OFFSET $offset",
    $params
);

$totalPages = ceil($total / $limit);

// الموديولات المتاحة للقائمة الجانبية بناءً على الصلاحيات
$enabledModules = getSidebarItems($company['id'], $_SESSION['user_id']);

$pageTitle = 'المبيعات';
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
                           class="nav-link <?= $module['slug'] === 'sales' ? 'active' : '' ?>">
                            <i class="<?= $module['icon'] ?>"></i>
                            <span><?= htmlspecialchars($module['name_ar']) ?></span>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1><i class="fas fa-shopping-cart"></i> <?= $pageTitle ?></h1>
                    <p>إدارة فواتير المبيعات</p>
                </div>
                <div class="header-actions">
                    <?php if (can('sales.create')): ?>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> فاتورة جديدة
                    </a>
                    <a href="customers.php?action=add" class="btn btn-outline">
                        <i class="fas fa-user-plus"></i> إضافة عميل
                    </a>
                    <?php endif; ?>
                </div>
            </header>
            
            <!-- القائمة الفرعية -->
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
            
            <div class="page-content">
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item active"><i class="fas fa-list"></i><span>الفواتير</span></a>
                        <a href="add.php" class="submenu-item"><i class="fas fa-plus"></i><span>فاتورة جديدة</span></a>
                        <a href="customers.php" class="submenu-item"><i class="fas fa-users"></i><span>العملاء</span></a>
                    </div>
                </div>
                <!-- الفلاتر -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-2 align-center flex-wrap">
                            <input type="text" name="search" class="form-control" style="max-width: 250px;" 
                                   placeholder="بحث..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            <select name="status" class="form-control form-select" style="max-width: 150px;">
                                <option value="">كل الحالات</option>
                                <option value="paid" <?= ($_GET['status'] ?? '') === 'paid' ? 'selected' : '' ?>>مدفوعة</option>
                                <option value="partial" <?= ($_GET['status'] ?? '') === 'partial' ? 'selected' : '' ?>>جزئي</option>
                                <option value="unpaid" <?= ($_GET['status'] ?? '') === 'unpaid' ? 'selected' : '' ?>>غير مدفوعة</option>
                            </select>
                            <button type="submit" class="btn btn-outline"><i class="fas fa-search"></i> بحث</button>
                            <?php if (!empty($_GET['search']) || !empty($_GET['status'])): ?>
                            <a href="index.php" class="btn btn-outline"><i class="fas fa-times"></i> مسح</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- قائمة الفواتير -->
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>رقم الفاتورة</th>
                                        <th>العميل</th>
                                        <th>التاريخ</th>
                                        <th>المبلغ</th>
                                        <th>المدفوع</th>
                                        <th>الحالة</th>
                                        <th>إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($invoices)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted p-3">
                                            <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                            لا توجد فواتير
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($invoices as $invoice): ?>
                                    <tr>
                                        <td>
                                            <a href="view.php?id=<?= $invoice['id'] ?>" class="text-primary">
                                                <?= htmlspecialchars($invoice['invoice_number']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($invoice['customer_name'] ?? 'عميل نقدي') ?></td>
                                        <td><?= date('Y/m/d', strtotime($invoice['invoice_date'])) ?></td>
                                        <td><?= number_format($invoice['total'], 2) ?> <?= $company['currency_symbol'] ?></td>
                                        <td><?= number_format($invoice['paid_amount'], 2) ?> <?= $company['currency_symbol'] ?></td>
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
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="view.php?id=<?= $invoice['id'] ?>" class="btn btn-sm btn-outline" title="عرض">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="print.php?id=<?= $invoice['id'] ?>" class="btn btn-sm btn-outline" title="طباعة" target="_blank">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                                <?php if ($invoice['payment_status'] !== 'paid'): ?>
                                                <a href="payment.php?id=<?= $invoice['id'] ?>" class="btn btn-sm btn-success" title="تسديد">
                                                    <i class="fas fa-money-bill"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                    <div class="card-footer d-flex justify-between align-center">
                        <div class="text-muted">
                            عرض <?= count($invoices) ?> من <?= $total ?> فاتورة
                        </div>
                        <div class="d-flex gap-1">
                            <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&<?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)) ?>" class="btn btn-sm btn-outline">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            <?php endif; ?>
                            
                            <span class="btn btn-sm" style="background: var(--primary); color: white;">
                                <?= $page ?> / <?= $totalPages ?>
                            </span>
                            
                            <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>&<?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)) ?>" class="btn btn-sm btn-outline">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
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
