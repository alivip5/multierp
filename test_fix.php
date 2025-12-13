<?php
// Script to test session bypass vulnerability

$url = 'http://localhost/multierp/pages/session_login.php';
$data = [
    'user_id' => 1,
    'company_id' => 1,
    'token' => 'fake_token_123'
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
        'ignore_errors' => true // Don't throw error on 401
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
$headers = $http_response_header;

// Check if response code is 401
$isProtected = false;
foreach ($headers as $header) {
    if (strpos($header, '401') !== false) {
        $isProtected = true;
        break;
    }
}

echo "Response Headers:\n";
print_r($headers);
echo "\nResponse Body:\n";
echo $result . "\n\n";

if ($isProtected) {
    echo "SUCCESS: The vulnerability is patched! Request with fake token was rejected (401).\n";
} else {
    echo "FAILURE: The request was accepted! The vulnerability still exists.\n";
}
