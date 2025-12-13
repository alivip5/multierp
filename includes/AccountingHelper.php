<?php
/**
 * Accounting Helper Functions
 * وظائف المحاسبة المساعدة
 */

require_once __DIR__ . '/Database.php';

class AccountingHelper {
    
    private static $db = null;
    
    private static function getDb() {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        return self::$db;
    }
    
    /**
     * إنشاء قيد يومي
     * Create journal entry
     */
    public static function createJournalEntry(
        int $companyId,
        string $reference,
        string $description,
        array $lines,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $createdBy = null
    ): ?int {
        $db = self::getDb();
        
        // التحقق من توازن القيد
        $totalDebit = 0;
        $totalCredit = 0;
        
        foreach ($lines as $line) {
            $totalDebit += (float)($line['debit'] ?? 0);
            $totalCredit += (float)($line['credit'] ?? 0);
        }
        
        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new Exception('القيد غير متوازن: مدين=' . $totalDebit . ' دائن=' . $totalCredit);
        }
        
        try {
            // إنشاء القيد
            $entryId = $db->insert('journal_entries', [
                'company_id' => $companyId,
                'entry_date' => date('Y-m-d'),
                'reference' => $reference,
                'description' => $description,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'status' => 'posted',
                'created_by' => $createdBy
            ]);
            
            // إضافة الأسطر
            foreach ($lines as $line) {
                $db->insert('journal_entry_lines', [
                    'entry_id' => $entryId,
                    'account_id' => (int)$line['account_id'],
                    'debit' => (float)($line['debit'] ?? 0),
                    'credit' => (float)($line['credit'] ?? 0),
                    'description' => $line['description'] ?? null
                ]);
            }
            
            return $entryId;
            
        } catch (Exception $e) {
            throw new Exception('خطأ في إنشاء القيد: ' . $e->getMessage());
        }
    }
    
    /**
     * إنشاء قيد فاتورة مبيعات
     * Create sales invoice journal entry
     */
    public static function createSalesInvoiceEntry(
        int $companyId,
        int $invoiceId,
        float $subtotal,
        float $taxAmount,
        float $total,
        ?int $customerId = null,
        ?int $createdBy = null
    ): ?int {
        $db = self::getDb();
        
        // جلب الحسابات الافتراضية
        // حساب المدينين (الأصول) - مدين
        // حساب الإيرادات - دائن
        // حساب ضريبة المبيعات - دائن
        
        $receivablesAccount = $db->fetch(
            "SELECT id FROM accounts WHERE company_id = ? AND code LIKE '12%' LIMIT 1", 
            [$companyId]
        );
        $salesAccount = $db->fetch(
            "SELECT id FROM accounts WHERE company_id = ? AND code LIKE '41%' LIMIT 1", 
            [$companyId]
        );
        $taxAccount = $db->fetch(
            "SELECT id FROM accounts WHERE company_id = ? AND code LIKE '21%' AND name LIKE '%ضريب%' LIMIT 1", 
            [$companyId]
        );
        
        if (!$receivablesAccount || !$salesAccount) {
            // الحسابات غير موجودة - لا ننشئ القيد
            return null;
        }
        
        $lines = [
            [
                'account_id' => $receivablesAccount['id'],
                'debit' => $total,
                'credit' => 0,
                'description' => 'ذمم مدينة من المبيعات'
            ],
            [
                'account_id' => $salesAccount['id'],
                'debit' => 0,
                'credit' => $subtotal,
                'description' => 'إيرادات المبيعات'
            ]
        ];
        
        if ($taxAmount > 0 && $taxAccount) {
            $lines[] = [
                'account_id' => $taxAccount['id'],
                'debit' => 0,
                'credit' => $taxAmount,
                'description' => 'ضريبة القيمة المضافة'
            ];
        }
        
        return self::createJournalEntry(
            $companyId,
            'INV-' . $invoiceId,
            'قيد فاتورة مبيعات',
            $lines,
            'sales_invoice',
            $invoiceId,
            $createdBy
        );
    }
    
    /**
     * إنشاء قيد فاتورة مشتريات
     * Create purchase invoice journal entry
     */
    public static function createPurchaseInvoiceEntry(
        int $companyId,
        int $invoiceId,
        float $subtotal,
        float $taxAmount,
        float $total,
        ?int $supplierId = null,
        ?int $createdBy = null
    ): ?int {
        $db = self::getDb();
        
        // حساب المشتريات/المخزون - مدين
        // حساب الدائنين - دائن
        
        $inventoryAccount = $db->fetch(
            "SELECT id FROM accounts WHERE company_id = ? AND code LIKE '14%' LIMIT 1", 
            [$companyId]
        );
        $payablesAccount = $db->fetch(
            "SELECT id FROM accounts WHERE company_id = ? AND code LIKE '21%' AND name LIKE '%دائن%' LIMIT 1", 
            [$companyId]
        );
        
        if (!$inventoryAccount || !$payablesAccount) {
            return null;
        }
        
        $lines = [
            [
                'account_id' => $inventoryAccount['id'],
                'debit' => $total,
                'credit' => 0,
                'description' => 'مشتريات مخزون'
            ],
            [
                'account_id' => $payablesAccount['id'],
                'debit' => 0,
                'credit' => $total,
                'description' => 'ذمم دائنة للموردين'
            ]
        ];
        
        return self::createJournalEntry(
            $companyId,
            'PINV-' . $invoiceId,
            'قيد فاتورة مشتريات',
            $lines,
            'purchase_invoice',
            $invoiceId,
            $createdBy
        );
    }
    
    /**
     * إنشاء قيد دفعة
     * Create payment journal entry
     */
    public static function createPaymentEntry(
        int $companyId,
        string $type, // receipt (قبض) أو payment (صرف)
        float $amount,
        int $accountId, // حساب العميل/المورد
        string $paymentMethod,
        ?int $referenceId = null,
        ?int $createdBy = null
    ): ?int {
        $db = self::getDb();
        
        // جلب حساب النقدية/البنك
        $cashAccount = $db->fetch(
            "SELECT id FROM accounts WHERE company_id = ? AND code LIKE '11%' LIMIT 1", 
            [$companyId]
        );
        
        if (!$cashAccount) {
            return null;
        }
        
        if ($type === 'receipt') {
            // استلام مبلغ: مدين النقدية، دائن العميل
            $lines = [
                ['account_id' => $cashAccount['id'], 'debit' => $amount, 'credit' => 0, 'description' => 'استلام نقدي'],
                ['account_id' => $accountId, 'debit' => 0, 'credit' => $amount, 'description' => 'سداد ذمم مدينة']
            ];
        } else {
            // صرف مبلغ: دائن النقدية، مدين المورد
            $lines = [
                ['account_id' => $accountId, 'debit' => $amount, 'credit' => 0, 'description' => 'سداد ذمم دائنة'],
                ['account_id' => $cashAccount['id'], 'debit' => 0, 'credit' => $amount, 'description' => 'صرف نقدي']
            ];
        }
        
        return self::createJournalEntry(
            $companyId,
            'PAY-' . date('YmdHis'),
            $type === 'receipt' ? 'قيد قبض' : 'قيد صرف',
            $lines,
            'payment',
            $referenceId,
            $createdBy
        );
    }
}
