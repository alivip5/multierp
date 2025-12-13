<?php
/**
 * API Journal Entries
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../includes/Database.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['lines']) || count($input['lines']) < 2) {
        echo json_encode(['error' => 'Entry must have at least 2 lines']);
        exit;
    }

    // Validate Balance (Backend check)
    $debit = 0;
    $credit = 0;
    foreach ($input['lines'] as $line) {
        $debit += (float)$line['debit'];
        $credit += (float)$line['credit'];
    }
    
    if (abs($debit - $credit) > 0.01) {
        echo json_encode(['error' => 'Entry imbalance']);
        exit;
    }

    $conn = $db->getConnection();
    $conn->beginTransaction();

    try {
        $company_id = $_SESSION['company_id'] ?? 1;
        
        // entry number
        $lastEntry = $db->fetch("SELECT entry_number FROM journal_entries WHERE company_id = ? ORDER BY id DESC LIMIT 1", [$company_id]);
        $nextNum = 1;
        if ($lastEntry) {
             preg_match('/(\d+)$/', $lastEntry['entry_number'], $matches);
             $nextNum = ((int)($matches[1] ?? 0)) + 1;
        }
        $entry_number = 'JV-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

        // Header
        $stmt = $conn->prepare("INSERT INTO journal_entries (company_id, entry_number, date, description, debit, credit, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, 'posted', ?, NOW())");
        $stmt->execute([
            $company_id,
            $entry_number,
            $input['date'],
            $input['description'],
            $debit, // total amount
            $credit,
            $_SESSION['user_id']
        ]);
        $entry_id = $conn->lastInsertId();

        // Lines
        $stmtLine = $conn->prepare("INSERT INTO journal_entry_lines (entry_id, account_id, account_name, debit, credit, description) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($input['lines'] as $line) {
            // Check if account exists or use dummy ID for now if we are using account_name
            // Ideally we resolve account_id from account_name or input should have account_id
            $account_id = $line['account_id'] ?? 0; // 0 or null

            $stmtLine->execute([
                $entry_id,
                $account_id,
                $line['account_name'] ?? 'Unknown',
                $line['debit'],
                $line['credit'],
                $line['description'] ?? ''
            ]);
            
            // Update Account Balance logic skipped for simplicity/safety
        }

        $conn->commit();
        echo json_encode(['success' => true, 'id' => $entry_id]);

    } catch (Exception $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    // GET logic (fetch)
    $company_id = $_SESSION['company_id'] ?? 1;
    $entries = $db->fetchAll("SELECT * FROM journal_entries WHERE company_id = ? ORDER BY date DESC LIMIT 50", [$company_id]);
    echo json_encode(['success' => true, 'data' => $entries]);
}
