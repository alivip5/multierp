<?php
/**
 * صفحة أوامر الإنتاج
 * Production Module - Orders List
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/SidebarHelper.php';

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

$pageTitle = 'أوامر الإنتاج';

// فلترة
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// بناء الاستعلام
$where = "po.company_id = ?";
$params = [$company_id];

if ($status) {
    $where .= " AND po.status = ?";
    $params[] = $status;
}

if ($search) {
    $where .= " AND (po.order_number LIKE ? OR p.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$orders = [];
try {
    $orders = $db->fetchAll(
        "SELECT po.*, p.name as product_name 
         FROM production_orders po 
         LEFT JOIN products p ON po.product_id = p.id 
         WHERE $where 
         ORDER BY po.created_at DESC",
        $params
    );
} catch (Exception $e) {}

$statusLabels = [
    'draft' => 'مسودة',
    'pending' => 'قيد الانتظار',
    'in_progress' => 'قيد التنفيذ',
    'completed' => 'مكتمل',
    'cancelled' => 'ملغي'
];

$statusColors = [
    'draft' => 'secondary',
    'pending' => 'warning',
    'in_progress' => 'info',
    'completed' => 'success',
    'cancelled' => 'danger'
];

$priorityLabels = ['low' => 'منخفضة', 'normal' => 'عادية', 'high' => 'عالية', 'urgent' => 'عاجلة'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="<?= $user['theme'] ?? 'dark' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
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
                        <a href="<?= $module['slug'] === 'dashboard' ? '../../pages/dashboard.php' : ($module['slug'] === 'settings' ? '../settings/branches.php' : '../' . $module['slug'] . '/index.php') ?>" 
                           class="nav-link <?= $module['slug'] === 'production' ? 'active' : '' ?>">
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
                    <h1><i class="fas fa-clipboard-list"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <a href="add_order.php" class="btn btn-primary"><i class="fas fa-plus"></i> أمر جديد</a>
                    <a href="index.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item"><i class="fas fa-industry"></i><span>لوحة الإنتاج</span></a>
                        <a href="orders.php" class="submenu-item active"><i class="fas fa-clipboard-list"></i><span>أوامر الإنتاج</span></a>
                        <a href="bom.php" class="submenu-item"><i class="fas fa-sitemap"></i><span>قوائم المواد</span></a>
                    </div>
                </div>

                <!-- فلتر -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-2 align-center flex-wrap">
                            <input type="text" name="search" class="form-control" placeholder="بحث..." value="<?= htmlspecialchars($search) ?>" style="max-width: 200px;">
                            <select name="status" class="form-control" style="max-width: 150px;">
                                <option value="">جميع الحالات</option>
                                <?php foreach ($statusLabels as $k => $v): ?>
                                <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-outline"><i class="fas fa-filter"></i></button>
                        </form>
                    </div>
                </div>

                <!-- قائمة الأوامر -->
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>رقم الأمر</th>
                                        <th>المنتج</th>
                                        <th>الكمية المطلوبة</th>
                                        <th>الكمية المنجزة</th>
                                        <th>الأولوية</th>
                                        <th>تاريخ الاستحقاق</th>
                                        <th>الحالة</th>
                                        <th>إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                    <tr><td colspan="8" class="text-center text-muted p-3">لا توجد أوامر إنتاج</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><a href="view_order.php?id=<?= $order['id'] ?>"><?= htmlspecialchars($order['order_number']) ?></a></td>
                                        <td><?= htmlspecialchars($order['product_name'] ?? '-') ?></td>
                                        <td><?= number_format($order['quantity']) ?></td>
                                        <td><?= number_format($order['produced_quantity']) ?></td>
                                        <td><?= $priorityLabels[$order['priority']] ?? $order['priority'] ?></td>
                                        <td><?= $order['due_date'] ?? '-' ?></td>
                                        <td><span class="badge badge-<?= $statusColors[$order['status']] ?? 'secondary' ?>"><?= $statusLabels[$order['status']] ?? $order['status'] ?></span></td>
                                        <td>
                                            <a href="view_order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline" title="عرض"><i class="fas fa-eye"></i></a>
                                            <a href="edit_order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline" title="تعديل"><i class="fas fa-edit"></i></a>
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
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>
</body>
</html>
