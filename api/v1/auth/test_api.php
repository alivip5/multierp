<?php
/**
 * ملف تشخيص API
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    // تضمين ملفات التكوين
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../../includes/Database.php';
    require_once __DIR__ . '/../../../includes/JWT.php';
    require_once __DIR__ . '/../../../includes/Auth.php';
    require_once __DIR__ . '/../../../includes/Middleware.php';
    
    // اختبار تسجيل الدخول
    $result = Auth::login('admin', 'admin123');
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}
