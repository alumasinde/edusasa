<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Modules\Finance\Repositories\PaymentChannelRepository;
use RuntimeException;

final class PaymentChannelService
{
    public function __construct(private readonly PaymentChannelRepository $repository) {}
    public function all(): array { return $this->repository->all(); }
    public function active(): array { return $this->repository->active(); }
    public function save(array $input, int $id=0): int
    {
        $code=strtolower(trim((string)($input['code']??'')));
        $name=trim((string)($input['name']??''));
        $type=strtolower(trim((string)($input['type']??'other')));
        $provider=trim((string)($input['provider']??''));
        $instructions=trim((string)($input['instructions']??''));
        $config=$input['config']??[];
        if (!is_array($config)) throw new RuntimeException('Payment configuration must be valid JSON data.');
        if ($code==='' || !preg_match('/^[a-z0-9][a-z0-9_-]{1,59}$/',$code)) throw new RuntimeException('Use a valid payment channel code.');
        if ($name==='') throw new RuntimeException('Payment channel name is required.');
        if (!in_array($type,['mpesa','bank','cash','cheque','other'],true)) throw new RuntimeException('Invalid payment channel type.');
        $data=['code'=>$code,'name'=>$name,'type'=>$type,'provider'=>$provider?:null,'config_json'=>json_encode($config,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'instructions'=>$instructions?:null,'active'=>!empty($input['active'])?1:0,'default'=>!empty($input['default'])?1:0,'sort_order'=>max(0,(int)($input['sort_order']??0)),'parent'=>!empty($input['parent'])?1:0,'staff'=>!empty($input['staff'])?1:0,'reference'=>!empty($input['reference'])?1:0];
        if ($id>0) { $this->repository->update($id,$data); return $id; }
        return $this->repository->create($data);
    }
    public function delete(int $id): void { if($id<1) throw new RuntimeException('Invalid payment channel.'); $this->repository->delete($id); }
}
