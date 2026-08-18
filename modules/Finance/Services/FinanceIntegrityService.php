<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use App\Core\Database;
use RuntimeException;

final class FinanceIntegrityService
{
    public function __construct(private readonly Database $db) {}

    public function scan(int $schoolId): array
    {
        if ($schoolId < 1) throw new RuntimeException('Invalid school context.');
        return [
            'negative_invoices' => $this->db->select("SELECT id, invoice_no, student_id, balance FROM fee_invoices WHERE school_id=:school_id AND deleted_at IS NULL AND (balance < 0 OR paid_amount < 0 OR total < 0)", ['school_id'=>$schoolId]),
            'balance_mismatches' => $this->db->select("SELECT id, invoice_no, total, paid_amount, balance FROM fee_invoices WHERE school_id=:school_id AND deleted_at IS NULL AND ABS(balance - GREATEST(0,total-paid_amount)) > 0.01", ['school_id'=>$schoolId]),
            'allocation_overages' => $this->db->select("SELECT i.id invoice_id, i.invoice_no, i.total, COALESCE(SUM(a.amount),0) allocated FROM fee_invoices i JOIN fee_payment_allocations a ON a.invoice_id=i.id WHERE i.school_id=:school_id AND i.deleted_at IS NULL GROUP BY i.id,i.invoice_no,i.total HAVING allocated > i.total + 0.01", ['school_id'=>$schoolId]),
            'orphan_allocations' => $this->db->select("SELECT a.id, a.payment_id, a.invoice_id, a.amount FROM fee_payment_allocations a LEFT JOIN fee_payments p ON p.id=a.payment_id LEFT JOIN fee_invoices i ON i.id=a.invoice_id WHERE (p.id IS NULL OR i.id IS NULL) AND (p.school_id=:school_id OR i.school_id=:school_id)", ['school_id'=>$schoolId]),
            'reversed_payment_allocations' => $this->db->select("SELECT a.id, a.payment_id, a.invoice_id, a.amount FROM fee_payment_allocations a JOIN fee_payments p ON p.id=a.payment_id WHERE p.school_id=:school_id AND p.status='reversed'", ['school_id'=>$schoolId]),
            'duplicate_references' => $this->db->select("SELECT reference, COUNT(*) occurrences, SUM(amount) amount FROM fee_payments WHERE school_id=:school_id AND reference IS NOT NULL AND TRIM(reference)<>'' GROUP BY reference HAVING COUNT(*) > 1", ['school_id'=>$schoolId]),
            'stale_provider_transactions' => $this->db->select("SELECT id, provider, external_reference, status FROM payment_provider_transactions WHERE school_id=:school_id AND status IN ('initiated','pending') AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)", ['school_id'=>$schoolId]),
            'unreconciled_payments' => $this->db->select("SELECT p.payment_date, p.method, COUNT(*) transactions, SUM(p.amount) amount FROM fee_payments p LEFT JOIN fee_reconciliations r ON r.school_id=p.school_id AND r.reconciliation_date=p.payment_date AND r.method=p.method AND r.status='reconciled' WHERE p.school_id=:school_id AND p.status='confirmed' AND r.id IS NULL GROUP BY p.payment_date,p.method ORDER BY p.payment_date", ['school_id'=>$schoolId]),
        ];
    }

    public function summary(int $schoolId): array
    {
        $data=$this->scan($schoolId); $counts=[]; $total=0;
        foreach($data as $key=>$rows){$counts[$key]=count($rows);$total+=count($rows);}
        return ['total'=>$total,'counts'=>$counts,'healthy'=>$total===0];
    }
}
