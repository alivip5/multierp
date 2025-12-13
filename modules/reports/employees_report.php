<?php
/**
 * تقرير الموظفين
 * Reports Module - Employees Report
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

$pageTitle = 'تقرير الموظفين';

// إحصائيات عامة
$stats = [];
try {
    $stats = $db->fetch(
        "SELECT 
            COUNT(*) as total_employees,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'on_leave' THEN 1 ELSE 0 END) as on_leave,
            SUM(CASE WHEN status = 'terminated' THEN 1 ELSE 0 END) as terminated,
            COALESCE(SUM(salary), 0) as total_salaries,
            COALESCE(AVG(salary), 0) as avg_salary
         FROM employees 
         WHERE company_id = ?",
        [$company_id]
    ) ?: [];
} catch (Exception $e) {
    $stats = ['total_employees' => 0, 'active' => 0, 'on_leave' => 0, 'terminated' => 0, 'total_salaries' => 0, 'avg_salary' => 0];
}

// حسب القسم
$byDepartment = [];
try {
    $byDepartment = $db->fetchAll(
        "SELECT d.name as department, COUNT(e.id) as count, COALESCE(SUM(e.salary), 0) as total_salary
         FROM employees e
         LEFT JOIN departments d ON e.department_id = d.id
         WHERE e.company_id = ?
         GROUP BY e.department_id
         ORDER BY count DESC",
        [$company_id]
    );
} catch (Exception $e) {}

// حسب نوع العقد
$byContract = [];
try {
    $byContract = $db->fetchAll(
        "SELECT contract_type, COUNT(*) as count, SUM(salary) as total_salary
         FROM employees 
         WHERE company_id = ?
         GROUP BY contract_type",
        [$company_id]
    );
} catch (Exception $e) {}

// قائمة الموظفين
$employees = [];
try {
    $employees = $db->fetchAll(
        "SELECT e.*, d.name as department_name, p.name as position_name
         FROM employees e
         LEFT JOIN departments d ON e.department_id = d.id
         LEFT JOIN positions p ON e.position_id = p.id
         WHERE e.company_id = ?
         ORDER BY e.first_name",
        [$company_id]
    );
} catch (Exception $e) {}

$contractLabels = [
    'permanent' => 'دائم',
    'contract' => 'مؤقت',
    'part_time' => 'جزئي',
    'probation' => 'تحت التجربة'
];
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
        @media print { 
            .sidebar, .header-actions, .no-print, .module-submenu, .sidebar-toggle { display: none !important; } 
            .main-content { margin: 0 !important; width: 100% !important; padding: 20px !important; } 
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
                           class="nav-link <?= $module['slug'] === 'reports' ? 'active' : '' ?>">
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
                <div class="header-actions no-print">
                    <button onclick="window.print()" class="btn btn-outline"><i class="fas fa-print"></i> طباعة</button>
                    <a href="index.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu no-print">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item"><i class="fas fa-chart-pie"></i><span>لوحة التقارير</span></a>
                        <a href="employees_report.php" class="submenu-item active"><i class="fas fa-user-tie"></i><span>الموظفين</span></a>
                    </div>
                </div>

                <!-- إحصائيات -->
                <div class="row mb-3">
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon primary"><i class="fas fa-users"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= $stats['total_employees'] ?? 0 ?></div>
                                <div class="stat-label">إجمالي الموظفين</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon success"><i class="fas fa-user-check"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= $stats['active'] ?? 0 ?></div>
                                <div class="stat-label">نشطين</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon warning"><i class="fas fa-money-bill-wave"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= number_format($stats['total_salaries'] ?? 0, 0) ?></div>
                                <div class="stat-label">إجمالي الرواتب</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon info"><i class="fas fa-calculator"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= number_format($stats['avg_salary'] ?? 0, 0) ?></div>
                                <div class="stat-label">متوسط الراتب</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-6">
                        <div class="card">
                            <div class="card-header"><h3 class="card-title">حسب القسم</h3></div>
                            <div class="card-body p-0">
                                <table class="table">
                                    <thead><tr><th>القسم</th><th>العدد</th><th>إجمالي الرواتب</th></tr></thead>
                                    <tbody>
                                        <?php if (empty($byDepartment)): ?>
                                        <tr><td colspan="3" class="text-center text-muted">لا توجد بيانات</td></tr>
                                        <?php else: ?>
                                        <?php foreach ($byDepartment as $d): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($d['department'] ?? 'غير محدد') ?></td>
                                            <td><?= $d['count'] ?></td>
                                            <td><?= number_format($d['total_salary'], 0) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card">
                            <div class="card-header"><h3 class="card-title">حسب نوع العقد</h3></div>
                            <div class="card-body p-0">
                                <table class="table">
                                    <thead><tr><th>نوع العقد</th><th>العدد</th><th>إجمالي الرواتب</th></tr></thead>
                                    <tbody>
                                        <?php if (empty($byContract)): ?>
                                        <tr><td colspan="3" class="text-center text-muted">لا توجد بيانات</td></tr>
                                        <?php else: ?>
                                        <?php foreach ($byContract as $c): ?>
                                        <tr>
                                            <td><?= $contractLabels[$c['contract_type']] ?? $c['contract_type'] ?></td>
                                            <td><?= $c['count'] ?></td>
                                            <td><?= number_format($c['total_salary'], 0) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- قائمة الموظفين -->
                <div class="card">
                    <div class="card-header"><h3 class="card-title">قائمة الموظفين</h3></div>
                    <div class="card-body p-0">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>القسم</th>
                                    <th>المنصب</th>
                                    <th>تاريخ التعيين</th>
                                    <th>نوع العقد</th>
                                    <th>الراتب</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($employees)): ?>
                                <tr><td colspan="7" class="text-center text-muted p-3">لا يوجد موظفين</td></tr>
                                <?php else: ?>
                                <?php foreach ($employees as $e): ?>
                                <tr>
                                    <td><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?></td>
                                    <td><?= htmlspecialchars($e['department_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($e['position_name'] ?? '-') ?></td>
                                    <td><?= $e['hire_date'] ?? '-' ?></td>
                                    <td><?= $contractLabels[$e['contract_type']] ?? '-' ?></td>
                                    <td><?= number_format($e['salary'], 0) ?></td>
                                    <td>
                                        <?php
                                        $statusClass = match($e['status']) {
                                            'active' => 'success',
                                            'on_leave' => 'warning',
                                            default => 'secondary'
                                        };
                                        $statusText = match($e['status']) {
                                            'active' => 'نشط',
                                            'on_leave' => 'إجازة',
                                            'terminated' => 'منتهي',
                                            default => $e['status']
                                        };
                                        ?>
                                        <span class="badge badge-<?= $statusClass ?>"><?= $statusText ?></span>
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

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>
</body>
</html>
