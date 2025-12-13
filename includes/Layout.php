<?php
/**
 * قالب الـ Header والـ Sidebar الموحد
 * Unified Layout Template
 * 
 * يستخدم هذا الملف لتوحيد المظهر عبر جميع الصفحات
 * مع الحفاظ على نظام الصلاحيات وتفعيل/إلغاء الموديولات
 */

if (!defined('MULTIERP_LOADED')) {
    define('MULTIERP_LOADED', true);
}

// تحميل الملفات الأساسية إذا لم تكن محملة
if (!class_exists('Database')) {
    require_once __DIR__ . '/../api/config/config.php';
    require_once __DIR__ . '/Database.php';
}

require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/SidebarHelper.php';

/**
 * تهيئة بيانات الصفحة
 */
function initPageData() {
    $db = Database::getInstance();
    $user = $db->fetch("SELECT u.*, r.name as role_slug, r.name_ar as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?", [$_SESSION['user_id']]);
    $company = $db->fetch("SELECT * FROM companies WHERE id = ?", [$_SESSION['company_id'] ?? 1]);
    
    // تحديث بيانات الجلسة
    if (!isset($_SESSION['role_id']) && $user) {
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['role_name'] = $user['role_slug'];
    }
    
    return ['user' => $user, 'company' => $company, 'db' => $db];
}

/**
 * الحصول على الموديولات للقائمة الجانبية (مع مراعاة التفعيل والصلاحيات)
 */
function getNavigationModules($company_id, $user_id) {
    return getSidebarItems($company_id, $user_id);
}

/**
 * الحصول على عناصر القائمة الفرعية لكل موديول
 */
function getModuleSubMenu($module_slug) {
    $subMenus = [
        'sales' => [
            ['url' => 'index.php', 'icon' => 'fas fa-list', 'title' => 'الفواتير'],
            ['url' => 'add.php', 'icon' => 'fas fa-plus', 'title' => 'فاتورة جديدة'],
            ['url' => 'customers.php', 'icon' => 'fas fa-users', 'title' => 'العملاء'],
        ],
        'purchases' => [
            ['url' => 'index.php', 'icon' => 'fas fa-list', 'title' => 'الفواتير'],
            ['url' => 'add.php', 'icon' => 'fas fa-plus', 'title' => 'فاتورة جديدة'],
            ['url' => 'suppliers.php', 'icon' => 'fas fa-truck', 'title' => 'الموردين'],
        ],
        'inventory' => [
            ['url' => 'products.php', 'icon' => 'fas fa-box', 'title' => 'المنتجات'],
            ['url' => 'opening_stock.php', 'icon' => 'fas fa-clipboard-list', 'title' => 'أرصدة أول المدة'],
            ['url' => 'stock_transfers.php', 'icon' => 'fas fa-exchange-alt', 'title' => 'التحويلات'],
            ['url' => 'low-stock.php', 'icon' => 'fas fa-exclamation-triangle', 'title' => 'منخفض المخزون'],
        ],
        'reports' => [
            ['url' => 'index.php', 'icon' => 'fas fa-chart-bar', 'title' => 'كل التقارير'],
            ['url' => 'sales_report.php', 'icon' => 'fas fa-shopping-cart', 'title' => 'المبيعات'],
            ['url' => 'inventory_report.php', 'icon' => 'fas fa-boxes', 'title' => 'المخزون'],
            ['url' => 'customer_debt_report.php', 'icon' => 'fas fa-user-clock', 'title' => 'مديونية العملاء'],
            ['url' => 'sales_agent_report.php', 'icon' => 'fas fa-user-tie', 'title' => 'مبيعات المندوبين'],
        ],
        'production' => [
            ['url' => 'index.php', 'icon' => 'fas fa-industry', 'title' => 'الرئيسية'],
            ['url' => 'orders.php', 'icon' => 'fas fa-clipboard-list', 'title' => 'أوامر الإنتاج'],
            ['url' => 'add_order.php', 'icon' => 'fas fa-plus', 'title' => 'أمر جديد'],
            ['url' => 'bom.php', 'icon' => 'fas fa-sitemap', 'title' => 'قوائم المواد'],
        ],
        'accounting' => [
            ['url' => 'accounts.php', 'icon' => 'fas fa-sitemap', 'title' => 'شجرة الحسابات'],
            ['url' => 'entries.php', 'icon' => 'fas fa-book', 'title' => 'القيود اليومية'],
            ['url' => 'add_entry.php', 'icon' => 'fas fa-plus', 'title' => 'قيد جديد'],
        ],
        'hr' => [
            ['url' => 'index.php', 'icon' => 'fas fa-users', 'title' => 'الرئيسية'],
            ['url' => 'employees.php', 'icon' => 'fas fa-user-tie', 'title' => 'الموظفين'],
            ['url' => 'add_employee.php', 'icon' => 'fas fa-user-plus', 'title' => 'موظف جديد'],
        ],
        'settings' => [
            ['url' => 'branches.php', 'icon' => 'fas fa-building', 'title' => 'الفروع والمخازن'],
            ['url' => 'users.php', 'icon' => 'fas fa-users-cog', 'title' => 'المستخدمين'],
            ['url' => 'roles.php', 'icon' => 'fas fa-user-shield', 'title' => 'الأدوار'],
            ['url' => 'sales_agents.php', 'icon' => 'fas fa-user-tie', 'title' => 'مندوبي التعاقد'],
            ['url' => 'backup.php', 'icon' => 'fas fa-database', 'title' => 'النسخ الاحتياطي'],
        ],
    ];
    
    return $subMenus[$module_slug] ?? [];
}

/**
 * تحديد الصفحة الحالية
 */
function getCurrentPage() {
    return basename($_SERVER['PHP_SELF']);
}

/**
 * تحديد الموديول الحالي من المسار
 */
function getCurrentModule() {
    $path = $_SERVER['PHP_SELF'];
    if (preg_match('/modules\/([^\/]+)\//', $path, $matches)) {
        return $matches[1];
    }
    if (strpos($path, 'dashboard') !== false) return 'dashboard';
    if (strpos($path, 'settings.php') !== false) return 'settings';
    return '';
}

/**
 * توليد HTML للقائمة الجانبية
 */
function renderSidebar($company, $modules, $currentModule) {
    $html = '<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>';
    $html .= '<aside class="sidebar" id="sidebar">';
    $html .= '<div class="sidebar-header">';
    $html .= '<div class="sidebar-logo"><i class="fas fa-building"></i></div>';
    $html .= '<span class="sidebar-brand">' . htmlspecialchars($company['name']) . '</span>';
    $html .= '</div>';
    
    $html .= '<nav class="sidebar-nav">';
    $html .= '<div class="nav-section">';
    $html .= '<div class="nav-section-title">القائمة الرئيسية</div>';
    
    foreach ($modules as $module) {
        $isActive = $module['slug'] === $currentModule ? 'active' : '';
        $url = $module['slug'] === 'dashboard' 
            ? (strpos($_SERVER['PHP_SELF'], '/modules/') !== false ? '../../pages/dashboard.php' : 'dashboard.php')
            : (strpos($_SERVER['PHP_SELF'], '/modules/') !== false ? '../' . $module['slug'] . '/index.php' : 'modules/' . $module['slug'] . '/index.php');
        
        if ($module['slug'] === 'settings') {
            $url = strpos($_SERVER['PHP_SELF'], '/modules/') !== false ? '../settings/branches.php' : 'modules/settings/branches.php';
        }
        
        $html .= '<div class="nav-item">';
        $html .= '<a href="' . $url . '" class="nav-link ' . $isActive . '">';
        $html .= '<i class="' . $module['icon'] . '"></i>';
        $html .= '<span>' . htmlspecialchars($module['name_ar']) . '</span>';
        $html .= '</a>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</nav>';
    
    $html .= '<div class="sidebar-footer">';
    $html .= '<button class="sidebar-toggle" onclick="toggleSidebar()">';
    $html .= '<i class="fas fa-chevron-right"></i>';
    $html .= '<span>طي القائمة</span>';
    $html .= '</button>';
    $html .= '</div>';
    
    $html .= '</aside>';
    
    return $html;
}

/**
 * توليد HTML للقائمة الفرعية الأفقية
 */
function renderSubMenu($module_slug, $currentPage) {
    $subMenu = getModuleSubMenu($module_slug);
    if (empty($subMenu)) return '';
    
    $html = '<div class="module-submenu">';
    $html .= '<div class="submenu-container">';
    
    foreach ($subMenu as $item) {
        $isActive = basename($item['url']) === $currentPage ? 'active' : '';
        $html .= '<a href="' . $item['url'] . '" class="submenu-item ' . $isActive . '">';
        $html .= '<i class="' . $item['icon'] . '"></i>';
        $html .= '<span>' . $item['title'] . '</span>';
        $html .= '</a>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * توليد HTML للـ Header
 */
function renderHeader($pageTitle, $pageSubtitle = '', $user = null, $headerActions = '') {
    $html = '<header class="header">';
    $html .= '<div class="header-title">';
    $html .= '<h1>' . $pageTitle . '</h1>';
    if ($pageSubtitle) {
        $html .= '<p>' . $pageSubtitle . '</p>';
    }
    $html .= '</div>';
    
    $html .= '<div class="header-actions">';
    $html .= $headerActions;
    
    // زر القائمة للموبايل
    $html .= '<button class="menu-toggle-btn" onclick="toggleSidebar()" title="القائمة">';
    $html .= '<i class="fas fa-bars"></i>';
    $html .= '</button>';
    
    if ($user) {
        $html .= '<button class="header-btn" onclick="toggleTheme()" title="تبديل الثيم">';
        $html .= '<i class="fas fa-moon" id="themeIcon"></i>';
        $html .= '</button>';
        
        $html .= '<div class="user-menu" onclick="toggleUserMenu()">';
        $html .= '<div class="user-avatar">' . mb_substr($user['full_name'], 0, 1, 'UTF-8') . '</div>';
        $html .= '<div class="user-info">';
        $html .= '<div class="user-name">' . htmlspecialchars($user['full_name']) . '</div>';
        $html .= '<div class="user-role">' . htmlspecialchars($user['role_name']) . '</div>';
        $html .= '</div>';
        $html .= '<i class="fas fa-chevron-down"></i>';
        
        $html .= '<div class="user-dropdown" id="userDropdown">';
        $basePath = strpos($_SERVER['PHP_SELF'], '/modules/') !== false ? '../../' : '';
        $html .= '<a href="' . $basePath . 'modules/settings/profile.php" class="dropdown-item"><i class="fas fa-user"></i> الملف الشخصي</a>';
        $html .= '<a href="' . $basePath . 'modules/settings/branches.php" class="dropdown-item"><i class="fas fa-cog"></i> الإعدادات</a>';
        $html .= '<hr style="margin: 8px 0; border-color: var(--border);">';
        $html .= '<a href="' . $basePath . 'pages/logout.php" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</header>';
    
    return $html;
}

/**
 * CSS للقائمة الفرعية
 */
function getSubMenuCSS() {
    return '
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
    .submenu-item i {
        font-size: 0.85rem;
    }
    </style>';
}

/**
 * JavaScript مشترك
 */
function getSharedJS() {
    return '
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            const overlay = document.getElementById("sidebarOverlay");
            
            // Check if we are in mobile mode (window width < 992px)
            if (window.innerWidth < 992) {
                sidebar.classList.toggle("show");
                if (overlay) overlay.classList.toggle("show");
            } else {
                sidebar.classList.toggle("collapsed");
                localStorage.setItem("sidebarCollapsed", sidebar.classList.contains("collapsed"));
            }
        }
        
        function toggleTheme() {
            const html = document.documentElement;
            const icon = document.getElementById("themeIcon");
            const newTheme = html.getAttribute("data-theme") === "dark" ? "light" : "dark";
            
            html.setAttribute("data-theme", newTheme);
            if (icon) icon.className = newTheme === "dark" ? "fas fa-moon" : "fas fa-sun";
            localStorage.setItem("theme", newTheme);
            
            fetch("../../api/v1/settings/theme.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ theme: newTheme })
            }).catch(() => {});
        }
        
        function toggleUserMenu() {
            event.stopPropagation();
            const dropdown = document.getElementById("userDropdown");
            if (dropdown) dropdown.classList.toggle("show");
        }
        
        document.addEventListener("click", function(e) {
            const dropdown = document.getElementById("userDropdown");
            const userMenu = document.querySelector(".user-menu");
            if (dropdown && userMenu && !userMenu.contains(e.target)) {
                dropdown.classList.remove("show");
            }
        });
        
        document.addEventListener("DOMContentLoaded", () => {
            if (localStorage.getItem("sidebarCollapsed") === "true") {
                document.getElementById("sidebar")?.classList.add("collapsed");
            }
            const theme = document.documentElement.getAttribute("data-theme");
            const icon = document.getElementById("themeIcon");
            if (icon) icon.className = theme === "dark" ? "fas fa-moon" : "fas fa-sun";
        });
    </script>';
}
