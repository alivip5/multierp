<?php
/**
 * شاشة التحويلات المخزنية
 * Inventory Module - Stock Transfers
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

$pageTitle = 'التحويلات المخزنية';
$success = '';
$error = '';

// جلب البيانات
$warehouses = $db->fetchAll("SELECT w.*, b.name as branch_name FROM warehouses w LEFT JOIN branches b ON w.branch_id = b.id WHERE w.company_id = ? AND w.status = 'active' ORDER BY w.name", [$company_id]);
$products = $db->fetchAll("SELECT * FROM products WHERE company_id = ? AND is_active = 1 AND track_inventory = 1 ORDER BY name", [$company_id]);
$branches = $db->fetchAll("SELECT * FROM branches WHERE company_id = ? AND is_active = 1 ORDER BY name", [$company_id]);

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['action'] ?? '';
    
    // إنشاء تحويل جديد
    if ($action === 'create_transfer') {
        $fromWarehouse = (int)($_POST['from_warehouse_id'] ?? 0);
        $toWarehouse = (int)($_POST['to_warehouse_id'] ?? 0);
        $transferDate = $_POST['transfer_date'] ?? date('Y-m-d');
        $notes = trim($_POST['notes'] ?? '');
        
        if ($fromWarehouse && $toWarehouse && $fromWarehouse !== $toWarehouse) {
            $conn = $db->getConnection();
            $conn->beginTransaction();
            
            try {
                // توليد رقم التحويل
                $lastTransfer = $db->fetch("SELECT transfer_number FROM stock_transfers WHERE company_id = ? ORDER BY id DESC LIMIT 1", [$company_id]);
                $nextNum = 1;
                if ($lastTransfer) {
                    preg_match('/(\d+)$/', $lastTransfer['transfer_number'], $matches);
                    $nextNum = ((int)($matches[1] ?? 0)) + 1;
                }
                $transferNumber = 'TR-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
                
                // جلب الفروع
                $fromWh = $db->fetch("SELECT * FROM warehouses WHERE id = ?", [$fromWarehouse]);
                $toWh = $db->fetch("SELECT * FROM warehouses WHERE id = ?", [$toWarehouse]);
                
                // إنشاء التحويل
                $transferId = $db->insert('stock_transfers', [
                    'company_id' => $company_id,
                    'transfer_number' => $transferNumber,
                    'from_warehouse_id' => $fromWarehouse,
                    'to_warehouse_id' => $toWarehouse,
                    'from_branch_id' => $fromWh['branch_id'] ?? null,
                    'to_branch_id' => $toWh['branch_id'] ?? null,
                    'transfer_date' => $transferDate,
                    'status' => 'completed',
                    'notes' => $notes,
                    'created_by' => $_SESSION['user_id'],
                    'completed_at' => date('Y-m-d H:i:s')
                ]);
                
                // إضافة البنود
                $items = $_POST['items'] ?? [];
                foreach ($items as $item) {
                    $productId = (int)($item['product_id'] ?? 0);
                    $quantity = (float)($item['quantity'] ?? 0);
                    
                    if ($productId && $quantity > 0) {
                        // التحقق من الرصيد المتاح
                        $currentStock = $db->fetch(
                            "SELECT * FROM product_stock WHERE product_id = ? AND warehouse_id = ?",
                            [$productId, $fromWarehouse]
                        );
                        
                        if (!$currentStock || $currentStock['quantity'] < $quantity) {
                            throw new Exception('الكمية المتاحة غير كافية للمنتج');
                        }
                        
                        $unitCost = $currentStock['avg_cost'] ?? 0;
                        
                        // إضافة بند التحويل
                        $db->insert('stock_transfer_items', [
                            'transfer_id' => $transferId,
                            'product_id' => $productId,
                            'quantity' => $quantity,
                            'unit_cost' => $unitCost,
                            'received_quantity' => $quantity
                        ]);
                        
                        // خصم من المخزن المصدر
                        $balanceBefore = (float)$currentStock['quantity'];
                        $balanceAfter = $balanceBefore - $quantity;
                        
                        $db->update('product_stock', 
                            ['quantity' => $balanceAfter],
                            'product_id = ? AND warehouse_id = ?',
                            [$productId, $fromWarehouse]
                        );
                        
                        // تسجيل حركة OUT
                        $db->insert('inventory_movements', [
                            'company_id' => $company_id,
                            'product_id' => $productId,
                            'warehouse_id' => $fromWarehouse,
                            'movement_type' => 'transfer_out',
                            'reference_type' => 'stock_transfer',
                            'reference_id' => $transferId,
                            'quantity' => $quantity,
                            'unit_cost' => $unitCost,
                            'balance_before' => $balanceBefore,
                            'balance_after' => $balanceAfter,
                            'notes' => 'تحويل إلى ' . $toWh['name'],
                            'created_by' => $_SESSION['user_id']
                        ]);
                        
                        // إضافة للمخزن الوجهة
                        $destStock = $db->fetch(
                            "SELECT * FROM product_stock WHERE product_id = ? AND warehouse_id = ?",
                            [$productId, $toWarehouse]
                        );
                        
                        $destBalanceBefore = $destStock ? (float)$destStock['quantity'] : 0;
                        $destBalanceAfter = $destBalanceBefore + $quantity;
                        
                        if ($destStock) {
                            $db->update('product_stock',
                                ['quantity' => $destBalanceAfter],
                                'product_id = ? AND warehouse_id = ?',
                                [$productId, $toWarehouse]
                            );
                        } else {
                            $db->insert('product_stock', [
                                'product_id' => $productId,
                                'warehouse_id' => $toWarehouse,
                                'quantity' => $quantity,
                                'avg_cost' => $unitCost
                            ]);
                        }
                        
                        // تسجيل حركة IN
                        $db->insert('inventory_movements', [
                            'company_id' => $company_id,
                            'product_id' => $productId,
                            'warehouse_id' => $toWarehouse,
                            'movement_type' => 'transfer_in',
                            'reference_type' => 'stock_transfer',
                            'reference_id' => $transferId,
                            'quantity' => $quantity,
                            'unit_cost' => $unitCost,
                            'balance_before' => $destBalanceBefore,
                            'balance_after' => $destBalanceAfter,
                            'notes' => 'تحويل من ' . $fromWh['name'],
                            'created_by' => $_SESSION['user_id']
                        ]);
                    }
                }
                
                $conn->commit();
                $success = 'تم إنشاء التحويل رقم ' . $transferNumber . ' بنجاح';
            } catch (Exception $e) {
                $conn->rollBack();
                $error = $e->getMessage();
            }
        } else {
            $error = 'يجب اختيار مخزنين مختلفين';
        }
    }
}

// جلب التحويلات السابقة
$transfers = [];
try {
    $transfers = $db->fetchAll(
        "SELECT st.*, 
                fw.name as from_warehouse_name, tw.name as to_warehouse_name,
                fb.name as from_branch_name, tb.name as to_branch_name,
                u.full_name as created_by_name
         FROM stock_transfers st
         LEFT JOIN warehouses fw ON st.from_warehouse_id = fw.id
         LEFT JOIN warehouses tw ON st.to_warehouse_id = tw.id
         LEFT JOIN branches fb ON st.from_branch_id = fb.id
         LEFT JOIN branches tb ON st.to_branch_id = tb.id
         LEFT JOIN users u ON st.created_by = u.id
         WHERE st.company_id = ?
         ORDER BY st.created_at DESC
         LIMIT 50",
        [$company_id]
    );
} catch (Exception $e) {}

$statusLabels = ['pending' => 'معلق', 'in_transit' => 'في الطريق', 'completed' => 'مكتمل', 'cancelled' => 'ملغي'];
$statusColors = ['pending' => 'warning', 'in_transit' => 'info', 'completed' => 'success', 'cancelled' => 'danger'];
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
                    <h1><i class="fas fa-exchange-alt"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="document.getElementById('newTransferModal').style.display='flex'">
                        <i class="fas fa-plus"></i> تحويل جديد
                    </button>
                    <a href="index.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <?php if ($success): ?><div class="alert alert-success mb-3"><?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger mb-3"><?= $error ?></div><?php endif; ?>
                
                <!-- قائمة التحويلات -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> سجل التحويلات</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>رقم التحويل</th>
                                    <th>التاريخ</th>
                                    <th>من</th>
                                    <th>إلى</th>
                                    <th>الحالة</th>
                                    <th>بواسطة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transfers)): ?>
                                <tr><td colspan="6" class="text-center text-muted p-3">لا توجد تحويلات</td></tr>
                                <?php else: ?>
                                <?php foreach ($transfers as $transfer): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($transfer['transfer_number']) ?></strong></td>
                                    <td><?= $transfer['transfer_date'] ?></td>
                                    <td>
                                        <?= htmlspecialchars($transfer['from_warehouse_name']) ?>
                                        <?php if ($transfer['from_branch_name']): ?>
                                        <small class="text-muted">(<?= $transfer['from_branch_name'] ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($transfer['to_warehouse_name']) ?>
                                        <?php if ($transfer['to_branch_name']): ?>
                                        <small class="text-muted">(<?= $transfer['to_branch_name'] ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $statusColors[$transfer['status']] ?? 'secondary' ?>">
                                            <?= $statusLabels[$transfer['status']] ?? $transfer['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($transfer['created_by_name'] ?? '-') ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal التحويل الجديد -->
    <div id="newTransferModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
        <div class="card" style="width:800px; max-width:95%; max-height: 90vh; overflow-y: auto;">
            <div class="card-header d-flex justify-between">
                <h3 class="card-title"><i class="fas fa-exchange-alt"></i> تحويل مخزني جديد</h3>
                <button onclick="document.getElementById('newTransferModal').style.display='none'" class="btn btn-sm btn-outline">&times;</button>
            </div>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_transfer">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="form-group">
                                <label><i class="fas fa-arrow-right"></i> من المخزن <span style="color: var(--danger);">*</span></label>
                                <select name="from_warehouse_id" class="form-control" required onchange="updateFromStock()">
                                    <option value="">اختر...</option>
                                    <?php foreach ($warehouses as $wh): ?>
                                    <option value="<?= $wh['id'] ?>">
                                        <?= htmlspecialchars($wh['name']) ?>
                                        <?= $wh['branch_name'] ? '(' . $wh['branch_name'] . ')' : '' ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label><i class="fas fa-arrow-left"></i> إلى المخزن <span style="color: var(--danger);">*</span></label>
                                <select name="to_warehouse_id" class="form-control" required>
                                    <option value="">اختر...</option>
                                    <?php foreach ($warehouses as $wh): ?>
                                    <option value="<?= $wh['id'] ?>">
                                        <?= htmlspecialchars($wh['name']) ?>
                                        <?= $wh['branch_name'] ? '(' . $wh['branch_name'] . ')' : '' ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label>تاريخ التحويل</label>
                        <input type="date" name="transfer_date" class="form-control" value="<?= date('Y-m-d') ?>" style="max-width: 200px;">
                    </div>
                    
                    <hr>
                    
                    <h4>الأصناف</h4>
                    <table class="table" id="itemsTable">
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>الكمية</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="item-row">
                                <td>
                                    <select name="items[0][product_id]" class="form-control">
                                        <option value="">اختر منتج...</option>
                                        <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="items[0][quantity]" class="form-control" min="0.01" step="0.01" value="1">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="fas fa-times"></i></button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3">
                                    <button type="button" class="btn btn-sm btn-outline" onclick="addRow()">
                                        <i class="fas fa-plus"></i> إضافة صنف
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <div class="form-group">
                        <label>ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> تنفيذ التحويل</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let rowCount = 1;
        
        function addRow() {
            const tbody = document.querySelector('#itemsTable tbody');
            const firstRow = tbody.rows[0];
            const row = firstRow.cloneNode(true);
            
            row.querySelectorAll('input, select').forEach(e => {
                e.name = e.name.replace(/\[\d+\]/, `[${rowCount}]`);
                if (e.tagName === 'SELECT') {
                    e.value = '';
                } else {
                    e.value = 1;
                }
            });
            
            tbody.appendChild(row);
            rowCount++;
        }
        
        function removeRow(btn) {
            if (document.querySelectorAll('#itemsTable tbody tr').length > 1) {
                btn.closest('tr').remove();
            }
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>
</body>
</html>
