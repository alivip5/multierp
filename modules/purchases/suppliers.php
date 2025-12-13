<?php
/**
 * إدارة الموردين
 * Suppliers Management
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/SidebarHelper.php';

$db = Database::getInstance();
$company_id = $_SESSION['company_id'] ?? 1;
$user = $db->fetch("SELECT u.*, r.name as role_slug, r.name_ar as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?", [$_SESSION['user_id']]);
$company = $db->fetch("SELECT * FROM companies WHERE id = ?", [$company_id]);

// التأكد من وجود بيانات الدور في الجلسة
if (!isset($_SESSION['role_id']) && $user) {
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_slug'];
}

// الموديولات للقائمة الجانبية
$enabledModules = getSidebarItems($company['id'], $_SESSION['user_id']);

// Handle Add/Edit/Delete actions
$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $id = $_POST['id'] ?? null;

    if ($name) {
        if ($id) {
            $db->update('suppliers', compact('name', 'phone', 'email'), "id = ? AND company_id = ?", [(int)$id, (int)$company_id]);
            $success = 'تم تحديث بيانات المورد بنجاح';
        } else {
            $conn = $db->getConnection();
            $stmt = $conn->prepare("INSERT INTO suppliers (company_id, name, phone, email, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$company_id, $name, $phone, $email]);
            $success = 'تم إضافة المورد بنجاح';
        }
    } else {
        $error = 'اسم المورد مطلوب';
    }
}

if ($action === 'delete' && isset($_GET['id'])) {
    $supplierIdToDelete = (int)$_GET['id'];
    $db->delete('suppliers', "id = ? AND company_id = ?", [$supplierIdToDelete, (int)$company_id]);
    header('Location: suppliers.php');
    exit;
}

$suppliers = $db->fetchAll("SELECT * FROM suppliers WHERE company_id = ? ORDER BY name ASC", [$company_id]);
$pageTitle = 'إدارة الموردين';
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
                    <h1><i class="fas fa-users"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal()">
                        <i class="fas fa-plus"></i> مورد جديد
                    </button>
                    <a href="index.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item"><i class="fas fa-list"></i><span>الفواتير</span></a>
                        <a href="add.php" class="submenu-item"><i class="fas fa-plus"></i><span>فاتورة جديدة</span></a>
                        <a href="suppliers.php" class="submenu-item active"><i class="fas fa-truck"></i><span>الموردين</span></a>
                    </div>
                </div>

                <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>الاسم</th>
                                        <th>الهاتف</th>
                                        <th>البريد الإلكتروني</th>
                                        <th>الحالة</th>
                                        <th>إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($suppliers)): ?>
                                    <tr><td colspan="5" class="text-center p-3 text-muted">لا يوجد موردين</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($suppliers as $s): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($s['name']) ?></td>
                                        <td><?= htmlspecialchars($s['phone']) ?></td>
                                        <td><?= htmlspecialchars($s['email']) ?></td>
                                        <td><span class="badge badge-<?= $s['status'] === 'active' ? 'success' : 'danger' ?>"><?= $s['status'] === 'active' ? 'نشط' : 'غير نشط' ?></span></td>
                                        <td>
                                            <a href="suppliers.php?action=delete&id=<?= $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد؟')"><i class="fas fa-trash"></i></a>
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

    <!-- Add Supplier Modal -->
    <div id="addSupplierModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="modal-content" style="background: var(--bg-surface); width: 100%; max-width: 500px; padding: 20px; border-radius: 8px;">
            <h3>مورد جديد</h3>
            <form method="POST">
                <div class="mb-3">
                    <label>الاسم</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>الهاتف</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="mb-3">
                    <label>البريد الإلكتروني</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="text-end">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
        
        function openModal() {
            document.getElementById('addSupplierModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('addSupplierModal').style.display = 'none';
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
