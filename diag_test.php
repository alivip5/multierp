<?php
// Test script to diagnose premature output issues

// Turn on maximum error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

function check_output($step) {
    if (headers_sent($file, $line) || ob_get_length() > 0) {
        // Clear the buffer to only show our error message
        ob_end_clean(); 
        echo "FATAL_ERROR: Output started after step '{$step}'.\n";
        echo "File: {$file}\nLine: {$line}\n";
        exit;
    }
}

// Start output buffering to capture any stray output
ob_start();

check_output('Initial state');

// --- Step 1: Include config.php ---
require_once __DIR__ . '/api/config/config.php';
check_output('After including config.php');

// --- Step 2: Include Database.php ---
require_once __DIR__ . '/includes/Database.php';
check_output('After including Database.php');

// --- Step 3: Include JWT.php ---
require_once __DIR__ . '/includes/JWT.php';
check_output('After including JWT.php');

// --- Step 4: Include Auth.php ---
require_once __DIR__ . '/includes/Auth.php';
check_output('After including Auth.php');

// --- Step 5: Include Middleware.php ---
require_once __DIR__ . '/includes/Middleware.php';
check_output('After including Middleware.php');

// --- Step 6: Attempt Database Connection ---
try {
    Database::getInstance();
} catch (Exception $e) {
    // We don't call check_output here because the exception itself is the error.
    ob_end_clean();
    echo "FATAL_ERROR: Caught exception during Database::getInstance().\n";
    echo "This is likely the cause of the problem.\n";
    echo "Error Message: " . $e->getMessage() . "\n";
    exit;
}
check_output('After Database::getInstance()');


// --- Final Check ---
ob_end_clean();

echo "DIAGNOSTIC_SUCCESS: No premature output was detected during the inclusion of core files or initial database connection.\n";
echo "If the problem persists, it lies within the specific logic of the API endpoint (e.g., function calls in login.php after the includes).\n";

?>
