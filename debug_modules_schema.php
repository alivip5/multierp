<?php
require_once 'api/config/config.php';
require_once 'includes/Database.php';
try {
    $db = Database::getInstance();
    $stmt = $db->query("DESCRIBE modules");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in modules table:\n";
    print_r($columns);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
