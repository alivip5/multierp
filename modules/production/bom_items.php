<?php
/**
 * صفحة مكونات قائمة المواد
 * Production Module - BOM Items
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

$bom_id = (int)($_GET['id'] ?? 0);
if (!$bom_id) {
    header('Location: bom.php');
    exit;
}

// جلب بيانات BOM
$bom = null;
try {
    $bom = $db->fetch("SELECT b.*, p.name as product_name FROM production_bom b LEFT JOIN products p ON b.product_id = p.id WHERE b.id = ? AND b.company_id = ?", [$bom_id, $company_id]);
} catch (Exception $e) {}

if (!$bom) {
    header('Location: bom.php?error=not_found');
    exit;
}

$pageTitle = 'مكونات: ' . $bom['name'];
$success = '';
$error = '';

// جلب المنتجات (المواد الخام)
// جلب المنتجات (المواد الخام)
$materials = $db->fetchAll("SELECT p.id, p.name, u.name as unit_name FROM products p LEFT JOIN units u ON p.unit_id = u.id WHERE p.company_id = ? ORDER BY p.name", [$company_id]);

// معالجة الإضافة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $material_id = (int)$_POST['material_id'];
        $quantity = (float)$_POST['quantity'];
        $unit = trim($_POST['unit'] ?? '');
        
        if ($material_id && $quantity > 0) {
            try {
                $conn = $db->getConnection();
                $stmt = $conn->prepare("INSERT INTO production_bom_items (bom_id, material_id, quantity, unit) VALUES (?, ?, ?, ?)");
                $stmt->execute([$bom_id, $material_id, $quantity, $unit]);
                $success = "تم إضافة المكون بنجاح";
            } catch (Exception $e) {
                $error = "خطأ: " . $e->getMessage();
            }
        } else {
            $error = "يرجى اختيار المادة وتحديد الكمية";
        }
    }
    
    if ($_POST['action'] === 'delete' && isset($_POST['item_id'])) {
        try {
            $conn = $db->getConnection();
            $stmt = $conn->prepare("DELETE FROM production_bom_items WHERE id = ? AND bom_id = ?");
            $stmt->execute([(int)$_POST['item_id'], $bom_id]);
            $success = "تم حذف المكون بنجاح";
        } catch (Exception $e) {
            $error = "خطأ في الحذف";
        }
    }
}

// جلب المكونات
$items = [];
try {
    $items = $db->fetchAll(
        "SELECT bi.*, p.name as material_name, u.name as material_unit
         FROM production_bom_items bi 
         LEFT JOIN products p ON bi.material_id = p.id 
         LEFT JOIN units u ON p.unit_id = u.id
         WHERE bi.bom_id = ?
         ORDER BY p.name",
        [$bom_id]
    );
} catch (Exception $e) {}
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
                    <h1><i class="fas fa-list"></i> <?= $pageTitle ?></h1>
                    <p>المنتج النهائي: <?= htmlspecialchars($bom['product_name'] ?? '-') ?></p>
                </div>
                <div class="header-actions">
                    <a href="bom.php" class="btn btn-outline">عودة للقوائم</a>
                </div>
            </header>

            <div class="page-content">
                <?php if ($success): ?><div class="alert alert-success mb-3"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger mb-3"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>

                <!-- إضافة مكون جديد -->
                <div class="card mb-3">
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-plus-circle"></i> إضافة مكون</h3></div>
                    <div class="card-body">
                        <form method="POST" class="d-flex gap-2 align-center flex-wrap">
                            <input type="hidden" name="action" value="add">
                            <select name="material_id" class="form-control" required style="max-width: 250px;">
                                <option value="">اختر المادة</option>
                                <?php foreach ($materials as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" name="quantity" class="form-control" placeholder="الكمية" step="0.001" min="0.001" required style="max-width: 120px;">
                            <input type="text" name="unit" class="form-control" placeholder="الوحدة" style="max-width: 100px;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> إضافة</button>
                        </form>
                    </div>
                </div>

                <!-- قائمة المكونات -->
                <div class="card">
                    <div class="card-header"><h3 class="card-title">المكونات (<?= count($items) ?>)</h3></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>المادة</th>
                                        <th>الكمية</th>
                                        <th>الوحدة</th>
                                        <th>الهدر %</th>
                                        <th>إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($items)): ?>
                                    <tr><td colspan="6" class="text-center text-muted p-3">لم يتم إضافة مكونات بعد</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($items as $i => $item): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= htmlspecialchars($item['material_name'] ?? '-') ?></td>
                                        <td><?= number_format($item['quantity'], 3) ?></td>
                                        <td><?= htmlspecialchars($item['unit'] ?: $item['material_unit'] ?? '-') ?></td>
                                        <td><?= $item['waste_percentage'] ?>%</td>
                                        <td>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد؟');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                            </form>
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
</body>
</html>
