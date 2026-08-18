<?php

declare(strict_types=1);

namespace Modules\Settings\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\ValidationException;
use Modules\Settings\Services\AdministrationService;
use RuntimeException;

final class AdministrationController extends BaseController
{
    public function __construct(private readonly AdministrationService $service) {}

    public function index(Request $request): Response
    {
        try{return $this->view('settings.index',$this->service->settings());}
        catch(RuntimeException $e){return $this->view('errors.forbidden',['message'=>$e->getMessage()],403);}
    }

    public function update(Request $request): Response
    {
        try{$data=$this->validate($request,['name'=>'required|max:190','email'=>'email|max:190','phone'=>'max:40','address'=>'max:255','timezone'=>'required|max:80','default_currency'=>'max:3','date_format'=>'max:30','attendance_cutoff_time'=>'max:5','academic_year_label'=>'max:100','term_label'=>'max:100']);$this->service->update($data);return $this->redirect('/settings','School settings updated.');}
        catch(ValidationException $e){$ctx=$this->service->settings();return $this->view('settings.index',$ctx+['errors'=>$e->errors()],422);}
        catch(RuntimeException $e){$ctx=$this->service->settings();return $this->view('settings.index',$ctx+['errors'=>['settings'=>[$e->getMessage()]]],422);}
    }

    public function audit(Request $request): Response
    {
        try{return $this->view('settings.audit',$this->service->audit(max(1,(int)$request->input('page',1))));}
        catch(RuntimeException $e){return $this->view('errors.forbidden',['message'=>$e->getMessage()],403);}
    }
}
