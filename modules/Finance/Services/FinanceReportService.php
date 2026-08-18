<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use App\Core\Database;
use RuntimeException;

final class FinanceReportService
{
    public function __construct(private readonly Database $db) {}

    public function dashboard(int $schoolId, array $filters): array
    {
        $where = $this->where($schoolId, $filters);
        $params = $where['params'];
        $summary = $this->db->selectOne("SELECT COALESCE(SUM(i.total),0) billed, COALESCE(SUM(i.paid_amount),0) collected, COALESCE(SUM(i.balance),0) outstanding FROM fee_invoices i WHERE i.school_id=:school_id AND i.deleted_at IS NULL AND i.invoice_date BETWEEN :from AND :to", $params) ?? [];
        $payments = $this->db->select("SELECT p.method, COUNT(*) transactions, COALESCE(SUM(p.amount),0) amount FROM fee_payments p WHERE p.school_id=:school_id AND p.status='confirmed' AND p.payment_date BETWEEN :from AND :to" . ($filters['method'] !== '' ? ' AND p.method=:method' : '') . " GROUP BY p.method ORDER BY amount DESC", $params + ($filters['method'] !== '' ? ['method'=>$filters['method']] : []));
        $outstanding = $this->db->select("SELECT COALESCE(c.name,'Unassigned') class_name, COUNT(DISTINCT i.student_id) students, COALESCE(SUM(i.balance),0) outstanding FROM fee_invoices i INNER JOIN students s ON s.id=i.student_id AND s.school_id=i.school_id LEFT JOIN classes c ON c.id=s.current_class_id WHERE i.school_id=:school_id AND i.deleted_at IS NULL AND i.balance>0 AND i.invoice_date<=:to" . ($filters['class_id'] > 0 ? ' AND s.current_class_id=:class_id' : '') . " GROUP BY c.id,c.name ORDER BY outstanding DESC", $params + ($filters['class_id'] > 0 ? ['class_id'=>$filters['class_id']] : []));
        $daily = $this->db->select("SELECT p.payment_date, COUNT(*) transactions, COALESCE(SUM(p.amount),0) amount FROM fee_payments p WHERE p.school_id=:school_id AND p.status='confirmed' AND p.payment_date BETWEEN :from AND :to" . ($filters['method'] !== '' ? ' AND p.method=:method' : '') . " GROUP BY p.payment_date ORDER BY p.payment_date", $params + ($filters['method'] !== '' ? ['method'=>$filters['method']] : []));
        $adjustments = $this->db->selectOne("SELECT COALESCE(SUM(CASE WHEN type='discount' THEN amount ELSE 0 END),0) discounts, COALESCE(SUM(CASE WHEN type='waiver' THEN amount ELSE 0 END),0) waivers, COALESCE(SUM(CASE WHEN type='credit' THEN amount ELSE 0 END),0) credits FROM fee_adjustments WHERE school_id=:school_id AND status='approved' AND DATE(created_at) BETWEEN :from AND :to", $params) ?? [];
        $refunds = $this->db->selectOne("SELECT COALESCE(SUM(amount),0) refunded FROM fee_refunds WHERE school_id=:school_id AND status='processed' AND DATE(processed_at) BETWEEN :from AND :to", $params) ?? [];
        return compact('filters','summary','payments','outstanding','daily','adjustments','refunds');
    }

    public function export(int $schoolId, array $filters): array
    {
        $data = $this->dashboard($schoolId, $filters);
        return ['generated_at'=>date(DATE_ATOM), 'filters'=>$filters, 'summary'=>$data['summary'], 'payment_methods'=>$data['payments'], 'outstanding_by_class'=>$data['outstanding'], 'daily_collections'=>$data['daily'], 'adjustments'=>$data['adjustments'], 'refunds'=>$data['refunds']];
    }

    private function where(int $schoolId, array $filters): array
    {
        if ($schoolId < 1) throw new RuntimeException('Invalid school context.');
        return ['params'=>['school_id'=>$schoolId,'from'=>$filters['from'],'to'=>$filters['to']]];
    }
}
