<?php

declare(strict_types=1);

namespace Modules\Finance\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Modules\Finance\Services\PaymentChannelService;
use RuntimeException;

final class PaymentChannelController extends BaseController
{
    public function __construct(private readonly PaymentChannelService $service) {}
    public function index(Request $request): Response { return $this->view('finance.payment_channels',['channels'=>$this->service->all()]); }
    public function save(Request $request): Response
    {
        try {
            $config=json_decode((string)$request->input('config_json','{}'),true);
            if(!is_array($config)) throw new RuntimeException('Configuration must be valid JSON.');
            $id=$this->service->save(['code'=>$request->input('code'),'name'=>$request->input('name'),'type'=>$request->input('type'),'provider'=>$request->input('provider'),'instructions'=>$request->input('instructions'),'config'=>$config,'active'=>$request->input('active'),'default'=>$request->input('default'),'sort_order'=>$request->input('sort_order'),'parent'=>$request->input('parent'),'staff'=>$request->input('staff'),'reference'=>$request->input('reference')],(int)$request->input('id',0));
            Session::flash('success',$id?'Payment channel saved.':'Payment channel added.');
        } catch(RuntimeException $e) { Session::flash('error',$e->getMessage()); }
        return $this->redirect('/finance/payment-methods');
    }
    public function delete(Request $request): Response
    {
        try{$this->service->delete((int)$request->input('id'));Session::flash('success','Payment channel removed.');}catch(RuntimeException $e){Session::flash('error',$e->getMessage());}
        return $this->redirect('/finance/payment-methods');
    }
}
