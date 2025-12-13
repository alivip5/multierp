<?php
/**
 * دليل الحسابات
 * Chart of Accounts
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

// التحقق من تفعيل الموديول
require_module($company['id'], 'accounting');

// معالجة إضافة حساب
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'];
    $name = $_POST['name'];
    $type = $_POST['type'];
    
    // Simple validation
    if ($code && $name && check_csrf()) {
        try {
            $db->getConnection()->exec("CREATE TABLE IF NOT EXISTS accounts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_id INT NOT NULL,
                code VARCHAR(20) NOT NULL,
                name VARCHAR(100) NOT NULL,
                type ENUM('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL,
                balance DECIMAL(15,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(company_id, code)
            )");

            $db->insert('accounts', [
                'company_id' => $company_id,
                'code' => $code,
                'name' => $name,
                'type' => $type
            ]);
            $success = "تم إضافة الحساب بنجاح";
        } catch (Exception $e) {
            $error = "خطأ: " . $e->getMessage();
        }
    } else {
        $error = "بيانات غير مكتملة أو خطأ في التحقق";
    }
}

// Fetch Accounts
try {
    $accounts = $db->fetchAll("SELECT * FROM accounts WHERE company_id = ? ORDER BY code ASC", [$company_id]);
} catch (Exception $e) {
    $accounts = [];
}

$pageTitle = 'دليل الحسابات';
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
                           class="nav-link <?= $module['slug'] === 'accounting' ? 'active' : '' ?>">
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
                    <button class="btn btn-primary" onclick="document.getElementById('addAccountModal').style.display='block'">
                        <i class="fas fa-plus"></i> حساب جديد
                    </button>
                    <a href="index.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item"><i class="fas fa-home"></i><span>الرئيسية</span></a>
                        <a href="accounts.php" class="submenu-item active"><i class="fas fa-sitemap"></i><span>دليل الحسابات</span></a>
                        <a href="entries.php" class="submenu-item"><i class="fas fa-book"></i><span>قيود اليومية</span></a>
                        <a href="reports.php" class="submenu-item"><i class="fas fa-file-invoice-dollar"></i><span>التقارير المالية</span></a>
                    </div>
                </div>

                <?php if ($success): ?><div class="alert alert-success mt-3"><?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger mt-3"><?= $error ?></div><?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> جميع الحسابات</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>كود الحساب</th>
                                        <th>اسم الحساب</th>
                                        <th>النوع</th>
                                        <th>الرصيد</th>
                                        <th>إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($accounts)): ?>
                                    <tr><td colspan="5" class="text-center p-3 text-muted">لا توجد حسابات مضافة</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($accounts as $acc): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($acc['code']) ?></span></td>
                                        <td class="fw-bold"><?= htmlspecialchars($acc['name']) ?></td>
                                        <td>
                                            <?php
                                            $types = ['asset'=>'أصول', 'liability'=>'خصوم', 'equity'=>'حقوق ملكية', 'revenue'=>'إيرادات', 'expense'=>'مصروفات'];
                                            $badges = ['asset'=>'info', 'liability'=>'warning', 'equity'=>'primary', 'revenue'=>'success', 'expense'=>'danger'];
                                            ?>
                                            <span class="badge bg-<?= $badges[$acc['type']] ?? 'secondary' ?>">
                                                <?= $types[$acc['type']] ?? $acc['type'] ?>
                                            </span>
                                        </td>
                                        <td dir="ltr" class="text-end fw-bold"><?= number_format($acc['balance'], 2) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline" title="تعديل"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-sm btn-outline text-info" title="كشف حساب"><i class="fas fa-file-alt"></i></button>
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

    <!-- Add Account Modal -->
    <div id="addAccountModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div class="modal-content" style="background: var(--bg-surface); width: 400px; margin: 100px auto; padding: 20px; border-radius: 8px; border: 1px solid var(--border);">
            <h3 class="mb-3">إضافة حساب جديد</h3>
            <form method="POST">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">كود الحساب</label>
                    <input type="text" name="code" class="form-control" required placeholder="مثال: 1101">
                </div>
                <div class="mb-3">
                    <label class="form-label">اسم الحساب</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">نوع الحساب</label>
                    <select name="type" class="form-control" required>
                        <option value="asset">أصول (Assets)</option>
                        <option value="liability">خصوم (Liabilities)</option>
                        <option value="equity">حقوق ملكية (Equity)</option>
                        <option value="revenue">إيرادات (Revenue)</option>
                        <option value="expense">مصروفات (Expenses)</option>
                    </select>
                </div>
                <div class="text-end">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('addAccountModal').style.display='none'">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('addAccountModal')) {
                document.getElementById('addAccountModal').style.display = "none";
            }
        }
    </script>
</body>
</html>
