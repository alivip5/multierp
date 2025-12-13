<?php
/**
 * إدارة مندوبي التعاقد
 * Sales Agents Management
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../api/config/config.php';
require_once __DIR__ . '/../../includes/SidebarHelper.php';
require_once __DIR__ . '/../../includes/Security.php';

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

// التحقق من الصلاحيات
if (!Auth::can('settings.view') && !in_array($user['role_slug'], ['super_admin', 'manager', 'sales_manager'])) {
    header('Location: ../../pages/dashboard.php');
    exit;
}

// الموديولات للقائمة الجانبية
$enabledModules = getSidebarItems($company['id'], $_SESSION['user_id']);

$pageTitle = 'مندوبي التعاقد';
$success = '';
$error = '';

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $commissionRate = (float)($_POST['commission_rate'] ?? 0);
        $commissionType = $_POST['commission_type'] ?? 'percentage';
        $notes = trim($_POST['notes'] ?? '');
        
        if ($name) {
            try {
                // توليد الكود تلقائياً إذا لم يحدد
                if (!$code) {
                    $lastAgent = $db->fetch("SELECT code FROM sales_agents WHERE company_id = ? ORDER BY id DESC LIMIT 1", [$company_id]);
                    $nextNum = 1;
                    if ($lastAgent && preg_match('/(\d+)$/', $lastAgent['code'], $m)) {
                        $nextNum = (int)$m[1] + 1;
                    }
                    $code = 'AG-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
                }
                
                $db->insert('sales_agents', [
                    'company_id' => $company_id,
                    'code' => $code,
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'address' => $address,
                    'commission_rate' => $commissionRate,
                    'commission_type' => $commissionType,
                    'notes' => $notes,
                    'is_active' => 1,
                    'created_by' => $_SESSION['user_id']
                ]);
                $success = 'تم إضافة المندوب بنجاح';
            } catch (Exception $e) {
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        } else {
            $error = 'اسم المندوب مطلوب';
        }
    }
    
    elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $commissionRate = (float)($_POST['commission_rate'] ?? 0);
        $commissionType = $_POST['commission_type'] ?? 'percentage';
        $notes = trim($_POST['notes'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if ($id && $name) {
            try {
                $db->update('sales_agents', [
                    'code' => $code,
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'address' => $address,
                    'commission_rate' => $commissionRate,
                    'commission_type' => $commissionType,
                    'notes' => $notes,
                    'is_active' => $isActive
                ], 'id = ? AND company_id = ?', [$id, $company_id]);
                $success = 'تم تحديث المندوب بنجاح';
            } catch (Exception $e) {
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            // التحقق من عدم وجود فواتير مرتبطة
            $invoiceCount = $db->fetch("SELECT COUNT(*) as count FROM sales_invoices WHERE sales_agent_id = ?", [$id]);
            if ($invoiceCount['count'] > 0) {
                $error = 'لا يمكن حذف المندوب لوجود فواتير مرتبطة به. يمكنك تعطيله بدلاً من ذلك.';
            } else {
                $db->query("DELETE FROM sales_agents WHERE id = ? AND company_id = ?", [$id, $company_id]);
                $success = 'تم حذف المندوب بنجاح';
            }
        }
    }
}

// جلب المندوبين
$agents = [];
try {
    $agents = $db->fetchAll(
        "SELECT sa.*, 
                (SELECT COUNT(*) FROM sales_invoices si WHERE si.sales_agent_id = sa.id) as invoice_count,
                (SELECT COALESCE(SUM(total), 0) FROM sales_invoices si WHERE si.sales_agent_id = sa.id) as total_sales
         FROM sales_agents sa
         WHERE sa.company_id = ?
         ORDER BY sa.name",
        [$company_id]
    );
} catch (Exception $e) {}

// المندوب المحدد للتعديل
$editAgent = null;
if (isset($_GET['edit'])) {
    $editAgent = $db->fetch("SELECT * FROM sales_agents WHERE id = ? AND company_id = ?", [(int)$_GET['edit'], $company_id]);
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
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal.active { display: flex; }
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
                        <a href="<?= $module['slug'] === 'dashboard' ? '../../pages/dashboard.php' : ($module['slug'] === 'settings' ? '../../pages/settings.php' : '../' . $module['slug'] . '/index.php') ?>" 
                           class="nav-link <?= $module['slug'] === 'settings' ? 'active' : '' ?>">
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
                    <h1><i class="fas fa-user-tie"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <button class="menu-toggle-btn" onclick="toggleSidebar()" title="القائمة">
                        <i class="fas fa-bars"></i>
                    </button>
                    <button class="btn btn-primary" onclick="showModal('addModal')">
                        <i class="fas fa-plus"></i> إضافة مندوب
                    </button>
                    <a href="../../pages/settings.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="../../pages/settings.php" class="submenu-item"><i class="fas fa-cog"></i><span>الإعدادات العامة</span></a>
                        <a href="branches.php" class="submenu-item"><i class="fas fa-code-branch"></i><span>الفروع والمخازن</span></a>
                        <a href="users.php" class="submenu-item"><i class="fas fa-users"></i><span>المستخدمين</span></a>
                        <a href="roles.php" class="submenu-item"><i class="fas fa-user-shield"></i><span>الأدوار</span></a>
                        <a href="sales_agents.php" class="submenu-item active"><i class="fas fa-user-tie"></i><span>المندوبين</span></a>
                    </div>
                </div>

                <?php if ($success): ?><div class="alert alert-success mb-3"><?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger mb-3"><?= $error ?></div><?php endif; ?>
                
                <div class="card">
                    <div class="card-body p-0">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>الكود</th>
                                    <th>الاسم</th>
                                    <th>الهاتف</th>
                                    <th>نسبة العمولة</th>
                                    <th>عدد الفواتير</th>
                                    <th>إجمالي المبيعات</th>
                                    <th>الحالة</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($agents)): ?>
                                <tr><td colspan="8" class="text-center text-muted p-3">لا يوجد مندوبين. أضف مندوباً جديداً.</td></tr>
                                <?php else: ?>
                                <?php foreach ($agents as $agent): ?>
                                <tr>
                                    <td><?= htmlspecialchars($agent['code'] ?? '-') ?></td>
                                    <td><strong><?= htmlspecialchars($agent['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($agent['phone'] ?? '-') ?></td>
                                    <td>
                                        <?= $agent['commission_rate'] ?>
                                        <?= $agent['commission_type'] === 'percentage' ? '%' : ' (ثابت)' ?>
                                    </td>
                                    <td><?= $agent['invoice_count'] ?></td>
                                    <td><?= number_format($agent['total_sales'], 2) ?></td>
                                    <td>
                                        <?php if ($agent['is_active']): ?>
                                        <span class="badge badge-success">نشط</span>
                                        <?php else: ?>
                                        <span class="badge badge-secondary">معطل</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?edit=<?= $agent['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('هل أنت متأكد؟')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $agent['id'] ?>">
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
        </main>
    </div>

    <!-- Modal الإضافة -->
    <div id="addModal" class="modal">
        <div class="card" style="width:500px; max-width:95%;">
            <div class="card-header d-flex justify-between">
                <h3 class="card-title"><i class="fas fa-plus"></i> إضافة مندوب جديد</h3>
                <button onclick="hideModal('addModal')" class="btn btn-sm btn-outline">&times;</button>
            </div>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label>الاسم <span style="color: var(--danger);">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group mb-3">
                                <label>الكود</label>
                                <input type="text" name="code" class="form-control" placeholder="سيتم توليده تلقائياً">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group mb-3">
                                <label>الهاتف</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label>البريد الإلكتروني</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group mb-3">
                                <label>نسبة/قيمة العمولة</label>
                                <input type="number" name="commission_rate" class="form-control" value="0" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group mb-3">
                                <label>نوع العمولة</label>
                                <select name="commission_type" class="form-control">
                                    <option value="percentage">نسبة مئوية</option>
                                    <option value="fixed">مبلغ ثابت</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label>ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($editAgent): ?>
    <!-- Modal التعديل -->
    <div id="editModal" class="modal active">
        <div class="card" style="width:500px; max-width:95%;">
            <div class="card-header d-flex justify-between">
                <h3 class="card-title"><i class="fas fa-edit"></i> تعديل المندوب</h3>
                <a href="sales_agents.php" class="btn btn-sm btn-outline">&times;</a>
            </div>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= $editAgent['id'] ?>">
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label>الاسم <span style="color: var(--danger);">*</span></label>
                        <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editAgent['name']) ?>">
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group mb-3">
                                <label>الكود</label>
                                <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($editAgent['code'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group mb-3">
                                <label>الهاتف</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($editAgent['phone'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label>البريد الإلكتروني</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editAgent['email'] ?? '') ?>">
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group mb-3">
                                <label>نسبة/قيمة العمولة</label>
                                <input type="number" name="commission_rate" class="form-control" value="<?= $editAgent['commission_rate'] ?>" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group mb-3">
                                <label>نوع العمولة</label>
                                <select name="commission_type" class="form-control">
                                    <option value="percentage" <?= $editAgent['commission_type'] === 'percentage' ? 'selected' : '' ?>>نسبة مئوية</option>
                                    <option value="fixed" <?= $editAgent['commission_type'] === 'fixed' ? 'selected' : '' ?>>مبلغ ثابت</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label>ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($editAgent['notes'] ?? '') ?></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="isActive" <?= $editAgent['is_active'] ? 'checked' : '' ?>>
                        <label for="isActive">نشط</label>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

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
        function showModal(id) {
            document.getElementById(id).style.display = 'flex';
        }
        function hideModal(id) {
            document.getElementById(id).style.display = 'none';
        }
    </script>
</body>
</html>
