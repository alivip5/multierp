<?php
/**
 * صفحة تعديل أمر إنتاج
 * Production Module - Edit Order
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

$pageTitle = 'تعديل أمر الإنتاج';
$success = '';
$error = '';

$order_id = (int)($_GET['id'] ?? 0);
if (!$order_id) {
    header('Location: orders.php');
    exit;
}

// جلب بيانات الأمر
$order = null;
try {
    $order = $db->fetch("SELECT * FROM production_orders WHERE id = ? AND company_id = ?", [$order_id, $company_id]);
} catch (Exception $e) {}

if (!$order) {
    header('Location: orders.php?error=not_found');
    exit;
}

// جلب المنتجات
$products = $db->fetchAll("SELECT id, name FROM products WHERE company_id = ? AND is_active = 1 ORDER BY name", [$company_id]);

// جلب قوائم المواد
$boms = [];
try {
    $boms = $db->fetchAll("SELECT id, name FROM production_bom WHERE company_id = ? AND is_active = 1 ORDER BY name", [$company_id]);
} catch (Exception $e) {}

// معالجة التحديث
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)$_POST['product_id'];
    $bom_id = !empty($_POST['bom_id']) ? (int)$_POST['bom_id'] : null;
    $quantity = (float)$_POST['quantity'];
    $produced_quantity = (float)$_POST['produced_quantity'];
    $due_date = $_POST['due_date'] ?: null;
    $priority = $_POST['priority'] ?? 'normal';
    $status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';
    
    if ($product_id && $quantity > 0) {
        try {
            $conn = $db->getConnection();
            $stmt = $conn->prepare("UPDATE production_orders SET 
                bom_id = ?, product_id = ?, quantity = ?, produced_quantity = ?, 
                due_date = ?, priority = ?, status = ?, notes = ?
                WHERE id = ? AND company_id = ?");
            $stmt->execute([
                $bom_id, $product_id, $quantity, $produced_quantity, 
                $due_date, $priority, $status, $notes, $order_id, $company_id
            ]);
            
            $success = "تم تحديث أمر الإنتاج بنجاح";
            $order = $db->fetch("SELECT * FROM production_orders WHERE id = ?", [$order_id]);
        } catch (Exception $e) {
            $error = "خطأ: " . $e->getMessage();
        }
    } else {
        $error = "يرجى اختيار المنتج وتحديد الكمية";
    }
}

$statusLabels = ['draft' => 'مسودة', 'pending' => 'قيد الانتظار', 'in_progress' => 'قيد التنفيذ', 'completed' => 'مكتمل', 'cancelled' => 'ملغي'];
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
                    <h1><i class="fas fa-edit"></i> تعديل: <?= htmlspecialchars($order['order_number']) ?></h1>
                </div>
                <div class="header-actions">
                    <button class="menu-toggle-btn" onclick="toggleSidebar()" title="القائمة">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="view_order.php?id=<?= $order_id ?>" class="btn btn-outline">عرض</a>
                    <a href="orders.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <?php if ($success): ?><div class="alert alert-success mb-3"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger mb-3"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="form-label">المنتج *</label>
                                        <select name="product_id" class="form-control" required>
                                            <?php foreach ($products as $p): ?>
                                            <option value="<?= $p['id'] ?>" <?= $order['product_id'] == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="form-label">قائمة المواد (BOM)</label>
                                        <select name="bom_id" class="form-control">
                                            <option value="">بدون</option>
                                            <?php foreach ($boms as $b): ?>
                                            <option value="<?= $b['id'] ?>" <?= $order['bom_id'] == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-3">
                                    <div class="form-group">
                                        <label class="form-label">الكمية المطلوبة *</label>
                                        <input type="number" name="quantity" class="form-control" value="<?= $order['quantity'] ?>" min="1" required>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="form-group">
                                        <label class="form-label">الكمية المنجزة</label>
                                        <input type="number" name="produced_quantity" class="form-control" value="<?= $order['produced_quantity'] ?>" min="0">
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="form-group">
                                        <label class="form-label">تاريخ الاستحقاق</label>
                                        <input type="date" name="due_date" class="form-control" value="<?= $order['due_date'] ?>">
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="form-group">
                                        <label class="form-label">الأولوية</label>
                                        <select name="priority" class="form-control">
                                            <option value="low" <?= $order['priority'] === 'low' ? 'selected' : '' ?>>منخفضة</option>
                                            <option value="normal" <?= $order['priority'] === 'normal' ? 'selected' : '' ?>>عادية</option>
                                            <option value="high" <?= $order['priority'] === 'high' ? 'selected' : '' ?>>عالية</option>
                                            <option value="urgent" <?= $order['priority'] === 'urgent' ? 'selected' : '' ?>>عاجلة</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">الحالة</label>
                                <select name="status" class="form-control" style="max-width: 200px;">
                                    <?php foreach ($statusLabels as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= $order['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">ملاحظات</label>
                                <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ التغييرات</button>
                                <a href="orders.php" class="btn btn-outline">إلغاء</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
