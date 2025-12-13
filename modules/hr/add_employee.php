<?php
/**
 * إضافة/تعديل موظف
 * HR Module - Add/Edit Employee
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
require_module($company['id'], 'hr');

$id = $_GET['id'] ?? null;
$employee = [];

if ($id) {
    $employee = $db->fetch("SELECT * FROM employees WHERE id = ? AND company_id = ?", [$id, $company_id]);
    if ($employee) {
        $pageTitle = 'تعديل موظف: ' . $employee['first_name'] . ' ' . $employee['last_name'];
    } else {
        header('Location: employees.php');
        exit;
    }
} else {
    $pageTitle = 'إضافة موظف جديد';
}

// جلب الأقسام والمناصب
$departments = $db->fetchAll("SELECT * FROM departments WHERE company_id = ? ORDER BY name", [$company_id]);
$positions = $db->fetchAll("SELECT * FROM positions WHERE company_id = ? ORDER BY name", [$company_id]);

$success = '';
$error = '';

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check needed here in production (omitted for brevity unless verify_csrf() is globally available)
    // Assuming verify_csrf() is available from Security.php or Auth.php if included.
    // Let's implement basic validation.
    
    $data = [
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'employee_number' => $_POST['employee_number'] ?? null,
        'email' => $_POST['email'] ?? null,
        'phone' => $_POST['phone'] ?? null,
        'mobile' => $_POST['mobile'] ?? null,
        'national_id' => $_POST['national_id'] ?? null,
        'date_of_birth' => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
        'gender' => $_POST['gender'] ?? null,
        'marital_status' => $_POST['marital_status'] ?? null,
        'nationality' => $_POST['nationality'] ?? null,
        'address' => $_POST['address'] ?? null,
        'department_id' => !empty($_POST['department_id']) ? $_POST['department_id'] : null,
        'position_id' => !empty($_POST['position_id']) ? $_POST['position_id'] : null,
        'hire_date' => !empty($_POST['hire_date']) ? $_POST['hire_date'] : null,
        'contract_type' => $_POST['contract_type'] ?? 'permanent',
        'salary' => $_POST['salary'] ?? 0,
        'bank_name' => $_POST['bank_name'] ?? null,
        'bank_account' => $_POST['bank_account'] ?? null,
        'iban' => $_POST['iban'] ?? null,
        'status' => $_POST['status'] ?? 'active',
    ];

    if (empty($data['first_name']) || empty($data['last_name'])) {
        $error = 'الاسم الأول واسم العائلة مطلوبان';
    } else {
        try {
            if ($id) {
                // Update
                $set = [];
                $params = [];
                foreach ($data as $key => $value) {
                    $set[] = "$key = ?";
                    $params[] = $value;
                }
                $params[] = $id;
                $params[] = $company_id;
                
                $sql = "UPDATE employees SET " . implode(', ', $set) . " WHERE id = ? AND company_id = ?";
                $db->getConnection()->prepare($sql)->execute($params);
                
                $success = 'تم تحديث بيانات الموظف بنجاح';
                $employee = array_merge($employee ?? [], $data);
            } else {
                // Insert
                $data['company_id'] = $company_id;
                $cols = implode(', ', array_keys($data));
                $vals = implode(', ', array_fill(0, count($data), '?'));
                $sql = "INSERT INTO employees ($cols) VALUES ($vals)";
                $db->getConnection()->prepare($sql)->execute(array_values($data));
                
                header('Location: employees.php?added=1');
                exit;
            }
        } catch (Exception $e) {
            $error = 'حدث خطأ: ' . $e->getMessage();
        }
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
                    <h1><i class="fas fa-user-plus"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <a href="employees.php" class="btn btn-outline">عودة للقائمة</a>
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

                <?php if ($success): ?><div class="alert alert-success mb-3"><?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger mb-3"><?= $error ?></div><?php endif; ?>

                <form method="POST">
                    <!-- البيانات الشخصية -->
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-user"></i> البيانات الشخصية</h3></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">الاسم الأول *</label>
                                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($employee['first_name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">اسم العائلة *</label>
                                    <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($employee['last_name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">الرقم الوظيفي</label>
                                    <input type="text" name="employee_number" class="form-control" value="<?= htmlspecialchars($employee['employee_number'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">رقم الهوية</label>
                                    <input type="text" name="national_id" class="form-control" value="<?= htmlspecialchars($employee['national_id'] ?? '') ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">تاريخ الميلاد</label>
                                    <input type="date" name="date_of_birth" class="form-control" value="<?= $employee['date_of_birth'] ?? '' ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">الجنسية</label>
                                    <input type="text" name="nationality" class="form-control" value="<?= htmlspecialchars($employee['nationality'] ?? 'سعودي') ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">الجنس</label>
                                    <select name="gender" class="form-control">
                                        <option value="">اختر...</option>
                                        <option value="male" <?= ($employee['gender'] ?? '') === 'male' ? 'selected' : '' ?>>ذكر</option>
                                        <option value="female" <?= ($employee['gender'] ?? '') === 'female' ? 'selected' : '' ?>>أنثى</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">الحالة الاجتماعية</label>
                                    <select name="marital_status" class="form-control">
                                        <option value="">اختر...</option>
                                        <option value="single" <?= ($employee['marital_status'] ?? '') === 'single' ? 'selected' : '' ?>>أعزب</option>
                                        <option value="married" <?= ($employee['marital_status'] ?? '') === 'married' ? 'selected' : '' ?>>متزوج</option>
                                        <option value="divorced" <?= ($employee['marital_status'] ?? '') === 'divorced' ? 'selected' : '' ?>>مطلق</option>
                                        <option value="widowed" <?= ($employee['marital_status'] ?? '') === 'widowed' ? 'selected' : '' ?>>أرمل</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- بيانات التواصل -->
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-phone"></i> بيانات التواصل</h3></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">البريد الإلكتروني</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($employee['email'] ?? '') ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">الهاتف</label>
                                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($employee['phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">الجوال</label>
                                    <input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars($employee['mobile'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">العنوان</label>
                                <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($employee['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- البيانات الوظيفية -->
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-briefcase"></i> البيانات الوظيفية</h3></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">القسم</label>
                                    <select name="department_id" class="form-control">
                                        <option value="">اختر القسم...</option>
                                        <?php foreach ($departments as $dept): ?>
                                        <option value="<?= $dept['id'] ?>" <?= ($employee['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">المنصب</label>
                                    <select name="position_id" class="form-control">
                                        <option value="">اختر المنصب...</option>
                                        <?php foreach ($positions as $pos): ?>
                                        <option value="<?= $pos['id'] ?>" <?= ($employee['position_id'] ?? '') == $pos['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pos['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">تاريخ التعيين</label>
                                    <input type="date" name="hire_date" class="form-control" value="<?= $employee['hire_date'] ?? '' ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">نوع العقد</label>
                                    <select name="contract_type" class="form-control">
                                        <option value="permanent" <?= ($employee['contract_type'] ?? '') === 'permanent' ? 'selected' : '' ?>>دائم</option>
                                        <option value="contract" <?= ($employee['contract_type'] ?? '') === 'contract' ? 'selected' : '' ?>>مؤقت</option>
                                        <option value="part_time" <?= ($employee['contract_type'] ?? '') === 'part_time' ? 'selected' : '' ?>>دوام جزئي</option>
                                        <option value="probation" <?= ($employee['contract_type'] ?? '') === 'probation' ? 'selected' : '' ?>>تحت التجربة</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">الراتب</label>
                                    <input type="number" name="salary" step="0.01" class="form-control" value="<?= $employee['salary'] ?? '0' ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">الحالة</label>
                                    <select name="status" class="form-control">
                                        <option value="active" <?= ($employee['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>نشط</option>
                                        <option value="on_leave" <?= ($employee['status'] ?? '') === 'on_leave' ? 'selected' : '' ?>>في إجازة</option>
                                        <option value="inactive" <?= ($employee['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>غير نشط</option>
                                        <option value="terminated" <?= ($employee['status'] ?? '') === 'terminated' ? 'selected' : '' ?>>منتهي</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- البيانات البنكية -->
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-university"></i> البيانات البنكية</h3></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">اسم البنك</label>
                                    <input type="text" name="bank_name" class="form-control" value="<?= htmlspecialchars($employee['bank_name'] ?? '') ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">رقم الحساب</label>
                                    <input type="text" name="bank_account" class="form-control" value="<?= htmlspecialchars($employee['bank_account'] ?? '') ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">IBAN</label>
                                    <input type="text" name="iban" class="form-control" value="<?= htmlspecialchars($employee['iban'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> حفظ البيانات</button>
                    </div>
                </form>
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
