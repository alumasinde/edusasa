<?php

declare(strict_types=1);
namespace Modules\Platform\Controllers;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Modules\Platform\Services\PlatformBootstrapService;
use Modules\Platform\Services\PlatformLoginService;
final class PlatformSetupController {
 public function __construct(private readonly PlatformBootstrapService $bootstrap,private readonly PlatformLoginService $login){}
 public function create(Request $request,array $params):Response{if(!$this->bootstrap->available())return Response::view('platform.setup-complete',[],404);return Response::view('platform.setup',['errors'=>[]]);}
 public function store(Request $request,array $params):Response{try{Csrf::verify((string)$request->input('_csrf'));$email=(string)$request->input('email');$password=(string)$request->input('password');$this->bootstrap->create((string)$request->input('first_name'),(string)$request->input('last_name'),$email,$password,(string)$request->input('password_confirmation'));$this->login->login($email,$password);return Response::redirect('/platform');}catch(\Throwable $e){return Response::view('platform.setup',['errors'=>[$e->getMessage()],'old'=>['first_name'=>$request->input('first_name'),'last_name'=>$request->input('last_name'),'email'=>$request->input('email')]],422);}}
 public function loginForm(Request $request,array $params):Response{if(Session::has('platform_user_id'))return Response::redirect('/platform');return Response::view('platform.login',['errors'=>[]]);}
 public function login(Request $request,array $params):Response{try{Csrf::verify((string)$request->input('_csrf'));$this->login->login((string)$request->input('email'),(string)$request->input('password'));return Response::redirect('/platform');}catch(\Throwable $e){return Response::view('platform.login',['errors'=>['Invalid platform administrator credentials.']],422);}}
 public function logout(Request $request,array $params):Response{Session::remove('platform_user_id');Session::regenerate();return Response::redirect('/platform/login');}
}
