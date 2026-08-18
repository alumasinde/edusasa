<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use App\Core\Session;
use Modules\Finance\Repositories\FinanceRepository;
use RuntimeException;

final class FinanceService
{
    public function __construct(private readonly FinanceRepository $repository) {}

    public function dashboard(): array { return $this->repository->dashboard(); }
    public function students(string $search=''): array { return $this->repository->students(trim($search)); }
    public function categories(): array { return $this->repository->categories(); }
    public function invoices(string $search=''): array { return $this->repository->invoices(trim($search)); }

    public function createCategory(string $name,string $code,string $description): int
    {
        $name=trim($name); $code=strtolower(trim($code));
        if($name==='') throw new RuntimeException('Category name is required.');
        if(!preg_match('/^[a-z0-9][a-z0-9_-]{1,59}$/',$code)) throw new RuntimeException('Category code must contain only letters, numbers, hyphens or underscores.');
        return $this->repository->createCategory($name,$code,trim($description));
    }

    public function createInvoice(array $data): int
    {
        $studentId=(int)($data['student_id']??0); $invoiceNo=trim((string)($data['invoice_no']??''));
        $date=(string)($data['invoice_date']??date('Y-m-d')); $due=trim((string)($data['due_date']??''));
        $items=(array)($data['items']??[]); $discount=(float)($data['discount']??0);
        if($studentId<1 || $invoiceNo==='') throw new RuntimeException('Student and invoice number are required.');
        if(!preg_match('/^[A-Za-z0-9._\/-]{3,80}$/',$invoiceNo)) throw new RuntimeException('Invalid invoice number.');
        foreach($items as $item){ if(trim((string)($item['description']??''))==='' || (float)($item['quantity']??0)<=0) throw new RuntimeException('Each invoice item needs a description and positive quantity.'); }
        return $this->repository->createInvoice($studentId,$invoiceNo,$date,$due!==''?$due:null,$items,$discount,Session::userId());
    }

    public function recordPayment(array $data): int
    {
        return $this->repository->recordPayment(
            (int)($data['student_id']??0), trim((string)($data['receipt_no']??'')),
            (string)($data['payment_date']??date('Y-m-d')), (float)($data['amount']??0),
            trim((string)($data['method']??'')), trim((string)($data['reference']??''))?:null,
            trim((string)($data['payer_name']??''))?:null, Session::userId(), (array)($data['allocations']??[])
        );
    }
}
