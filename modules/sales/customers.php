<?php
/**
 * صفحة إدارة العملاء
 * Sales Module - Customers Management
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

$pageTitle = 'العملاء';
$success = '';
$error = '';

// Search Logic
$search = $_GET['search'] ?? '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';

    if ($name) {
        if ($id) {
            // Edit
            $db->update('customers', [
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'address' => $address
            ], "id = ? AND company_id = ?", [$id, $company['id']]);
            $success = "تم تحديث بيانات العميل بنجاح";
        } else {
            // Add
            try {
                $db->insert('customers', [
                    'company_id' => $company['id'],
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'address' => $address
                ]);
                $success = "تم إضافة العميل بنجاح";
            } catch (Exception $e) {
                $error = "خطأ: " . $e->getMessage();
            }
        }
    }
}

// Fetch Customers
$where = "company_id = ?";
$params = [$company['id']];

if ($search) {
    $where .= " AND (name LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$customers = $db->fetchAll("SELECT * FROM customers WHERE $where ORDER BY created_at DESC", $params);

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
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: var(--surface); padding: 20px; border-radius: var(--radius-lg); width: 100%; max-width: 500px; }
        
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
                    <h1><i class="fas fa-users"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <button class="menu-toggle-btn" onclick="toggleSidebar()" title="القائمة">
                        <i class="fas fa-bars"></i>
                    </button>
                    <button onclick="openModal()" class="btn btn-primary">
                        <i class="fas fa-plus"></i> عميل جديد
                    </button>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item"><i class="fas fa-list"></i><span>الفواتير</span></a>
                        <a href="add.php" class="submenu-item"><i class="fas fa-plus"></i><span>فاتورة جديدة</span></a>
                        <a href="customers.php" class="submenu-item active"><i class="fas fa-users"></i><span>العملاء</span></a>
                    </div>
                </div>
                <?php if ($success): ?><div class="alert alert-success mb-3"><?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger mb-3"><?= $error ?></div><?php endif; ?>
                <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success mb-3">تم حذف العميل بنجاح</div><?php endif; ?>

                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control" placeholder="بحث عن عميل..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-outline"><i class="fas fa-search"></i></button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>الاسم</th>
                                        <th>الهاتف</th>
                                        <th>البريد الإلكتروني</th>
                                        <th>العنوان</th>
                                        <th>الرصيد</th>
                                        <th>إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($customers)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center p-3 text-muted">لا يوجد عملاء</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($customers as $c): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($c['name']) ?></td>
                                        <td><?= htmlspecialchars($c['phone'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($c['email'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($c['address'] ?? '-') ?></td>
                                        <td><?= number_format($c['balance'] ?? 0, 2) ?></td>
                                        <td>
                                            <button onclick='editCustomer(<?= json_encode($c) ?>)' class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></button>
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

    <!-- Add/Edit Modal -->
    <div id="customerModal" class="modal">
        <div class="modal-content">
            <h3 class="mb-3" id="modalTitle">عميل جديد</h3>
            <form id="customerForm" method="POST">
                <input type="hidden" name="id" id="customerId">
                <div class="form-group mb-3">
                    <label>الاسم *</label>
                    <input type="text" name="name" id="customerName" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label>الهاتف</label>
                    <input type="text" name="phone" id="customerPhone" class="form-control">
                </div>
                <div class="form-group mb-3">
                    <label>البريد الإلكتروني</label>
                    <input type="email" name="email" id="customerEmail" class="form-control">
                </div>
                <div class="form-group mb-3">
                    <label>العنوان</label>
                    <textarea name="address" id="customerAddress" class="form-control"></textarea>
                </div>
                <div class="d-flex justify-end gap-2">
                    <button type="button" onclick="closeModal()" class="btn btn-outline">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('customerModal').classList.add('active');
            document.getElementById('modalTitle').textContent = 'عميل جديد';
            document.getElementById('customerForm').reset();
            document.getElementById('customerId').value = '';
        }

        function closeModal() {
            document.getElementById('customerModal').classList.remove('active');
        }

        function editCustomer(data) {
            openModal();
            document.getElementById('modalTitle').textContent = 'تعديل بيانات عميل';
            document.getElementById('customerId').value = data.id || '';
            document.getElementById('customerName').value = data.name || '';
            document.getElementById('customerPhone').value = data.phone || '';
            document.getElementById('customerEmail').value = data.email || '';
            document.getElementById('customerAddress').value = data.address || '';
        }

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

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('action') === 'add') {
                openModal();
            }
        });
    </script>
</body>
</html>
