<?php
/**
 * قائمة الموظفين
 * HR Module - Employees List
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

$enabledModules = getSidebarItems($company['id'], $_SESSION['user_id']);
require_module($company_id, 'hr');

$pageTitle = 'قائمة الموظفين';

// معالجة الحذف
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $empId = (int)$_POST['delete_id'];
    $db->delete('employees', 'id = ? AND company_id = ?', [$empId, $company_id]);
    header('Location: employees.php?deleted=1');
    exit;
}

// الفلترة
$search = $_GET['search'] ?? '';
$department = $_GET['department'] ?? '';

$where = "e.company_id = ?";
$params = [$company_id];

if ($search) {
    $where .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($department) {
    $where .= " AND e.department_id = ?";
    $params[] = $department;
}

$employees = $db->fetchAll(
    "SELECT e.*, d.name as department_name 
     FROM employees e 
     LEFT JOIN departments d ON e.department_id = d.id 
     WHERE $where 
     ORDER BY e.created_at DESC",
    $params
);

$departments = $db->fetchAll("SELECT * FROM departments WHERE company_id = ? ORDER BY name", [$company_id]);

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
                    <h1><i class="fas fa-users"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <a href="add_employee.php" class="btn btn-primary"><i class="fas fa-plus"></i> موظف جديد</a>
                    <a href="index.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item"><i class="fas fa-home"></i><span>الرئيسية</span></a>
                        <a href="employees.php" class="submenu-item active"><i class="fas fa-users"></i><span>الموظفين</span></a>
                        <a href="departments.php" class="submenu-item"><i class="fas fa-sitemap"></i><span>الأقسام</span></a>
                    </div>
                </div>

                <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success mb-3">تم حذف الموظف بنجاح</div><?php endif; ?>

                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-2 flex-wrap">
                            <input type="text" name="search" class="form-control" placeholder="بحث بالاسم أو الرقم الوظيفي..." value="<?= htmlspecialchars($search) ?>" style="max-width: 300px;">
                            <select name="department" class="form-control" style="max-width: 200px;">
                                <option value="">كل الأقسام</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= $department == $dept['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-outline"><i class="fas fa-search"></i> بحث</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>رقم الموظف</th>
                                        <th>الاسم</th>
                                        <th>القسم</th>
                                        <th>الهاتف</th>
                                        <th>الحالة</th>
                                        <th>تاريخ التعيين</th>
                                        <th>إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($employees)): ?>
                                    <tr><td colspan="7" class="text-center p-3 text-muted">لا يوجد موظفين</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($employees as $emp): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($emp['employee_number'] ?? '-') ?></span></td>
                                        <td><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></td>
                                        <td><?= htmlspecialchars($emp['department_name'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($emp['mobile'] ?? '-') ?></td>
                                        <td>
                                            <?php
                                            $statusLabel = match($emp['status']) {
                                                'active' => 'نشط',
                                                'on_leave' => 'إجازة',
                                                'terminated' => 'منتهي',
                                                default => $emp['status']
                                            };
                                            $statusClass = match($emp['status']) {
                                                'active' => 'success',
                                                'on_leave' => 'warning',
                                                'terminated' => 'danger',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?>"><?= $statusLabel ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($emp['hire_date'] ?? '-') ?></td>
                                        <td>
                                            <a href="add_employee.php?id=<?= $emp['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد؟')">
                                                <input type="hidden" name="delete_id" value="<?= $emp['id'] ?>">
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

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>
</body>
</html>
