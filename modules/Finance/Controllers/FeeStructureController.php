<?php

declare(strict_types=1);

namespace Modules\Finance\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Modules\Finance\Services\FeeStructureService;
use RuntimeException;

final class FeeStructureController extends BaseController
{
    public function __construct(private readonly FeeStructureService $service) {}
    public function index(Request $request): Response
    {
        return $this->view('finance.fee_structures.index',['structures'=>$this->service->structures(),'categories'=>$this->service->categories()]);
    }
    public function create(Request $request): Response
    {
        return $this->view('finance.fee_structures.create',['categories'=>$this->service->categories()]);
    }
    public function store(Request $request): Response
    {
        $names=(array)$request->input('item_name',[]);$amounts=(array)$request->input('item_amount',[]);$categories=(array)$request->input('category_id',[]);$mandatory=(array)$request->input('mandatory',[]);$items=[];
        foreach($names as $i=>$name){if(trim((string)$name)===''&&trim((string)($amounts[$i]??''))==='')continue;$items[]=['name'=>(string)$name,'amount'=>(float)($amounts[$i]??0),'category_id'=>(int)($categories[$i]??0),'mandatory'=>isset($mandatory[$i])];}
        try{$id=$this->service->create(['name'=>$request->input('name'),'target_class_id'=>$request->input('target_class_id'),'target_stream_id'=>$request->input('target_stream_id'),'academic_year_id'=>$request->input('academic_year_id'),'term_id'=>$request->input('term_id'),'items'=>$items]);Session::flash('success','Fee structure created.');return $this->redirect('/finance/fee-structures');}catch(RuntimeException $e){Session::flash('error',$e->getMessage());return $this->redirect('/finance/fee-structures/create');}
    }
    public function publish(Request $request): Response
    {try{$this->service->publish((int)$request->input('id'));Session::flash('success','Fee structure published.');}catch(RuntimeException $e){Session::flash('error',$e->getMessage());}return $this->redirect('/finance/fee-structures');}
    public function generate(Request $request): Response
    {try{$result=$this->service->generate(['fee_structure_id'=>$request->input('fee_structure_id'),'invoice_date'=>$request->input('invoice_date'),'due_date'=>$request->input('due_date'),'invoice_prefix'=>$request->input('invoice_prefix')]);Session::flash('success','Generated '.$result['invoices_created'].' invoices for KES '.number_format((float)$result['total_billed'],2).'.');}catch(RuntimeException $e){Session::flash('error',$e->getMessage());}return $this->redirect('/finance/fee-structures');}
}
