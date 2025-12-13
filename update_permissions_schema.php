<?php
require_once 'api/config/config.php';
require_once 'includes/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "Starting schema update...\n";
    
    // 1. Add column if not exists
    $stmt = $conn->query("SHOW COLUMNS FROM modules LIKE 'required_permission'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE `modules` ADD COLUMN `required_permission` VARCHAR(50) NULL COMMENT 'الصلاحية المطلوبة للوصول' AFTER `slug`");
        echo "Column 'required_permission' added to 'modules' table.\n";
    } else {
        echo "Column 'required_permission' already exists.\n";
    }
    
    // 2. Data Migration
    $permissions = [
        'sales' => 'sales.view',
        'purchases' => 'purchases.view',
        'inventory' => 'inventory.view',
        'accounting' => 'accounting.view',
        'hr' => 'hr.view',
        'production' => 'production.view',
        'reports' => 'reports.view',
        'settings' => 'settings.view',
        'pos' => 'pos.access'
    ];
    
    echo "Migrating data...\n";
    $stmt = $conn->prepare("UPDATE modules SET required_permission = ? WHERE slug = ?");
    
    foreach ($permissions as $slug => $perm) {
        $stmt->execute([$perm, $slug]);
        if ($stmt->rowCount() > 0) {
            echo "Updated module '$slug' with permission '$perm'.\n";
        }
    }
    
    echo "Done!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
