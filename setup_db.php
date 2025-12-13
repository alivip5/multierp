<?php
require_once __DIR__ . '/api/config/config.php';
require_once __DIR__ . '/includes/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$queries = [
    // Accounting Tables
    "CREATE TABLE IF NOT EXISTS journal_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        entry_number VARCHAR(50) NOT NULL,
        date DATE NOT NULL,
        description TEXT,
        debit DECIMAL(15,2) DEFAULT 0,
        credit DECIMAL(15,2) DEFAULT 0,
        status ENUM('draft', 'posted', 'void') DEFAULT 'posted',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS journal_entry_lines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        entry_id INT NOT NULL,
        account_id INT,
        account_name VARCHAR(100),
        description TEXT,
        debit DECIMAL(15,2) DEFAULT 0,
        credit DECIMAL(15,2) DEFAULT 0,
        FOREIGN KEY (entry_id) REFERENCES journal_entries(id) ON DELETE CASCADE
    )",
    // Purchase Tables
    "CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        email VARCHAR(100),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS purchase_invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        supplier_id INT,
        invoice_number VARCHAR(50) NOT NULL,
        invoice_date DATE,
        total DECIMAL(15,2) DEFAULT 0,
        status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS purchase_invoice_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT DEFAULT 1,
        unit_price DECIMAL(15,2) DEFAULT 0,
        total DECIMAL(15,2) DEFAULT 0,
        FOREIGN KEY (invoice_id) REFERENCES purchase_invoices(id) ON DELETE CASCADE
    )",
     // Inventory Stock Table (if missing)
     "CREATE TABLE IF NOT EXISTS product_stock (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        type ENUM('in', 'out') NOT NULL,
        reference_id INT, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($queries as $sql) {
    try {
        $conn->exec($sql);
        echo "Executed: " . substr($sql, 0, 50) . "... <br>";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "<br>";
    }
}

echo "Tables setup completed.";
