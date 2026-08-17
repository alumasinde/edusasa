<?php

declare(strict_types=1);

namespace Modules\Platform\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use Modules\Platform\Services\SchoolAdminActivationService;

final class SchoolAdminActivationController
{
    public function __construct(private readonly SchoolAdminActivationService $activation) {}

    public function create(Request $request, array $params): Response
    {
        $token = trim((string) $request->input('token'));
        $invitation = $token === '' ? null : $this->activation->invitation($token);
        return Response::view('platform.school-admin-activation', [
            'token' => $token,
            'invitation' => $invitation,
            'errors' => [],
        ], $invitation === null ? 404 : 200);
    }

    public function store(Request $request, array $params): Response
    {
        try {
            Csrf::verify((string) $request->input('_csrf'));
            $result = $this->activation->activate(
                (string) $request->input('token'),
                (string) $request->input('first_name'),
                (string) $request->input('last_name'),
                (string) $request->input('password'),
                (string) $request->input('password_confirmation')
            );
            return Response::redirect('/dashboard');
        } catch (\Throwable $e) {
            $token = trim((string) $request->input('token'));
            return Response::view('platform.school-admin-activation', [
                'token'=>$token,
                'invitation'=>$token === '' ? null : $this->activation->invitation($token),
                'errors'=>[$e->getMessage()],
            ], 422);
        }
    }
}
