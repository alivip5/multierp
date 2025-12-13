<?php
/**
 * الصفحة الرئيسية - نقطة الدخول
 * Main Entry Point
 */

session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: pages/login.php');
    exit;
}

// التوجيه للوحة التحكم
header('Location: pages/dashboard.php');
exit;
