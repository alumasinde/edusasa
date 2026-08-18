<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use App\Core\Session;
use Modules\Finance\Repositories\ReconciliationRepository;
use RuntimeException;

final class ReconciliationService
{
    public function __construct(private readonly ReconciliationRepository $repository) {}
    private function userId(): ?int { $id=Session::get('user_id'); return $id===null?null:(int)$id; }
    public function dashboard(string $date): array { if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)) throw new RuntimeException('Invalid reconciliation date.'); return $this->repository->summary($date); }
    public function payments(string $date, ?string $method=null): array { return $this->repository->payments($date,$method); }
    public function save(array $data): int
    {
        $date=(string)($data['date']??date('Y-m-d')); $method=strtolower(trim((string)($data['method']??''))); $actual=(float)($data['actual_amount']??0); $id=(int)($data['id']??0); $notes=trim((string)($data['notes']??''));
        if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)) throw new RuntimeException('Invalid reconciliation date.');
        if($method==='' || !preg_match('/^[a-z0-9_-]{2,40}$/',$method)) throw new RuntimeException('Invalid payment method.');
        return $this->repository->save($id,$date,$method,$actual,$notes,$this->userId());
    }
    public function reconcile(int $id): void { if($id<1) throw new RuntimeException('Invalid reconciliation.'); $this->repository->reconcile($id,$this->userId()); }
}
