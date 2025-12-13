<?php
/**
 * API تسجيل الدخول
 * Login API Endpoint
 */

// تضمين ملفات التكوين
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../includes/Database.php';
require_once __DIR__ . '/../../../includes/JWT.php';
require_once __DIR__ . '/../../../includes/Auth.php';
require_once __DIR__ . '/../../../includes/Middleware.php';

// إعداد CORS
Middleware::cors();

// Rate Limiting
if (!Middleware::rateLimit()) {
    exit;
}

// فقط طلبات POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Middleware::sendError('طريقة الطلب غير مسموحة', 405);
}

// الحصول على البيانات
$data = Middleware::getJsonInput();
if (empty($data)) {
    $data = Middleware::getPost();
}

// التحقق من الحقول المطلوبة
$errors = Middleware::validate($data, [
    'username' => 'required',
    'password' => 'required'
]);

if (!empty($errors)) {
    Middleware::sendJson([
        'success' => false,
        'message' => 'بيانات غير صالحة',
        'errors' => $errors
    ], 422);
}

try {
    // محاولة تسجيل الدخول
    $companyId = !empty($data['company_id']) ? (int)$data['company_id'] : null;
    $result = Auth::login($data['username'], $data['password'], $companyId);

    if ($result['success']) {
        Middleware::sendJson($result);
    } else {
        Middleware::sendJson($result, 401);
    }
} catch (PDOException $e) {
    // A production app should log this error internally.
    // error_log('Database Connection Error: ' . $e->getMessage());
    Middleware::sendError('حدث خطأ في الاتصال بالخادم. يرجى المحاولة مرة أخرى لاحقًا.', 500);
} catch (Exception $e) {
    // A production app should log this error internally.
    // error_log('General Error: ' . $e->getMessage());
    Middleware::sendError('حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى لاحقًا.', 500);
}
