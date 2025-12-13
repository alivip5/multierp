<?php
/**
 * شاشة أرصدة أول المدة للمخازن
 * Inventory Module - Opening Stock
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

// الموديولات للقائمة الجانبية
$enabledModules = getSidebarItems($company['id'], $_SESSION['user_id']);

$pageTitle = 'أرصدة أول المدة';
$success = '';
$error = '';

// جلب المخازن
$warehouses = $db->fetchAll("SELECT * FROM warehouses WHERE company_id = ? AND status = 'active' ORDER BY is_default DESC, name", [$company_id]);

// جلب المنتجات
$products = $db->fetchAll("SELECT * FROM products WHERE company_id = ? AND is_active = 1 AND track_inventory = 1 ORDER BY name", [$company_id]);

// المخزن المحدد
$selectedWarehouse = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : ($warehouses[0]['id'] ?? 0);

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_opening_stock') {
        $warehouseId = (int)($_POST['warehouse_id'] ?? 0);
        $openingDate = $_POST['opening_date'] ?? date('Y-m-d');
        $fiscalYear = (int)date('Y', strtotime($openingDate));
        
        if ($warehouseId) {
            $conn = $db->getConnection();
            $conn->beginTransaction();
            
            try {
                $items = $_POST['items'] ?? [];
                
                foreach ($items as $productId => $data) {
                    $quantity = (float)($data['quantity'] ?? 0);
                    $unitCost = (float)($data['unit_cost'] ?? 0);
                    
                    if ($quantity > 0) {
                        // التحقق من وجود رصيد سابق
                        $existing = $db->fetch(
                            "SELECT * FROM opening_stock WHERE product_id = ? AND warehouse_id = ? AND fiscal_year = ?",
                            [$productId, $warehouseId, $fiscalYear]
                        );
                        
                        if ($existing) {
                            // تحديث
                            $db->update('opening_stock', [
                                'quantity' => $quantity,
                                'unit_cost' => $unitCost,
                                'total_cost' => $quantity * $unitCost,
                                'opening_date' => $openingDate
                            ], 'id = ?', [$existing['id']]);
                        } else {
                            // إضافة جديد
                            $db->insert('opening_stock', [
                                'company_id' => $company_id,
                                'product_id' => $productId,
                                'warehouse_id' => $warehouseId,
                                'quantity' => $quantity,
                                'unit_cost' => $unitCost,
                                'total_cost' => $quantity * $unitCost,
                                'opening_date' => $openingDate,
                                'fiscal_year' => $fiscalYear,
                                'created_by' => $_SESSION['user_id']
                            ]);
                        }
                        
                        // تحديث رصيد المخزون
                        $currentStock = $db->fetch(
                            "SELECT * FROM product_stock WHERE product_id = ? AND warehouse_id = ?",
                            [$productId, $warehouseId]
                        );
                        
                        if ($currentStock) {
                            $db->update('product_stock', [
                                'quantity' => $quantity,
                                'avg_cost' => $unitCost
                            ], 'product_id = ? AND warehouse_id = ?', [$productId, $warehouseId]);
                        } else {
                            $db->insert('product_stock', [
                                'product_id' => $productId,
                                'warehouse_id' => $warehouseId,
                                'quantity' => $quantity,
                                'avg_cost' => $unitCost
                            ]);
                        }
                        
                        // تسجيل حركة مخزنية
                        $db->insert('inventory_movements', [
                            'company_id' => $company_id,
                            'product_id' => $productId,
                            'warehouse_id' => $warehouseId,
                            'movement_type' => 'opening_balance',
                            'quantity' => $quantity,
                            'unit_cost' => $unitCost,
                            'balance_before' => 0,
                            'balance_after' => $quantity,
                            'notes' => 'رصيد أول المدة - ' . $fiscalYear,
                            'created_by' => $_SESSION['user_id']
                        ]);
                    }
                }
                
                $conn->commit();
                $success = 'تم حفظ أرصدة أول المدة بنجاح';
            } catch (Exception $e) {
                $conn->rollBack();
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        }
    }
}

// جلب الأرصدة الحالية للمخزن المحدد
$currentStock = [];
if ($selectedWarehouse) {
    $stockData = $db->fetchAll(
        "SELECT ps.*, p.name as product_name, p.code as product_code,
                os.quantity as opening_quantity, os.unit_cost as opening_cost
         FROM product_stock ps
         JOIN products p ON ps.product_id = p.id
         LEFT JOIN opening_stock os ON os.product_id = ps.product_id AND os.warehouse_id = ps.warehouse_id
         WHERE ps.warehouse_id = ?
         ORDER BY p.name",
        [$selectedWarehouse]
    );
    foreach ($stockData as $item) {
        $currentStock[$item['product_id']] = $item;
    }
}
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
                           class="nav-link <?= $module['slug'] === 'inventory' ? 'active' : '' ?>">
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
                    <h1><i class="fas fa-box-open"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <a href="index.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item"><i class="fas fa-boxes"></i><span>لوحة المخزون</span></a>
                        <a href="products.php" class="submenu-item"><i class="fas fa-box"></i><span>المنتجات</span></a>
                        <a href="opening_stock.php" class="submenu-item active"><i class="fas fa-box-open"></i><span>أرصدة أول المدة</span></a>
                        <a href="stock_transfers.php" class="submenu-item"><i class="fas fa-exchange-alt"></i><span>نقل مخزون</span></a>
                        <a href="low_stock.php" class="submenu-item"><i class="fas fa-exclamation-triangle"></i><span>نواقص المخزون</span></a>
                    </div>
                </div>
                <?php if ($success): ?><div class="alert alert-success mb-3"><?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger mb-3"><?= $error ?></div><?php endif; ?>
                
                <!-- اختيار المخزن -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-2 align-center">
                            <label><strong>المخزن:</strong></label>
                            <select name="warehouse_id" class="form-control" style="max-width: 300px;" onchange="this.form.submit()">
                                <?php foreach ($warehouses as $wh): ?>
                                <option value="<?= $wh['id'] ?>" <?= $selectedWarehouse == $wh['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($wh['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>
                
                <!-- إدخال الأرصدة -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-edit"></i> إدخال أرصدة أول المدة</h3>
                    </div>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="save_opening_stock">
                        <input type="hidden" name="warehouse_id" value="<?= $selectedWarehouse ?>">
                        
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label>تاريخ الرصيد الافتتاحي</label>
                                <input type="date" name="opening_date" class="form-control" value="<?= date('Y-m-d') ?>" style="max-width: 200px;">
                            </div>
                        </div>
                        
                        <div class="card-body p-0">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>كود المنتج</th>
                                        <th>اسم المنتج</th>
                                        <th>الكمية</th>
                                        <th>سعر التكلفة</th>
                                        <th>الإجمالي</th>
                                        <th>الرصيد الحالي</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): 
                                        $stock = $currentStock[$product['id']] ?? null;
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($product['code'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($product['name']) ?></td>
                                        <td>
                                            <input type="number" 
                                                   name="items[<?= $product['id'] ?>][quantity]" 
                                                   class="form-control qty-input" 
                                                   value="<?= $stock['opening_quantity'] ?? 0 ?>" 
                                                   min="0" 
                                                   step="0.01"
                                                   data-product="<?= $product['id'] ?>"
                                                   onchange="calculateTotal(this)">
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   name="items[<?= $product['id'] ?>][unit_cost]" 
                                                   class="form-control cost-input" 
                                                   value="<?= $stock['opening_cost'] ?? $product['purchase_price'] ?>" 
                                                   min="0" 
                                                   step="0.01"
                                                   data-product="<?= $product['id'] ?>"
                                                   onchange="calculateTotal(this)">
                                        </td>
                                        <td>
                                            <span class="total-display" data-product="<?= $product['id'] ?>">
                                                <?= number_format(($stock['opening_quantity'] ?? 0) * ($stock['opening_cost'] ?? $product['purchase_price']), 2) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($stock): ?>
                                            <span class="badge badge-info"><?= number_format($stock['quantity'], 2) ?></span>
                                            <?php else: ?>
                                            <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ الأرصدة
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function calculateTotal(input) {
            const productId = input.dataset.product;
            const row = input.closest('tr');
            const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
            const cost = parseFloat(row.querySelector('.cost-input').value) || 0;
            const total = qty * cost;
            
            row.querySelector('.total-display').textContent = total.toFixed(2);
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>
</body>
</html>
