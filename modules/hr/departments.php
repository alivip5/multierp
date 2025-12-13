<?php
/**
 * إدارة الأقسام
 * HR Module - Departments Management
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../api/config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/SidebarHelper.php';
require_once __DIR__ . '/../../includes/Auth.php';

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

// التحقق من تفعيل الموديول
require_module($company['id'], 'hr');

$pageTitle = 'الأقسام';
$success = '';
$error = '';

// إنشاء جدول الأقسام إذا لم يكن موجوداً
$db->getConnection()->exec("CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    manager_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY(company_id)
)");

// معالجة النماذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            
            if ($name) {
                if ($_POST['action'] === 'edit' && !empty($_POST['id'])) {
                    $db->update('departments', [
                        'name' => $name,
                        'description' => $description
                    ], "id = ? AND company_id = ?", [$_POST['id'], $company['id']]);
                    $success = "تم تحديث القسم بنجاح";
                } else {
                    try {
                        $db->insert('departments', [
                            'company_id' => $company['id'],
                            'name' => $name,
                            'description' => $description
                        ]);
                        $success = "تم إضافة القسم بنجاح";
                    } catch (Exception $e) {
                         $error = "خطأ: " . $e->getMessage();
                    }
                }
            } else {
                $error = "اسم القسم مطلوب";
            }
        } elseif ($_POST['action'] === 'delete' && !empty($_POST['id'])) {
            // Check dependencies
            $empCount = $db->fetchColumn("SELECT COUNT(*) FROM employees WHERE department_id = ?", [$_POST['id']]);
            if ($empCount > 0) {
                $error = "لا يمكن حذف القسم لوجود موظفين مرتبطين به";
            } else {
                $db->delete('departments', "id = ? AND company_id = ?", [$_POST['id'], $company['id']]);
                $success = "تم حذف القسم بنجاح";
            }
        }
    }
}

$departments = $db->fetchAll("
    SELECT d.*, 
    (SELECT COUNT(*) FROM employees e WHERE e.department_id = d.id) as employee_count 
    FROM departments d 
    WHERE d.company_id = ? 
    ORDER BY d.name", [$company['id']]);
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
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: var(--surface); padding: 20px; border-radius: var(--radius-lg); width: 100%; max-width: 500px; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
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
                        <a href="<?= $module['slug'] === 'dashboard' ? '../../pages/dashboard.php' : ($module['slug'] === 'settings' ? '../../pages/settings.php' : '../' . $module['slug'] . '/index.php') ?>" 
                           class="nav-link <?= $module['slug'] === 'hr' ? 'active' : '' ?>">
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
                    <h1><i class="fas fa-sitemap"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <button onclick="openModal()" class="btn btn-primary"><i class="fas fa-plus"></i> قسم جديد</button>
                    <a href="index.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item"><i class="fas fa-home"></i><span>الرئيسية</span></a>
                        <a href="employees.php" class="submenu-item"><i class="fas fa-users"></i><span>الموظفين</span></a>
                        <a href="departments.php" class="submenu-item active"><i class="fas fa-sitemap"></i><span>الأقسام</span></a>
                    </div>
                </div>

                <?php if ($success): ?><div class="alert alert-success mb-3"><?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger mb-3"><?= $error ?></div><?php endif; ?>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>اسم القسم</th>
                                        <th>الوصف</th>
                                        <th>عدد الموظفين</th>
                                        <th>إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($departments)): ?>
                                    <tr><td colspan="4" class="text-center p-3 text-muted">لا توجد أقسام معرفة</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($departments as $dept): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($dept['name']) ?></strong></td>
                                        <td><?= htmlspecialchars($dept['description']) ?></td>
                                        <td><span class="badge bg-info"><?= $dept['employee_count'] ?></span></td>
                                        <td>
                                            <button onclick='editDepartment(<?= json_encode($dept) ?>)' class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا القسم؟')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $dept['id'] ?>">
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

    <!-- Modal -->
    <div id="deptModal" class="modal">
        <div class="modal-content">
            <h3 class="mb-3" id="modalTitle">إضافة قسم جديد</h3>
            <form id="deptForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="deptId">
                <div class="form-group mb-3">
                    <label>اسم القسم *</label>
                    <input type="text" name="name" id="deptName" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label>الوصف</label>
                    <textarea name="description" id="deptDesc" class="form-control" rows="3"></textarea>
                </div>
                <div class="d-flex justify-end gap-2">
                    <button type="button" onclick="closeModal()" class="btn btn-outline">إلغاء</button>
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
            document.getElementById('deptModal').classList.add('active');
            document.getElementById('modalTitle').innerText = 'إضافة قسم جديد';
            document.getElementById('formAction').value = 'add';
            document.getElementById('deptId').value = '';
            document.getElementById('deptName').value = '';
            document.getElementById('deptDesc').value = '';
        }

        function closeModal() {
            document.getElementById('deptModal').classList.remove('active');
        }

        function editDepartment(dept) {
            openModal();
            document.getElementById('modalTitle').innerText = 'تعديل قسم';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('deptId').value = dept.id;
            document.getElementById('deptName').value = dept.name;
            document.getElementById('deptDesc').value = dept.description || '';
        }
    </script>
</body>
</html>
