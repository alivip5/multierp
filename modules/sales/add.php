<?php
/**
 * صفحة إضافة فاتورة مبيعات جديدة
 * Sales Module - Add New Invoice
 * تم تحديثه: إضافة اختيار المخزن، مندوب التوصيل، بيانات السيارة، مندوب التعاقد
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../api/config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
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

$pageTitle = 'فاتورة مبيعات جديدة';

// جلب البيانات
$customers = $db->fetchAll("SELECT * FROM customers WHERE company_id = ? AND status = 'active' ORDER BY name ASC", [$company['id']]);
$products = $db->fetchAll("SELECT * FROM products WHERE company_id = ? AND is_active = 1 ORDER BY name ASC", [$company['id']]);
$warehouses = $db->fetchAll("SELECT * FROM warehouses WHERE company_id = ? AND status = 'active' ORDER BY is_default DESC, name", [$company['id']]);

// جلب مندوبي التعاقد
$salesAgents = [];
try {
    $salesAgents = $db->fetchAll("SELECT * FROM sales_agents WHERE company_id = ? AND is_active = 1 ORDER BY name", [$company['id']]);
} catch (Exception $e) {
    // الجدول قد لا يكون موجوداً بعد
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
        .credit-warning {
            background: var(--danger);
            color: white;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: none;
        }
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
                           class="nav-link <?= $module['slug'] === 'sales' ? 'active' : '' ?>">
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

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1><i class="fas fa-plus"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <button class="menu-toggle-btn" onclick="toggleSidebar()" title="القائمة">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="customers.php?action=add" class="btn btn-outline" target="_blank">
                        <i class="fas fa-user-plus"></i> إضافة عميل
                    </a>
                    <button type="submit" form="invoiceForm" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ الفاتورة
                    </button>
                    <a href="index.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item"><i class="fas fa-list"></i><span>الفواتير</span></a>
                        <a href="add.php" class="submenu-item active"><i class="fas fa-plus"></i><span>فاتورة جديدة</span></a>
                        <a href="customers.php" class="submenu-item"><i class="fas fa-users"></i><span>العملاء</span></a>
                    </div>
                </div>
                <!-- تحذير الحد الائتماني -->
                <div id="creditWarning" class="credit-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span id="creditWarningText"></span>
                </div>
                
                <form id="invoiceForm" class="invoice-form">
                    <div class="row">
                        <!-- Invoice Details -->
                        <div class="col-8">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-box"></i> تفاصيل المنتجات</h3>
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
                                                    <select name="items[0][product_id]" class="form-control product-select" required onchange="updatePrice(this)">
                                                        <option value="">اختر منتج...</option>
                                                        <?php foreach ($products as $product): ?>
                                                        <option value="<?= $product['id'] ?>" data-price="<?= $product['selling_price'] ?>">
                                                            <?= htmlspecialchars($product['name']) ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" name="items[0][quantity]" class="form-control quantity-input" value="1" min="0.01" step="0.01" onchange="calculateRow(this)">
                                                </td>
                                                <td>
                                                    <input type="number" name="items[0][price]" class="form-control price-input" step="0.01" onchange="calculateRow(this)">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control total-input" readonly>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger remove-row" onclick="removeRow(this)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="5">
                                                    <button type="button" class="btn btn-sm btn-outline" onclick="addRow()">
                                                        <i class="fas fa-plus"></i> إضافة منتج
                                                    </button>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Settings -->
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
                                        <small class="text-muted">سيتم خصم الأصناف من هذا المخزن</small>
                                    </div>
                                    
                                    <!-- العميل -->
                                    <div class="form-group mb-3">
                                        <label><i class="fas fa-user"></i> العميل <span style="color: var(--danger);">*</span></label>
                                        <div class="d-flex gap-1">
                                            <select name="customer_id" id="customerId" class="form-control" required onchange="checkCreditLimit()">
                                                <option value="" data-credit="0" data-balance="0">اختر عميل...</option>
                                                <?php foreach ($customers as $customer): ?>
                                                <option value="<?= $customer['id'] ?>" 
                                                        data-credit="<?= $customer['credit_limit'] ?>"
                                                        data-balance="<?= $customer['balance'] ?>">
                                                    <?= htmlspecialchars($customer['name']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <a href="customers.php?action=add" class="btn btn-outline" title="إضافة عميل">
                                                <i class="fas fa-plus"></i>
                                            </a>
                                        </div>
                                        <small id="customerBalance" class="text-muted"></small>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label><i class="fas fa-calendar"></i> تاريخ الفاتورة</label>
                                        <input type="date" name="invoice_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- بيانات التوصيل والمندوب -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-truck"></i> بيانات التوصيل</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group mb-3">
                                        <label><i class="fas fa-user-tie"></i> مندوب التعاقد</label>
                                        <select name="sales_agent_id" class="form-control">
                                            <option value="">اختر مندوب...</option>
                                            <?php foreach ($salesAgents as $agent): ?>
                                            <option value="<?= $agent['id'] ?>"><?= htmlspecialchars($agent['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label><i class="fas fa-motorcycle"></i> مندوب التوصيل</label>
                                        <input type="text" name="delivery_driver_name" class="form-control" placeholder="اسم مندوب التوصيل">
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label><i class="fas fa-car"></i> بيانات السيارة</label>
                                        <input type="text" name="vehicle_info" class="form-control" placeholder="نوع السيارة / رقم اللوحة">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- الحساب -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-calculator"></i> الحساب</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group mb-3">
                                        <label>ملاحظات</label>
                                        <textarea name="notes" class="form-control" rows="2"></textarea>
                                    </div>

                                    <!-- التحكم بالضريبة -->
                                    <div class="form-group mb-3">
                                        <div class="d-flex justify-between align-center">
                                            <div class="form-check">
                                                <input type="checkbox" id="applyTax" name="apply_tax" checked onchange="calculateTotals()">
                                                <label for="applyTax">تطبيق الضريبة</label>
                                            </div>
                                            <div style="width: 100px;">
                                                <input type="number" id="taxRate" name="tax_rate" class="form-control form-control-sm" value="15" min="0" max="100" step="0.5" onchange="calculateTotals()">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- الخصم -->
                                    <div class="form-group mb-3">
                                        <label>الخصم</label>
                                        <div class="d-flex gap-2">
                                            <select id="discountType" name="discount_type" class="form-control" style="width: 100px;" onchange="calculateTotals()">
                                                <option value="fixed">ثابت</option>
                                                <option value="percent">%</option>
                                            </select>
                                            <input type="number" id="discountValue" name="discount_value" class="form-control" value="0" min="0" step="0.01" onchange="calculateTotals()">
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="summary-row d-flex justify-between mb-2">
                                        <span>المجموع الفرعي:</span>
                                        <span id="subtotal">0.00</span>
                                    </div>
                                    <div class="summary-row d-flex justify-between mb-2" id="discountRow" style="display: none;">
                                        <span>الخصم:</span>
                                        <span id="discountAmount" style="color: var(--danger);">-0.00</span>
                                    </div>
                                    <div class="summary-row d-flex justify-between mb-2" id="taxRow">
                                        <span>الضريبة (<span id="taxRateDisplay">15</span>%):</span>
                                        <span id="tax">0.00</span>
                                    </div>
                                    <div class="summary-row d-flex justify-between font-bold" style="font-size: 1.2em; padding-top: 10px; border-top: 2px solid var(--border-color);">
                                        <span>الإجمالي النهائي:</span>
                                        <span id="total" style="color: var(--primary);">0.00</span>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-money-bill"></i> الدفع</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group mb-3">
                                        <label>المبلغ المدفوع</label>
                                        <input type="number" name="paid_amount" class="form-control" step="0.01" value="0.00" onchange="checkCreditLimit()">
                                    </div>
                                    <div class="form-group">
                                        <label>طريقة الدفع</label>
                                        <select name="payment_method" class="form-control">
                                            <option value="cash">نقداً</option>
                                            <option value="card">بطاقة</option>
                                            <option value="transfer">تحويل بنكي</option>
                                            <option value="credit">آجل</option>
                                        </select>
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
        
        // بيانات العملاء للتحقق من الحد الائتماني
        const customersData = <?= json_encode(array_map(function($c) {
            return ['id' => $c['id'], 'credit_limit' => $c['credit_limit'], 'balance' => $c['balance']];
        }, $customers)) ?>;

        function updatePrice(select) {
            const row = select.closest('tr');
            const price = select.options[select.selectedIndex].dataset.price || 0;
            row.querySelector('.price-input').value = price;
            calculateRow(select);
        }

        function calculateRow(element) {
            const row = element.closest('tr');
            const qty = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const total = qty * price;
            
            row.querySelector('.total-input').value = total.toFixed(2);
            calculateTotals();
        }

        function calculateTotals() {
            let subtotal = 0;
            document.querySelectorAll('.total-input').forEach(input => {
                subtotal += parseFloat(input.value) || 0;
            });

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
            document.getElementById('subtotal').textContent = subtotal.toFixed(2);
            document.getElementById('taxRateDisplay').textContent = taxRate;
            document.getElementById('tax').textContent = tax.toFixed(2);
            document.getElementById('total').textContent = total.toFixed(2);
            
            // عرض/إخفاء صف الخصم
            const discountRow = document.getElementById('discountRow');
            if (discountAmount > 0) {
                discountRow.style.display = 'flex';
                document.getElementById('discountAmount').textContent = '-' + discountAmount.toFixed(2);
            } else {
                discountRow.style.display = 'none';
            }
            
            // عرض/إخفاء صف الضريبة
            document.getElementById('taxRow').style.display = applyTax ? 'flex' : 'none';
            
            // التحقق من الحد الائتماني
            checkCreditLimit();
        }
        
        function checkCreditLimit() {
            const customerSelect = document.getElementById('customerId');
            const selectedOption = customerSelect.options[customerSelect.selectedIndex];
            const creditLimit = parseFloat(selectedOption.dataset.credit) || 0;
            const currentBalance = parseFloat(selectedOption.dataset.balance) || 0;
            const total = parseFloat(document.getElementById('total').textContent) || 0;
            const paidAmount = parseFloat(document.querySelector('[name="paid_amount"]').value) || 0;
            
            const newBalance = currentBalance + total - paidAmount;
            const warningDiv = document.getElementById('creditWarning');
            const balanceDiv = document.getElementById('customerBalance');
            
            // عرض الرصيد الحالي
            if (customerSelect.value) {
                balanceDiv.innerHTML = `الرصيد الحالي: <strong>${currentBalance.toFixed(2)}</strong>`;
                if (creditLimit > 0) {
                    balanceDiv.innerHTML += ` | الحد الائتماني: <strong>${creditLimit.toFixed(2)}</strong>`;
                }
            } else {
                balanceDiv.innerHTML = '';
            }
            
            // التحقق من تجاوز الحد الائتماني
            if (creditLimit > 0 && newBalance > creditLimit) {
                warningDiv.style.display = 'block';
                document.getElementById('creditWarningText').textContent = 
                    `تحذير: الرصيد الجديد (${newBalance.toFixed(2)}) سيتجاوز الحد الائتماني (${creditLimit.toFixed(2)})`;
            } else {
                warningDiv.style.display = 'none';
            }
        }

        function addRow() {
            const tbody = document.querySelector('#itemsTable tbody');
            const newRow = tbody.rows[0].cloneNode(true);
            
            // Reset values
            newRow.querySelector('select').name = `items[${rowCount}][product_id]`;
            newRow.querySelector('select').value = '';
            newRow.querySelector('.quantity-input').name = `items[${rowCount}][quantity]`;
            newRow.querySelector('.quantity-input').value = 1;
            newRow.querySelector('.price-input').name = `items[${rowCount}][price]`;
            newRow.querySelector('.price-input').value = '';
            newRow.querySelector('.total-input').value = '';
            
            tbody.appendChild(newRow);
            rowCount++;
        }

        function removeRow(btn) {
            if (document.querySelectorAll('#itemsTable tbody tr').length > 1) {
                btn.closest('tr').remove();
                calculateTotals();
            }
        }

        // Form Submission
        document.getElementById('invoiceForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // التحقق من المخزن
            const warehouseId = document.getElementById('warehouseId').value;
            if (!warehouseId) {
                alert('يجب اختيار المخزن');
                return;
            }
            
            const formData = new FormData(e.target);
            
            const submitData = {
                warehouse_id: warehouseId,
                customer_id: formData.get('customer_id'),
                invoice_date: formData.get('invoice_date'),
                notes: formData.get('notes'),
                sales_agent_id: formData.get('sales_agent_id') || null,
                delivery_driver_name: formData.get('delivery_driver_name') || null,
                vehicle_info: formData.get('vehicle_info') || null,
                tax_rate: formData.get('tax_rate'),
                discount_type: formData.get('discount_type'),
                discount_value: formData.get('discount_value'),
                paid_amount: formData.get('paid_amount'),
                payment_method: formData.get('payment_method'),
                items: []
            };

            const rows = document.querySelectorAll('#itemsTable tbody tr');
            rows.forEach((row, index) => {
                const productId = row.querySelector('select').value;
                if(productId) {
                    submitData.items.push({
                        product_id: productId,
                        quantity: row.querySelector('.quantity-input').value,
                        unit_price: row.querySelector('.price-input').value
                    });
                }
            });

            if(submitData.items.length === 0) {
                alert('الرجاء اختيار منتج واحد على الأقل');
                return;
            }

            try {
                const response = await fetch('../../api/v1/sales/invoices.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(submitData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = 'view.php?id=' + result.data.id;
                } else {
                    alert(result.message || 'حدث خطأ أثناء حفظ الفاتورة');
                }
            } catch (error) {
                console.error(error);
                alert('حدث خطأ في الاتصال');
            }
        });
    </script>
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
