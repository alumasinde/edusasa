<?php

declare(strict_types=1);

namespace Modules\Finance\Repositories;

use App\Core\Database;
use App\Core\Tenant;
use RuntimeException;

final class PaymentChannelRepository
{
    public function __construct(private readonly Database $db) {}

    public function all(): array
    {
        return $this->db->select('SELECT * FROM school_payment_channels WHERE school_id=:school_id ORDER BY sort_order, name', ['school_id'=>Tenant::id()]);
    }

    public function active(): array
    {
        return $this->db->select('SELECT * FROM school_payment_channels WHERE school_id=:school_id AND is_active=1 ORDER BY is_default DESC, sort_order, name', ['school_id'=>Tenant::id()]);
    }

    public function create(array $data): int
    {
        $schoolId=Tenant::id();
        if ($data['is_default']) {
            $this->db->execute('UPDATE school_payment_channels SET is_default=0 WHERE school_id=:school_id', ['school_id'=>$schoolId]);
        }
        return (int)$this->db->insert('INSERT INTO school_payment_channels (school_id,code,name,type,provider,config_json,instructions,is_active,is_default,sort_order,allow_parent_payment,allow_staff_entry,requires_reference) VALUES (:school_id,:code,:name,:type,:provider,:config_json,:instructions,:active,:default,:sort_order,:parent,:staff,:reference)', $data + ['school_id'=>$schoolId]);
    }

    public function update(int $id, array $data): void
    {
        $schoolId=Tenant::id();
        $existing=$this->db->selectOne('SELECT id FROM school_payment_channels WHERE id=:id AND school_id=:school_id', ['id'=>$id,'school_id'=>$schoolId]);
        if (!$existing) throw new RuntimeException('Payment channel not found.');
        if ($data['is_default']) $this->db->execute('UPDATE school_payment_channels SET is_default=0 WHERE school_id=:school_id AND id<>:id', ['school_id'=>$schoolId,'id'=>$id]);
        $this->db->execute('UPDATE school_payment_channels SET code=:code,name=:name,type=:type,provider=:provider,config_json=:config_json,instructions=:instructions,is_active=:active,is_default=:default,sort_order=:sort_order,allow_parent_payment=:parent,allow_staff_entry=:staff,requires_reference=:reference WHERE id=:id AND school_id=:school_id', $data + ['id'=>$id,'school_id'=>$schoolId]);
    }

    public function delete(int $id): void
    {
        $this->db->execute('DELETE FROM school_payment_channels WHERE id=:id AND school_id=:school_id', ['id'=>$id,'school_id'=>Tenant::id()]);
    }
}
