<?php
/**
 * صفحة تصدير واستيراد البيانات
 * Settings Module - Excel Import/Export
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../api/config/config.php';
require_once __DIR__ . '/../../includes/SidebarHelper.php';
require_once __DIR__ . '/../../includes/Security.php';
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

// التحقق من الصلاحيات
if (!Auth::can('settings.view') && !in_array($user['role_slug'], ['super_admin', 'manager', 'admin'])) {
    header('Location: ../../pages/dashboard.php?error=not_authorized');
    exit;
}

// الموديولات للقائمة الجانبية
$enabledModules = getSidebarItems($company['id'], $_SESSION['user_id']);

$pageTitle = 'تصدير واستيراد البيانات';
$success = '';
$error = '';

// الجداول المتاحة للتصدير
$exportTables = [
    'products' => ['name' => 'المنتجات', 'fields' => ['id', 'name', 'sku', 'barcode', 'category', 'cost_price', 'selling_price', 'quantity', 'unit', 'is_active']],
    'customers' => ['name' => 'العملاء', 'fields' => ['id', 'name', 'email', 'phone', 'address', 'balance', 'created_at']],
    'suppliers' => ['name' => 'الموردين', 'fields' => ['id', 'name', 'email', 'phone', 'address', 'balance', 'created_at']],
    'employees' => ['name' => 'الموظفين', 'fields' => ['id', 'first_name', 'last_name', 'email', 'phone', 'hire_date', 'salary', 'status']],
];

// معالجة التصدير
if (isset($_GET['export']) && isset($exportTables[$_GET['export']])) {
    $table = $_GET['export'];
    try {
        $data = $db->fetchAll("SELECT * FROM `$table` WHERE company_id = ?", [$company_id]);
        
        if (!empty($data)) {
            // إنشاء ملف CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $table . '_' . date('Y-m-d') . '.csv"');
            
            // BOM للعربية
            echo "\xEF\xBB\xBF";
            
            $output = fopen('php://output', 'w');
            
            // كتابة العناوين
            fputcsv($output, array_keys($data[0]));
            
            // كتابة البيانات
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
            
            fclose($output);
            exit;
        } else {
            $error = "لا توجد بيانات للتصدير";
        }
    } catch (Exception $e) {
        $error = "خطأ في التصدير: " . $e->getMessage();
    }
}

// معالجة الاستيراد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file']) && verify_csrf()) {
    $table = $_POST['import_table'] ?? '';
    $file = $_FILES['import_file'];
    
    if (isset($exportTables[$table]) && $file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($ext === 'csv') {
            try {
                $handle = fopen($file['tmp_name'], 'r');
                
                // قراءة العناوين
                $headers = fgetcsv($handle);
                $importCount = 0;
                
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) === count($headers)) {
                        $data = array_combine($headers, $row);
                        
                        // إزالة ID إذا كان موجوداً (للإضافة الجديدة)
                        unset($data['id']);
                        $data['company_id'] = $company_id;
                        
                        // بناء الاستعلام
                        $columns = array_keys($data);
                        $placeholders = array_fill(0, count($columns), '?');
                        
                        $sql = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
                        
                        $conn = $db->getConnection();
                        $stmt = $conn->prepare($sql);
                        $stmt->execute(array_values($data));
                        $importCount++;
                    }
                }
                
                fclose($handle);
                $success = "تم استيراد $importCount سجل بنجاح";
            } catch (Exception $e) {
                $error = "خطأ في الاستيراد: " . $e->getMessage();
            }
        } else {
            $error = "يرجى رفع ملف CSV فقط";
        }
    } else {
        $error = "يرجى اختيار الجدول ورفع الملف";
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
        .export-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
        .export-card { background: var(--bg); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border); text-align: center; transition: all 0.3s; }
        .export-card:hover { border-color: var(--primary); transform: translateY(-4px); }
        .export-card i { font-size: 2rem; color: var(--primary); margin-bottom: 12px; }
        .export-card h4 { margin: 0 0 12px; }
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
                    <h1><i class="fas fa-file-excel"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <a href="../../pages/settings.php" class="btn btn-outline">عودة للإعدادات</a>
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
                        <a href="import_export.php" class="submenu-item active"><i class="fas fa-file-excel"></i><span>استيراد وتصدير</span></a>
                    </div>
                </div>

                <?php if ($success): ?><div class="alert alert-success mb-3"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger mb-3"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>

                <!-- تصدير البيانات -->
                <div class="card mb-3">
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-download text-success"></i> تصدير البيانات</h3></div>
                    <div class="card-body">
                        <p class="text-muted mb-3">اختر البيانات التي تريد تصديرها إلى ملف CSV:</p>
                        <div class="export-grid">
                            <?php foreach ($exportTables as $key => $table): ?>
                            <div class="export-card">
                                <i class="fas fa-table"></i>
                                <h4><?= $table['name'] ?></h4>
                                <a href="?export=<?= $key ?>" class="btn btn-sm btn-success"><i class="fas fa-download"></i> تصدير</a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- استيراد البيانات -->
                <div class="card">
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-upload text-info"></i> استيراد البيانات</h3></div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="form-label">اختر الجدول</label>
                                        <select name="import_table" class="form-control" required>
                                            <option value="">اختر...</option>
                                            <?php foreach ($exportTables as $key => $table): ?>
                                            <option value="<?= $key ?>"><?= $table['name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="form-label">ملف CSV</label>
                                        <input type="file" name="import_file" class="form-control" accept=".csv" required>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> استيراد</button>
                        </form>
                        
                        <hr style="margin: 20px 0; border-color: var(--border);">
                        
                        <h4><i class="fas fa-info-circle text-info"></i> ملاحظات هامة</h4>
                        <ul class="text-muted" style="margin-top: 10px;">
                            <li>يجب أن يكون الملف بصيغة CSV</li>
                            <li>الصف الأول يجب أن يحتوي على أسماء الأعمدة</li>
                            <li>لتصدير البيانات أولاً واستخدام الملف كقالب</li>
                            <li>تأكد من أن البيانات لا تحتوي على تكرارات</li>
                        </ul>
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
