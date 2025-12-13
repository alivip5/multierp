<?php
/**
 * API تسجيل الخروج
 * Logout API Endpoint
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../includes/Database.php';
require_once __DIR__ . '/../../../includes/JWT.php';
require_once __DIR__ . '/../../../includes/Auth.php';
require_once __DIR__ . '/../../../includes/Middleware.php';

Middleware::cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Middleware::sendError('طريقة الطلب غير مسموحة', 405);
}

$result = Auth::logout();
Middleware::sendJson($result);
