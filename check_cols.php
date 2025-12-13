<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/api/config/config.php';
$db = Database::getInstance();
$cols = $db->fetchAll("SHOW COLUMNS FROM product_stock");
print_r($cols);
?>
