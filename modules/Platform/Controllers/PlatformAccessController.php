<?php

declare(strict_types=1);

namespace Modules\Platform\Controllers;

use App\Core\Request;
use App\Core\Response;
use Modules\Platform\Services\PlatformRbacService;

final class PlatformAccessController
{
    public function __construct(private readonly PlatformRbacService $rbac) {}

    public function index(Request $request, array $params): Response
    {
        $this->rbac->assert('platform.roles.view');
        return Response::view('platform.access', array_merge($this->rbac->catalog(), ['users'=>$this->rbac->users()]));
    }

    public function saveRole(Request $request, array $params): Response
    {
        $this->rbac->assert('platform.roles.manage');
        try {
            $id=$this->rbac->saveRole($request->all(), isset($params['id'])?(int)$params['id']:null);
            $this->rbac->setPermissions($id, (array)$request->input('permission_ids',[]));
            return Response::redirect('/platform/access');
        } catch (\Throwable $e) {
            return Response::json(['success'=>false,'message'=>$e->getMessage()],422);
        }
    }

    public function assignRoles(Request $request, array $params): Response
    {
        $this->rbac->assert('platform.users.manage');
        try {
            $this->rbac->assignRoles((int)$params['id'], (array)$request->input('role_ids',[]));
            return Response::redirect('/platform/access');
        } catch (\Throwable $e) {
            return Response::json(['success'=>false,'message'=>$e->getMessage()],422);
        }
    }
}
