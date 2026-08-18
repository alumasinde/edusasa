<?php

declare(strict_types=1);

namespace Modules\ParentPortal\Controllers;

use App\Core\Auth;
use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\ValidationException;
use Modules\ParentPortal\Services\ParentPortalService;
use RuntimeException;

final class ParentPortalController extends BaseController
{
    public function __construct(private readonly ParentPortalService $service, private readonly Auth $auth) {}

    public function index(Request $request): Response
    {
        try{return $this->view('parentportal.dashboard',$this->service->dashboard());}
        catch(RuntimeException $e){return $this->view('errors.forbidden',['message'=>$e->getMessage()],403);}
    }

    public function child(Request $request,array $params): Response
    {
        try{return $this->view('parentportal.child',$this->service->child((int)$params['id']));}
        catch(RuntimeException $e){return $this->view('errors.forbidden',['message'=>$e->getMessage()],403);}
    }

    public function notifications(Request $request): Response
    {
        try{return $this->view('parentportal.notifications',$this->service->notifications(max(1,(int)$request->input('page',1))));}
        catch(RuntimeException $e){return $this->view('errors.forbidden',['message'=>$e->getMessage()],403);}
    }

    public function readNotification(Request $request,array $params): Response
    {
        try{$this->service->markNotificationRead((int)$params['id']);return $this->redirect('/parent-portal/notifications','Notification marked as read.');}
        catch(RuntimeException $e){return $this->redirect('/parent-portal/notifications','Unable to update notification.');}
    }

    public function profile(Request $request): Response
    {
        try{$context=$this->service->context();return $this->view('parentportal.profile',['guardian'=>$context['guardian'],'errors'=>[]]);}
        catch(RuntimeException $e){return $this->view('errors.forbidden',['message'=>$e->getMessage()],403);}
    }

    public function updateProfile(Request $request): Response
    {
        try{$data=$this->validate($request,['phone'=>'max:40','address'=>'max:500']);$this->service->updateProfile($data);return $this->redirect('/parent-portal/profile','Profile updated.');}
        catch(ValidationException $e){$context=$this->service->context();return $this->view('parentportal.profile',['guardian'=>$context['guardian'],'errors'=>$e->errors()],422);}
        catch(RuntimeException $e){$context=$this->service->context();return $this->view('parentportal.profile',['guardian'=>$context['guardian'],'errors'=>['profile'=>[$e->getMessage()]]],422);}
    }
}
