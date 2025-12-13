<?php
/**
 * صفحة تسجيل الخروج
 * Logout Page
 */

session_start();

// حذف جميع متغيرات الجلسة
$_SESSION = [];

// حذف كوكي الجلسة
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// حذف كوكي JWT إذا كان موجوداً
if (isset($_COOKIE['jwt_token'])) {
    setcookie('jwt_token', '', time() - 3600, '/');
}

// تدمير الجلسة
session_destroy();

// إعادة التوجيه لصفحة تسجيل الدخول
header('Location: login.php');
exit;
