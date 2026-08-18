<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Modules\Finance\Repositories\StudentLedgerRepository;
use RuntimeException;

final class StudentLedgerService
{
    public function __construct(private readonly StudentLedgerRepository $repository) {}

    private function date(?string $value): ?string
    {
        if ($value===null || trim($value)==='') return null;
        $value=trim($value); $d=\DateTimeImmutable::createFromFormat('Y-m-d',$value);
        if (!$d || $d->format('Y-m-d')!==$value) throw new RuntimeException('Invalid ledger date.');
        return $value;
    }

    public function ledger(int $studentId, ?string $from, ?string $to): array
    {
        $from=$this->date($from); $to=$this->date($to);
        if($from!==null && $to!==null && $from>$to) throw new RuntimeException('Start date cannot be after end date.');
        return $this->repository->ledger($studentId,$from,$to);
    }

    public function statement(int $studentId, ?string $from, ?string $to): array
    {
        $from=$this->date($from); $to=$this->date($to);
        if($from!==null && $to!==null && $from>$to) throw new RuntimeException('Start date cannot be after end date.');
        return $this->repository->statement($studentId,$from,$to);
    }
}
