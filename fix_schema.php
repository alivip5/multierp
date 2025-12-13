<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/api/config/config.php';
$db = Database::getInstance();
$conn = $db->getConnection();

echo "Attempting to add avg_cost column...\n";

try {
    // Check if column exists first
    $check = $db->fetchAll("SHOW COLUMNS FROM product_stock LIKE 'avg_cost'");
    if (empty($check)) {
        $conn->exec("ALTER TABLE product_stock ADD COLUMN avg_cost DECIMAL(15,2) DEFAULT 0.00 AFTER quantity");
        echo "SUCCESS: Column 'avg_cost' added.\n";
    } else {
        echo "INFO: Column 'avg_cost' already exists.\n";
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Verify again
$check = $db->fetchAll("SHOW COLUMNS FROM product_stock LIKE 'avg_cost'");
if (!empty($check)) {
    echo "VERIFICATION: Column 'avg_cost' is PRESENT.\n";
} else {
    echo "VERIFICATION FAILED: Column 'avg_cost' is STILL MISSING.\n";
}
?>
