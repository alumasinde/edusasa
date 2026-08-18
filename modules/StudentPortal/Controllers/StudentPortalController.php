<?php

declare(strict_types=1);

namespace Modules\StudentPortal\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\ValidationException;
use Modules\StudentPortal\Services\StudentPortalService;
use RuntimeException;

final class StudentPortalController extends BaseController
{
    public function __construct(private readonly StudentPortalService $service) {}

    public function index(Request $request): Response
    {
        try { return $this->view('studentportal.dashboard',$this->service->dashboard()); }
        catch (RuntimeException $e) { return $this->view('errors.forbidden',['message'=>$e->getMessage()],403); }
    }

    public function academics(Request $request): Response
    {
        try { return $this->view('studentportal.academics',$this->service->academics()); }
        catch (RuntimeException $e) { return $this->view('errors.forbidden',['message'=>$e->getMessage()],403); }
    }

    public function timetable(Request $request): Response
    {
        try { return $this->view('studentportal.timetable',$this->service->timetable()); }
        catch (RuntimeException $e) { return $this->view('errors.forbidden',['message'=>$e->getMessage()],403); }
    }

    public function attendance(Request $request): Response
    {
        try { return $this->view('studentportal.attendance',$this->service->attendance()); }
        catch (RuntimeException $e) { return $this->view('errors.forbidden',['message'=>$e->getMessage()],403); }
    }

    public function finance(Request $request): Response
    {
        try { return $this->view('studentportal.finance',$this->service->finance()); }
        catch (RuntimeException $e) { return $this->view('errors.forbidden',['message'=>$e->getMessage()],403); }
    }

    public function notifications(Request $request): Response
    {
        try { return $this->view('studentportal.notifications',$this->service->notifications(max(1,(int)$request->input('page',1)))); }
        catch (RuntimeException $e) { return $this->view('errors.forbidden',['message'=>$e->getMessage()],403); }
    }

    public function readNotification(Request $request,array $params): Response
    {
        try { $this->service->markNotificationRead((int)$params['id']); return $this->redirect('/student-portal/notifications','Notification marked as read.'); }
        catch (RuntimeException $e) { return $this->redirect('/student-portal/notifications','Unable to update notification.'); }
    }

    public function profile(Request $request): Response
    {
        try { return $this->view('studentportal.profile',['student'=>$this->service->context()['student'],'errors'=>[]]); }
        catch (RuntimeException $e) { return $this->view('errors.forbidden',['message'=>$e->getMessage()],403); }
    }

    public function updateProfile(Request $request): Response
    {
        try {
            $data=$this->validate($request,['phone'=>'max:40','email'=>'email','address'=>'max:500']);
            $this->service->updateProfile($data);
            return $this->redirect('/student-portal/profile','Profile updated.');
        } catch (ValidationException $e) {
            return $this->view('studentportal.profile',['student'=>$this->service->context()['student'],'errors'=>$e->errors()],422);
        } catch (RuntimeException $e) {
            return $this->view('studentportal.profile',['student'=>$this->service->context()['student'],'errors'=>['profile'=>[$e->getMessage()]]],422);
        }
    }
}
