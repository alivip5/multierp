<?php
// reproduce_issue.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting reproduction test...\n";

// Start buffering to catch any output from includes
ob_start();

$files = [
    __DIR__ . '/../../config/config.php',
    __DIR__ . '/../../../includes/Database.php',
    __DIR__ . '/../../../includes/JWT.php',
    __DIR__ . '/../../../includes/Auth.php',
    __DIR__ . '/../../../includes/Middleware.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        require_once $file;
    } else {
        echo "File not found: $file\n";
    }
}

$output = ob_get_contents();
ob_end_clean();

if (strlen($output) > 0) {
    echo "FAIL: Unexpected output detected (" . strlen($output) . " bytes):\n";
    echo "--- START OUTPUT ---\n";
    echo $output;
    echo "\n--- END OUTPUT ---\n";
    echo "Hex dump:\n";
    echo bin2hex($output) . "\n";
} else {
    echo "PASS: No unexpected output during inclusion.\n";
}

echo "Testing login logic...\n";
try {
    // Attempt login with mocked input to see if it throws valid JSON or error
    // Mock Middleware input
    $_SERVER['REQUEST_METHOD'] = 'POST';
    // We can't easily mock php://input for Middleware::getJsonInput without deeper hacks,
    // but we can test Auth::login directly as that's where logic is.
    // However, the issue is likely "Unexpected token <" which comes from the entry script.
    
    // Let's print something to confirm we are done
    echo "Done.\n";
} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
