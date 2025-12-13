<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/api/config/config.php';
$db = Database::getInstance();

echo "Checking product_stock columns:\n";
$cols = $db->fetchAll("SHOW COLUMNS FROM product_stock");
foreach ($cols as $col) {
    echo $col['Field'] . "\n";
}

echo "\nChecking inventory_movements columns:\n";
try {
    $cols2 = $db->fetchAll("SHOW COLUMNS FROM inventory_movements");
    foreach ($cols2 as $col) {
        echo $col['Field'] . "\n";
    }
} catch (Exception $e) {
    echo "inventory_movements table error: " . $e->getMessage();
}
?>
