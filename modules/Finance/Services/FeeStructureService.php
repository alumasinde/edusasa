<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use App\Core\Session;
use Modules\Finance\Repositories\FeeStructureRepository;
use RuntimeException;

final class FeeStructureService
{
    public function __construct(private readonly FeeStructureRepository $repository) {}
    private function userId(): ?int { $id=Session::get('user_id'); return $id===null?null:(int)$id; }
    public function structures(): array { return $this->repository->structures(); }
    public function categories(): array { return $this->repository->categories(); }
    public function create(array $data): int
    {
        $name=trim((string)($data['name']??''));
        $classId=($data['target_class_id']??'')!==''?(int)$data['target_class_id']:null;
        $streamId=($data['target_stream_id']??'')!==''?(int)$data['target_stream_id']:null;
        $items=(array)($data['items']??[]);
        if($name==='') throw new RuntimeException('Fee structure name is required.');
        return $this->repository->createStructure($name,$classId,$streamId,($data['academic_year_id']??'')!==''?(int)$data['academic_year_id']:null,($data['term_id']??'')!==''?(int)$data['term_id']:null,$items,$this->userId());
    }
    public function publish(int $id): void { if($id<1) throw new RuntimeException('Invalid fee structure.'); $this->repository->publish($id); }
    public function generate(array $data): array
    {
        $id=(int)($data['fee_structure_id']??0);$date=(string)($data['invoice_date']??date('Y-m-d'));$due=trim((string)($data['due_date']??''));$prefix=strtoupper(trim((string)($data['invoice_prefix']??'FEE')));
        if($id<1) throw new RuntimeException('Select a fee structure.');
        if(!preg_match('/^[A-Z0-9_-]{2,20}$/',$prefix)) throw new RuntimeException('Invoice prefix must contain only letters, numbers, hyphens or underscores.');
        return $this->repository->generateInvoices($id,$date,$due!==''?$due:null,$prefix,$this->userId());
    }
}
