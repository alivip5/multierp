<?php
/**
 * سكربت تشخيصي لمحاكاة طلب تسجيل الدخول
 * يرسل طلب POST داخلي إلى login.php ويعرض الاستجابة الخام
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$url = 'http://localhost/multierp/api/v1/auth/login';
$data = [
    'username' => 'admin',
    'password' => 'admin123'
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
        'ignore_errors' => true // لجلب محتوى الخطأ أيضاً
    ]
];

$context  = stream_context_create($options);
echo "Testing connection to: $url\n";
echo "Sending Data: " . json_encode($data) . "\n\n";

$result = file_get_contents($url, false, $context);

echo "--- Response Start ---\n";
var_dump($result);
echo "--- Response End ---\n";

echo "\nResponse Headers:\n";
print_r($http_response_header);
