<?php
/**
 * API Router
 * handles requests rewritten by .htaccess
 */

// إعداد بيئة العمل
require_once __DIR__ . '/../config/config.php';

// الحصول على المسار المطلوب
$route = $_GET['route'] ?? '';
$route = trim($route, '/');

// توجيه المسار
switch ($route) {
    case 'auth/login':
        require __DIR__ . '/auth/login.php';
        break;
        
    case 'auth/test':
        require __DIR__ . '/auth/test_api.php';
        break;
        
    default:
        // محاولة البحث عن الملف مباشرة
        $possibleFile = __DIR__ . '/' . $route . '.php';
        if (file_exists($possibleFile)) {
            require $possibleFile;
        } else {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'Endpoint not found',
                'route' => $route
            ]);
        }
        break;
}
