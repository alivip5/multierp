<?php
/**
 * صفحة عرض أمر إنتاج
 * Production Module - View Order
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../api/config/config.php';
require_once __DIR__ . '/../../includes/Database.php';

$db = Database::getInstance();
$company_id = $_SESSION['company_id'] ?? 1;
$user = $db->fetch("SELECT u.*, r.name_ar as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?", [$_SESSION['user_id']]);
$company = $db->fetch("SELECT * FROM companies WHERE id = ?", [$company_id]);

$pageTitle = 'تفاصيل أمر الإنتاج';

$order_id = (int)($_GET['id'] ?? 0);
if (!$order_id) {
    header('Location: orders.php');
    exit;
}

// جلب بيانات الأمر
$order = null;
try {
    $order = $db->fetch(
        "SELECT po.*, p.name as product_name, b.name as bom_name
         FROM production_orders po 
         LEFT JOIN products p ON po.product_id = p.id 
         LEFT JOIN production_bom b ON po.bom_id = b.id
         WHERE po.id = ? AND po.company_id = ?",
        [$order_id, $company_id]
    );
} catch (Exception $e) {}

if (!$order) {
    header('Location: orders.php?error=not_found');
    exit;
}

$statusLabels = ['draft' => 'مسودة', 'pending' => 'قيد الانتظار', 'in_progress' => 'قيد التنفيذ', 'completed' => 'مكتمل', 'cancelled' => 'ملغي'];
$statusColors = ['draft' => 'secondary', 'pending' => 'warning', 'in_progress' => 'info', 'completed' => 'success', 'cancelled' => 'danger'];
$priorityLabels = ['low' => 'منخفضة', 'normal' => 'عادية', 'high' => 'عالية', 'urgent' => 'عاجلة'];

// تحديث الحالة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_status'])) {
    $newStatus = $_POST['new_status'];
    try {
        $conn = $db->getConnection();
        $stmt = $conn->prepare("UPDATE production_orders SET status = ? WHERE id = ? AND company_id = ?");
        $stmt->execute([$newStatus, $order_id, $company_id]);
        header("Location: view_order.php?id=$order_id");
        exit;
    } catch (Exception $e) {}
}
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
</head>
<body>
    <div class="app-container">
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo"><i class="fas fa-building"></i></div>
                <span class="sidebar-brand"><?= htmlspecialchars($company['name']) ?></span>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-item"><a href="../../pages/dashboard.php" class="nav-link"><i class="fas fa-home"></i><span>لوحة التحكم</span></a></div>
                    <div class="nav-item"><a href="index.php" class="nav-link active"><i class="fas fa-industry"></i><span>الإنتاج</span></a></div>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1><i class="fas fa-clipboard-list"></i> <?= htmlspecialchars($order['order_number']) ?></h1>
                    <p><span class="badge badge-<?= $statusColors[$order['status']] ?>"><?= $statusLabels[$order['status']] ?></span></p>
                </div>
                <div class="header-actions">
                    <button class="menu-toggle-btn" onclick="toggleSidebar()" title="القائمة">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="edit_order.php?id=<?= $order_id ?>" class="btn btn-outline"><i class="fas fa-edit"></i> تعديل</a>
                    <a href="orders.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <div class="row">
                    <div class="col-6">
                        <div class="card mb-3">
                            <div class="card-header"><h3 class="card-title">معلومات الأمر</h3></div>
                            <div class="card-body">
                                <table class="table">
                                    <tr><td><strong>رقم الأمر</strong></td><td><?= htmlspecialchars($order['order_number']) ?></td></tr>
                                    <tr><td><strong>المنتج</strong></td><td><?= htmlspecialchars($order['product_name'] ?? '-') ?></td></tr>
                                    <tr><td><strong>قائمة المواد</strong></td><td><?= htmlspecialchars($order['bom_name'] ?? 'غير محدد') ?></td></tr>
                                    <tr><td><strong>الكمية المطلوبة</strong></td><td><?= number_format($order['quantity']) ?></td></tr>
                                    <tr><td><strong>الكمية المنجزة</strong></td><td><?= number_format($order['produced_quantity']) ?></td></tr>
                                    <tr><td><strong>الأولوية</strong></td><td><?= $priorityLabels[$order['priority']] ?? $order['priority'] ?></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card mb-3">
                            <div class="card-header"><h3 class="card-title">التواريخ والتكاليف</h3></div>
                            <div class="card-body">
                                <table class="table">
                                    <tr><td><strong>تاريخ البدء</strong></td><td><?= $order['start_date'] ?? '-' ?></td></tr>
                                    <tr><td><strong>تاريخ الاستحقاق</strong></td><td><?= $order['due_date'] ?? '-' ?></td></tr>
                                    <tr><td><strong>تاريخ الإكمال</strong></td><td><?= $order['completion_date'] ?? '-' ?></td></tr>
                                    <tr><td><strong>تكلفة المواد</strong></td><td><?= number_format($order['total_material_cost'], 2) ?></td></tr>
                                    <tr><td><strong>تكلفة العمالة</strong></td><td><?= number_format($order['total_labor_cost'], 2) ?></td></tr>
                                    <tr><td><strong>إجمالي التكلفة</strong></td><td><strong><?= number_format($order['total_cost'], 2) ?></strong></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- تغيير الحالة -->
                <div class="card mb-3">
                    <div class="card-header"><h3 class="card-title">تحديث الحالة</h3></div>
                    <div class="card-body">
                        <form method="POST" class="d-flex gap-2">
                            <select name="new_status" class="form-control" style="max-width: 200px;">
                                <?php foreach ($statusLabels as $k => $v): ?>
                                <option value="<?= $k ?>" <?= $order['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary">تحديث</button>
                        </form>
                    </div>
                </div>

                <?php if ($order['notes']): ?>
                <div class="card">
                    <div class="card-header"><h3 class="card-title">ملاحظات</h3></div>
                    <div class="card-body"><?= nl2br(htmlspecialchars($order['notes'])) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
