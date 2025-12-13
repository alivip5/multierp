<?php
/**
 * صفحة الإعدادات
 * Settings Page
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../api/config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Security.php';

$db = Database::getInstance();
$user = $db->fetch("SELECT u.*, r.name_ar as role_name, r.name as role_slug FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?", [$_SESSION['user_id']]);
$company = $db->fetch("SELECT * FROM companies WHERE id = ?", [$_SESSION['company_id'] ?? 1]);

// التحقق من الصلاحيات
if (!in_array($user['role_slug'], ['super_admin', 'manager'])) {
    header('Location: dashboard.php');
    exit;
}

// معالجة تحديث الإعدادات
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $message = 'خطأ في التحقق من الأمان';
        $messageType = 'danger';
    } else {
        $action = $_POST['action'] ?? '';
    
        if ($action === 'update_company') {
        $updateData = [
            'name' => $_POST['name'] ?? $company['name'],
            'name_en' => $_POST['name_en'] ?? $company['name_en'],
            'address' => $_POST['address'] ?? $company['address'],
            'phone' => $_POST['phone'] ?? $company['phone'],
            'email' => $_POST['email'] ?? $company['email'],
            'tax_number' => $_POST['tax_number'] ?? $company['tax_number'],
            'commercial_registry' => $_POST['commercial_registry'] ?? $company['commercial_registry'],
            'currency' => $_POST['currency'] ?? $company['currency'],
            'currency_symbol' => $_POST['currency_symbol'] ?? $company['currency_symbol'],
            'tax_rate' => $_POST['tax_rate'] ?? $company['tax_rate'],
        ];
        
        $db->update('companies', $updateData, 'id = ?', [$company['id']]);
        $message = 'تم تحديث بيانات الشركة بنجاح';
        $messageType = 'success';
        $company = array_merge($company, $updateData);
    }
    
    if ($action === 'toggle_module') {
        $moduleId = $_POST['module_id'] ?? 0;
        $status = $_POST['status'] ?? 'disabled';
        
        $existing = $db->fetch("SELECT * FROM company_modules WHERE company_id = ? AND module_id = ?", [$company['id'], $moduleId]);
        
        if ($existing) {
            $db->update('company_modules', ['status' => $status], 'company_id = ? AND module_id = ?', [$company['id'], $moduleId]);
        } else {
            $db->insert('company_modules', ['company_id' => $company['id'], 'module_id' => $moduleId, 'status' => $status]);
        }
        
        $message = 'تم تحديث حالة الموديول';
        $messageType = 'success';
    }
    }
}

// الحصول على الموديولات
$modules = $db->fetchAll(
    "SELECT m.*, COALESCE(cm.status, 'disabled') as status
     FROM modules m
     LEFT JOIN company_modules cm ON m.id = cm.module_id AND cm.company_id = ?
     ORDER BY m.sort_order",
    [$company['id']]
);

// الحصول على الموديولات المفعلة للقائمة الجانبية
$enabledModules = $db->fetchAll(
    "SELECT m.* FROM modules m 
     LEFT JOIN company_modules cm ON m.id = cm.module_id AND cm.company_id = ?
     WHERE cm.status = 'enabled' OR m.is_system = 1
     ORDER BY m.sort_order",
    [$company['id']]
);

$pageTitle = 'الإعدادات';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="<?= $user['theme'] ?? 'dark' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= htmlspecialchars($company['name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .settings-tabs {
            display: flex;
            gap: 8px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0;
            margin-bottom: 24px;
        }
        .tab-btn {
            padding: 12px 24px;
            background: none;
            border: none;
            color: var(--text-muted);
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            transition: all 0.2s;
        }
        .tab-btn:hover { color: var(--text); }
        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .module-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            background: var(--bg);
            border-radius: var(--radius-md);
            margin-bottom: 12px;
        }
        .module-info { display: flex; align-items: center; gap: 16px; }
        .module-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }
        .module-name { font-weight: 600; }
        .module-desc { font-size: 0.85rem; color: var(--text-muted); }
        
        .toggle-switch {
            position: relative;
            width: 52px;
            height: 28px;
        }
        .toggle-switch input { display: none; }
        .toggle-slider {
            position: absolute;
            inset: 0;
            background: var(--border);
            border-radius: 14px;
            cursor: pointer;
            transition: 0.3s;
        }
        .toggle-slider:before {
            content: '';
            position: absolute;
            width: 22px;
            height: 22px;
            background: white;
            border-radius: 50%;
            top: 3px;
            right: 3px;
            transition: 0.3s;
        }
        .toggle-switch input:checked + .toggle-slider {
            background: var(--success);
        }
        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(-24px);
        }
        .toggle-switch input:disabled + .toggle-slider {
            opacity: 0.5;
            cursor: not-allowed;
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
                        <a href="<?= $module['slug'] === 'dashboard' ? 'dashboard.php' : ($module['slug'] === 'settings' ? 'settings.php' : '../modules/' . $module['slug'] . '/index.php') ?>" 
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
                    <i class="fas fa-chevron-right"></i><span>طي القائمة</span>
                </button>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1><i class="fas fa-cog"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <button class="header-btn" onclick="toggleTheme()"><i class="fas fa-moon" id="themeIcon"></i></button>
                    <div class="user-menu">
                        <div class="user-avatar"><?= mb_substr($user['full_name'], 0, 1, 'UTF-8') ?></div>
                        <div class="user-info">
                            <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
                            <div class="user-role"><?= htmlspecialchars($user['role_name']) ?></div>
                        </div>
                    </div>
                </div>
            </header>
            
            <div class="page-content">
                <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> mb-3" style="padding: 16px; border-radius: var(--radius-md); background: <?= $messageType === 'success' ? 'rgba(34, 197, 94, 0.15)' : 'rgba(239, 68, 68, 0.15)' ?>; color: <?= $messageType === 'success' ? 'var(--success)' : 'var(--danger)' ?>;">
                    <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <div class="settings-tabs">
                            <button class="tab-btn active" data-tab="company">
                                <i class="fas fa-building"></i> بيانات الشركة
                            </button>
                            <button class="tab-btn" data-tab="modules">
                                <i class="fas fa-cubes"></i> الموديولات
                            </button>
                            <button class="tab-btn" data-tab="tools">
                                <i class="fas fa-tools"></i> الأدوات
                            </button>
                            <button class="tab-btn" data-tab="print">
                                <i class="fas fa-print"></i> الطباعة
                            </button>
                            <button class="tab-btn" data-tab="security">
                                <i class="fas fa-shield-alt"></i> الأمان
                            </button>
                        </div>
                        
                        <!-- Company Tab -->
                        <div class="tab-content active" id="tab-company">
                            <form method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="update_company">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="form-label">اسم الشركة (عربي)</label>
                                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($company['name']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="form-label">اسم الشركة (إنجليزي)</label>
                                            <input type="text" name="name_en" class="form-control" value="<?= htmlspecialchars($company['name_en'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="form-label">الهاتف</label>
                                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($company['phone'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="form-label">البريد الإلكتروني</label>
                                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($company['email'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">العنوان</label>
                                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($company['address'] ?? '') ?></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="form-label">الرقم الضريبي</label>
                                            <input type="text" name="tax_number" class="form-control" value="<?= htmlspecialchars($company['tax_number'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="form-label">السجل التجاري</label>
                                            <input type="text" name="commercial_registry" class="form-control" value="<?= htmlspecialchars($company['commercial_registry'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">العملة</label>
                                            <input type="text" name="currency" class="form-control" value="<?= htmlspecialchars($company['currency'] ?? 'SAR') ?>">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">رمز العملة</label>
                                            <input type="text" name="currency_symbol" class="form-control" value="<?= htmlspecialchars($company['currency_symbol'] ?? 'ر.س') ?>">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">نسبة الضريبة %</label>
                                            <input type="number" step="0.01" name="tax_rate" class="form-control" value="<?= htmlspecialchars($company['tax_rate'] ?? 15) ?>">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> حفظ التغييرات
                                </button>
                            </form>
                        </div>
                        
                        <!-- Modules Tab -->
                        <div class="tab-content" id="tab-modules">
                            <p class="text-muted mb-3">قم بتفعيل أو تعطيل الموديولات حسب احتياجات شركتك</p>
                            <?php foreach ($modules as $module): ?>
                            <div class="module-card">
                                <div class="module-info">
                                    <div class="module-icon"><i class="<?= $module['icon'] ?>"></i></div>
                                    <div>
                                        <div class="module-name"><?= htmlspecialchars($module['name_ar']) ?></div>
                                        <div class="module-desc"><?= htmlspecialchars($module['description'] ?? '') ?></div>
                                    </div>
                                </div>
                                <form method="POST" style="display: inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle_module">
                                    <input type="hidden" name="module_id" value="<?= $module['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $module['status'] === 'enabled' ? 'disabled' : 'enabled' ?>">
                                    <label class="toggle-switch">
                                        <input type="checkbox" <?= $module['status'] === 'enabled' ? 'checked' : '' ?> 
                                               <?= $module['is_system'] ? 'disabled' : '' ?>
                                               onchange="this.form.submit()">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Tools Tab -->
                        <div class="tab-content" id="tab-tools">
                            <p class="text-muted mb-3">أدوات متقدمة لإدارة النظام والبيانات</p>
                            <div class="row">
                                <div class="col-4">
                                    <div class="module-card" style="flex-direction: column; text-align: center; padding: 24px;">
                                        <div class="module-icon" style="width: 64px; height: 64px; margin-bottom: 16px; font-size: 1.5rem;">
                                            <i class="fas fa-database"></i>
                                        </div>
                                        <h4>النسخ الاحتياطي</h4>
                                        <p class="text-muted" style="font-size: 0.85rem;">إنشاء وإدارة النسخ الاحتياطية</p>
                                        <a href="../modules/settings/backup.php" class="btn btn-primary btn-sm mt-2">فتح</a>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="module-card" style="flex-direction: column; text-align: center; padding: 24px;">
                                        <div class="module-icon" style="width: 64px; height: 64px; margin-bottom: 16px; font-size: 1.5rem; background: linear-gradient(135deg, var(--success), #16a34a);">
                                            <i class="fas fa-file-excel"></i>
                                        </div>
                                        <h4>استيراد/تصدير</h4>
                                        <p class="text-muted" style="font-size: 0.85rem;">تصدير واستيراد البيانات بصيغة CSV</p>
                                        <a href="../modules/settings/import_export.php" class="btn btn-success btn-sm mt-2">فتح</a>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="module-card" style="flex-direction: column; text-align: center; padding: 24px;">
                                        <div class="module-icon" style="width: 64px; height: 64px; margin-bottom: 16px; font-size: 1.5rem; background: linear-gradient(135deg, var(--info), #2563eb);">
                                            <i class="fas fa-user-cog"></i>
                                        </div>
                                        <h4>الملف الشخصي</h4>
                                        <p class="text-muted" style="font-size: 0.85rem;">تعديل بياناتك الشخصية</p>
                                        <a href="../modules/settings/profile.php" class="btn btn-outline btn-sm mt-2">فتح</a>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-4">
                                    <div class="module-card" style="flex-direction: column; text-align: center; padding: 24px;">
                                        <div class="module-icon" style="width: 64px; height: 64px; margin-bottom: 16px; font-size: 1.5rem; background: linear-gradient(135deg, #8b5cf6, #6366f1);">
                                            <i class="fas fa-code-branch"></i>
                                        </div>
                                        <h4>الفروع والمخازن</h4>
                                        <p class="text-muted" style="font-size: 0.85rem;">إدارة فروع ومخازن الشركة</p>
                                        <a href="../modules/settings/branches.php" class="btn btn-primary btn-sm mt-2">فتح</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Print Tab -->
                        <div class="tab-content" id="tab-print">
                            <h4 class="mb-3">إعدادات الطباعة</h4>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">حجم الورق الافتراضي</label>
                                        <select class="form-control">
                                            <option value="A4">A4</option>
                                            <option value="A5">A5</option>
                                            <option value="Letter">Letter</option>
                                            <option value="thermal">حراري (80mm)</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label">اتجاه الطباعة</label>
                                        <select class="form-control">
                                            <option value="portrait">عمودي (Portrait)</option>
                                            <option value="landscape">أفقي (Landscape)</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" id="showLogo" checked>
                                            <label for="showLogo">إظهار شعار الشركة</label>
                                        </div>
                                    </div>
                                    <div class="form-group mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" id="showQR" checked>
                                            <label for="showQR">إظهار رمز QR</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">نص الترويسة</label>
                                        <textarea class="form-control" rows="2" placeholder="نص يظهر أعلى الفاتورة"></textarea>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label">نص التذييل</label>
                                        <textarea class="form-control" rows="2" placeholder="نص يظهر أسفل الفاتورة (شكراً لتعاملكم معنا)"></textarea>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label">عدد النسخ الافتراضي</label>
                                        <input type="number" class="form-control" value="1" min="1" max="5">
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-primary mt-3"><i class="fas fa-save"></i> حفظ إعدادات الطباعة</button>
                        </div>
                        
                        <!-- Security Tab -->
                        <div class="tab-content" id="tab-security">
                            <!-- إدارة المستخدمين والأدوار -->
                            <h4 class="mb-3">إدارة الوصول</h4>
                            <div class="row mb-4">
                                <div class="col-4">
                                    <div class="module-card" style="flex-direction: column; text-align: center; padding: 24px;">
                                        <div class="module-icon" style="width: 64px; height: 64px; margin-bottom: 16px; font-size: 1.5rem; background: linear-gradient(135deg, var(--warning), #d97706);">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <h4>إدارة المستخدمين</h4>
                                        <p class="text-muted" style="font-size: 0.85rem;">إضافة وإدارة مستخدمي النظام</p>
                                        <a href="../modules/settings/users.php" class="btn btn-warning btn-sm mt-2">فتح</a>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="module-card" style="flex-direction: column; text-align: center; padding: 24px;">
                                        <div class="module-icon" style="width: 64px; height: 64px; margin-bottom: 16px; font-size: 1.5rem; background: linear-gradient(135deg, var(--danger), #dc2626);">
                                            <i class="fas fa-user-shield"></i>
                                        </div>
                                        <h4>الأدوار والصلاحيات</h4>
                                        <p class="text-muted" style="font-size: 0.85rem;">إدارة صلاحيات المستخدمين</p>
                                        <a href="../modules/settings/roles.php" class="btn btn-danger btn-sm mt-2">فتح</a>
                                    </div>
                                </div>
                            </div>
                            
                            <hr style="border-color: var(--border-color); margin: 24px 0;">
                            
                            <!-- تغيير كلمة المرور -->
                            <h4 class="mb-3">تغيير كلمة المرور</h4>
                            <form>
                                <div class="form-group">
                                    <label class="form-label">كلمة المرور الحالية</label>
                                    <input type="password" class="form-control" style="max-width: 400px;">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">كلمة المرور الجديدة</label>
                                    <input type="password" class="form-control" style="max-width: 400px;">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">تأكيد كلمة المرور</label>
                                    <input type="password" class="form-control" style="max-width: 400px;">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key"></i> تغيير كلمة المرور
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Tabs
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
            });
        });
        
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
        
        function toggleTheme() {
            const html = document.documentElement;
            const newTheme = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            document.getElementById('themeIcon').className = newTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
            localStorage.setItem('theme', newTheme);
        }
    </script>
</body>
</html>
