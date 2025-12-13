<?php
// Deactivate all error reporting
error_reporting(0);
ini_set('display_errors', 0);

// Set header to plain text for clean output
header('Content-Type: text/plain');

// Check if config file exists
$configFile = __DIR__ . '/api/config/config.php';
if (!file_exists($configFile)) {
    echo "FAILURE: The configuration file could not be found at: " . $configFile;
    exit;
}
require_once $configFile;

echo "Database Connection Test\n";
echo "========================\n";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_USER: " . DB_USER . "\n";
echo "DB_PASS: " . (empty(DB_PASS) ? '(empty)' : '(not empty)') . "\n\n";

try {
    // Check if PDO class exists
    if (!class_exists('PDO')) {
        echo "FAILURE: The PDO class does not exist. This means the pdo_mysql extension is NOT enabled or loaded correctly.\n";
        exit;
    }

    echo "Attempting to connect...\n";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    
    // If we reach here, the connection was successful
    echo "\nSUCCESS: Database connection was successful.\n";
    
} catch (PDOException $e) {
    // If connection fails, catch the exception
    echo "\nFAILURE: Could not connect to the database.\n";
    echo "------------------------------------------\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "Error Message: " . $e->getMessage() . "\n";
    echo "------------------------------------------\n\n";
    echo "Possible Solutions:\n";
    echo "1. Ensure the MySQL server is running in your XAMPP control panel.\n";
    echo "2. Verify that the database name '" . DB_NAME . "' exists in phpMyAdmin.\n";
    echo "3. Double-check that the user '" . DB_USER . "' and password are correct in 'api/config/config.php'.\n";

} catch (Exception $e) {
    // Catch any other unexpected errors
    echo "\nFAILURE: An unexpected error occurred.\n";
    echo "Error Message: " . $e->getMessage() . "\n";
}
?>
