<?php
/**
 * معالج جلسة تسجيل الدخول
 * Session Login Handler
 */

session_start();

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../api/config/config.php';
    require_once __DIR__ . '/../includes/Database.php';
    require_once __DIR__ . '/../includes/JWT.php';
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // التحقق من صحة التوكن
    $token = $data['token'] ?? '';
    $payload = JWT::verify($token);
    
    if (!$payload || !isset($payload['user_id']) || $payload['user_id'] != $data['user_id']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'غير مصرح: توكن غير صالح']);
        exit;
    }
    
    if (!empty($data['user_id']) && !empty($data['company_id'])) {
        $_SESSION['user_id'] = (int)$data['user_id'];
        $_SESSION['company_id'] = (int)$data['company_id'];
        $_SESSION['token'] = $token;
        $_SESSION['login_time'] = time();
        
        // محاولة جلب بيانات الدور
        try {
            $db = Database::getInstance();
            $user = $db->fetch(
                "SELECT u.role_id, r.name as role_name, r.name_ar as role_name_ar 
                 FROM users u 
                 JOIN roles r ON u.role_id = r.id 
                 WHERE u.id = ?",
                [(int)$data['user_id']]
            );
            
            if ($user) {
                $_SESSION['role_id'] = (int)$user['role_id'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['role_name_ar'] = $user['role_name_ar'];
            }
        } catch (Exception $e) {
            // تجاهل خطأ جلب الدور - الجلسة تعمل بدونه
            error_log('Role fetch error: ' . $e->getMessage());
        }
        
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ في الخادم: ' . $e->getMessage()]);
}

