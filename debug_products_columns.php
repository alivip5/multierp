<?php
require_once 'api/config/config.php';
require_once 'includes/Database.php';
try {
    $db = Database::getInstance();
    $stmt = $db->query("DESCRIBE production_bom_items");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in production_bom_items table:\n";
    print_r($columns);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
