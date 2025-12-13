<?php
/**
 * إضافة فاتورة مشتريات
 * Purchases Module - Add Invoice
 * تم تحديثه: إضافة اختيار المخزن الإلزامي
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../api/config/config.php';
require_once __DIR__ . '/../../includes/SidebarHelper.php';

$db = Database::getInstance();
$user = $db->fetch("SELECT u.*, r.name as role_slug, r.name_ar as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?", [$_SESSION['user_id']]);
$company = $db->fetch("SELECT * FROM companies WHERE id = ?", [$_SESSION['company_id'] ?? 1]);

// التأكد من وجود بيانات الدور في الجلسة
if (!isset($_SESSION['role_id']) && $user) {
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_slug'];
}

// الموديولات للقائمة الجانبية
$enabledModules = getSidebarItems($company['id'], $_SESSION['user_id']);

$pageTitle = 'فاتورة شراء جديدة';
$products = $db->fetchAll("SELECT * FROM products WHERE company_id = ? AND is_active = 1 ORDER BY name", [$company['id']]);
$suppliers = $db->fetchAll("SELECT * FROM suppliers WHERE company_id = ? AND status = 'active' ORDER BY name", [$company['id']]);
$warehouses = $db->fetchAll("SELECT * FROM warehouses WHERE company_id = ? AND status = 'active' ORDER BY is_default DESC, name", [$company['id']]);
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
                    <h1><i class="fas fa-plus"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <a href="suppliers.php?action=add" class="btn btn-outline" target="_blank"><i class="fas fa-truck"></i> إضافة مورد</a>
                    <button type="submit" form="purchaseForm" class="btn btn-primary"><i class="fas fa-save"></i> حفظ</button>
                    <a href="index.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item"><i class="fas fa-list"></i><span>الفواتير</span></a>
                        <a href="add.php" class="submenu-item active"><i class="fas fa-plus"></i><span>فاتورة جديدة</span></a>
                        <a href="suppliers.php" class="submenu-item"><i class="fas fa-truck"></i><span>الموردين</span></a>
                    </div>
                </div>

                <form id="purchaseForm">
                    <div class="row">
                        <div class="col-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-box"></i> الأصناف</h3>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table" id="itemsTable">
                                        <thead>
                                            <tr>
                                                <th width="40%">المنتج</th>
                                                <th>الكمية</th>
                                                <th>السعر</th>
                                                <th>الإجمالي</th>
                                                <th width="50px"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="item-row">
                                                <td>
                                                    <select name="items[0][product_id]" class="form-control product-select" required>
                                                        <option value="">اختر منتج...</option>
                                                        <?php foreach ($products as $p): ?>
                                                        <option value="<?= $p['id'] ?>" data-price="<?= $p['purchase_price'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td><input type="number" name="items[0][quantity]" class="form-control qty" value="1" min="0.01" step="0.01" onchange="calcRow(this)"></td>
                                                <td><input type="number" name="items[0][price]" class="form-control price" step="0.01" onchange="calcRow(this)"></td>
                                                <td><input type="text" class="form-control total" readonly></td>
                                                <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="fas fa-times"></i></button></td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr><td colspan="5"><button type="button" class="btn btn-outline btn-sm" onclick="addRow()"><i class="fas fa-plus"></i> إضافة منتج</button></td></tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-info-circle"></i> بيانات الفاتورة</h3>
                                </div>
                                <div class="card-body">
                                    <!-- المخزن - إلزامي -->
                                    <div class="form-group mb-3">
                                        <label><i class="fas fa-warehouse"></i> المخزن <span style="color: var(--danger);">*</span></label>
                                        <select name="warehouse_id" id="warehouseId" class="form-control" required>
                                            <option value="">اختر المخزن...</option>
                                            <?php foreach ($warehouses as $wh): ?>
                                            <option value="<?= $wh['id'] ?>" <?= $wh['is_default'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($wh['name']) ?>
                                                <?= $wh['is_default'] ? '(افتراضي)' : '' ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">سيتم إضافة الأصناف لهذا المخزن</small>
                                    </div>
                                    
                                    <!-- المورد -->
                                    <div class="form-group mb-3">
                                        <label><i class="fas fa-user"></i> المورد</label>
                                        <select name="supplier_id" class="form-control">
                                            <option value="">اختر مورد (اختياري)</option>
                                            <?php foreach ($suppliers as $s): ?>
                                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- تاريخ الفاتورة -->
                                    <div class="form-group mb-3">
                                        <label><i class="fas fa-calendar"></i> تاريخ الفاتورة</label>
                                        <input type="date" name="invoice_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                    
                                    <!-- رقم فاتورة المورد -->
                                    <div class="form-group mb-3">
                                        <label><i class="fas fa-file-invoice"></i> رقم فاتورة المورد</label>
                                        <input type="text" name="supplier_invoice_number" class="form-control" placeholder="اختياري">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-calculator"></i> الحساب</h3>
                                </div>
                                <div class="card-body">
                                    <!-- التحكم بالضريبة -->
                                    <div class="form-group mb-2">
                                        <div class="d-flex justify-between align-center">
                                            <div class="form-check">
                                                <input type="checkbox" id="applyTax" name="apply_tax" checked onchange="calcTotal()">
                                                <label for="applyTax">الضريبة</label>
                                            </div>
                                            <div style="width: 80px;">
                                                <input type="number" id="taxRate" name="tax_rate" class="form-control form-control-sm" value="15" min="0" max="100" step="0.5" onchange="calcTotal()">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- الخصم -->
                                    <div class="form-group mb-2">
                                        <label>الخصم</label>
                                        <div class="d-flex gap-2">
                                            <select id="discountType" name="discount_type" class="form-control" style="width: 80px;" onchange="calcTotal()">
                                                <option value="fixed">ثابت</option>
                                                <option value="percent">%</option>
                                            </select>
                                            <input type="number" id="discountValue" name="discount_value" class="form-control" value="0" min="0" step="0.01" onchange="calcTotal()">
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="d-flex justify-between mb-1">
                                        <span>المجموع:</span>
                                        <span id="subtotal">0.00</span>
                                    </div>
                                    <div class="d-flex justify-between mb-1" id="discountRow" style="display: none;">
                                        <span>الخصم:</span>
                                        <span id="discountAmount" style="color: var(--danger);">-0.00</span>
                                    </div>
                                    <div class="d-flex justify-between mb-1" id="taxRow">
                                        <span>الضريبة (<span id="taxRateDisplay">15</span>%):</span>
                                        <span id="taxAmount">0.00</span>
                                    </div>
                                    <div class="d-flex justify-between font-bold mt-2" style="font-size: 1.2em; padding-top: 10px; border-top: 2px solid var(--border-color);">
                                        <span>الإجمالي النهائي:</span>
                                        <span id="grandTotal" style="color: var(--primary);">0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script>
        let rowCount = 1;
        
        // تحديث السعر عند اختيار المنتج
        document.querySelectorAll('.product-select').forEach(select => {
            select.addEventListener('change', function() {
                updatePrice(this);
            });
        });
        
        function updatePrice(select) {
            const row = select.closest('tr');
            const price = select.options[select.selectedIndex]?.dataset?.price || 0;
            row.querySelector('.price').value = price;
            calcRow(select);
        }
        
        function calcRow(el) {
            let row = el.closest('tr');
            let qty = parseFloat(row.querySelector('.qty').value) || 0;
            let price = parseFloat(row.querySelector('.price').value) || 0;
            row.querySelector('.total').value = (qty * price).toFixed(2);
            calcTotal();
        }
        
        function calcTotal() {
            let subtotal = 0;
            document.querySelectorAll('.total').forEach(e => subtotal += parseFloat(e.value) || 0);
            
            // حساب الخصم
            const discountType = document.getElementById('discountType').value;
            const discountValue = parseFloat(document.getElementById('discountValue').value) || 0;
            let discountAmount = 0;
            
            if (discountType === 'percent') {
                discountAmount = subtotal * (discountValue / 100);
            } else {
                discountAmount = discountValue;
            }
            
            const afterDiscount = subtotal - discountAmount;
            
            // حساب الضريبة
            const applyTax = document.getElementById('applyTax').checked;
            const taxRate = parseFloat(document.getElementById('taxRate').value) || 0;
            let tax = 0;
            
            if (applyTax) {
                tax = afterDiscount * (taxRate / 100);
            }
            
            const total = afterDiscount + tax;

            // تحديث العرض
            document.getElementById('subtotal').innerText = subtotal.toFixed(2);
            document.getElementById('taxRateDisplay').innerText = taxRate;
            document.getElementById('taxAmount').innerText = tax.toFixed(2);
            document.getElementById('grandTotal').innerText = total.toFixed(2);
            
            // عرض/إخفاء صف الخصم
            const discountRow = document.getElementById('discountRow');
            if (discountAmount > 0) {
                discountRow.style.display = 'flex';
                document.getElementById('discountAmount').innerText = '-' + discountAmount.toFixed(2);
            } else {
                discountRow.style.display = 'none';
            }
            
            // عرض/إخفاء صف الضريبة
            document.getElementById('taxRow').style.display = applyTax ? 'flex' : 'none';
        }
        
        function addRow() {
            let tbody = document.querySelector('#itemsTable tbody');
            let idx = rowCount++;
            let firstRow = tbody.rows[0];
            let row = firstRow.cloneNode(true);
            
            row.querySelectorAll('input, select').forEach(e => {
                e.name = e.name.replace(/\[\d+\]/, `[${idx}]`);
                if (e.tagName === 'SELECT') {
                    e.value = '';
                } else if (e.classList.contains('qty')) {
                    e.value = 1;
                } else {
                    e.value = '';
                }
            });
            
            // إضافة event listener للـ select الجديد
            const newSelect = row.querySelector('.product-select');
            newSelect.addEventListener('change', function() {
                updatePrice(this);
            });
            
            tbody.appendChild(row);
        }
        
        function removeRow(btn) {
            if (document.querySelectorAll('#itemsTable tbody tr').length > 1) {
                btn.closest('tr').remove();
                calcTotal();
            }
        }
        
        document.getElementById('purchaseForm').onsubmit = async (e) => {
            e.preventDefault();
            
            // التحقق من اختيار المخزن
            const warehouseId = document.getElementById('warehouseId').value;
            if (!warehouseId) {
                alert('يجب اختيار المخزن');
                return;
            }
            
            let formData = new FormData(e.target);
            
            // Build JSON object
            let submitData = {
                warehouse_id: warehouseId,
                supplier_id: formData.get('supplier_id'),
                invoice_date: formData.get('invoice_date'),
                supplier_invoice_number: formData.get('supplier_invoice_number'),
                tax_rate: formData.get('tax_rate'),
                discount_type: formData.get('discount_type'),
                discount_value: formData.get('discount_value'),
                items: []
            };

            const rows = document.querySelectorAll('#itemsTable tbody tr');
            rows.forEach((row, index) => {
                const productId = row.querySelector('select').value;
                if (productId) {
                    submitData.items.push({
                        product_id: productId,
                        quantity: row.querySelector('.qty').value,
                        price: row.querySelector('.price').value
                    });
                }
            });

            if (submitData.items.length === 0) {
                alert('الرجاء اختيار منتج واحد على الأقل');
                return;
            }

            try {
                const response = await fetch('../../api/v1/purchases/create.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(submitData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('تم إنشاء فاتورة الشراء بنجاح');
                    window.location.href = 'index.php';
                } else {
                    alert(result.error || 'حدث خطأ');
                }
            } catch (error) {
                console.error(error);
                alert('حدث خطأ في الاتصال');
            }
        };
    </script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>
</body>
</html>
