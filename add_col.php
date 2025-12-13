<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/api/config/config.php';
$db = Database::getInstance();

try {
    $db->getConnection()->exec("ALTER TABLE product_stock ADD COLUMN avg_cost DECIMAL(15,2) DEFAULT 0.00 AFTER quantity");
    echo "Column avg_cost added successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
