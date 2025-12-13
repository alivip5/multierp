<?php
require_once __DIR__ . '/api/config/config.php';
require_once __DIR__ . '/includes/Database.php';

$db = Database::getInstance();
$columns = $db->fetchAll("DESCRIBE products");
echo "<pre>";
print_r($columns);
echo "</pre>";
