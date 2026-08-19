<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Tenant;

final class LoginController
{
    public function __construct(
        private readonly Database $db,
        private readonly Auth $auth,
    ) {}

    public function create(Request $request, array $params): Response
    {
        if ($this->auth->check()) {
            return Response::redirect('/dashboard');
        }

        return Response::view('auth.login', [
            'school' => Tenant::current(),
            'errors' => [],
            'old' => [],
        ]);
    }

    public function store(Request $request, array $params): Response
    {
        if ($this->auth->check()) {
            return Response::redirect('/dashboard');
        }

        try {
            Csrf::verify((string) $request->input('_csrf'));

            $email = strtolower(trim((string) $request->input('email')));
            $password = (string) $request->input('password');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
                throw new \RuntimeException('Enter a valid email address and password.');
            }

            $schoolId = Tenant::id();
            $user = $this->db->selectOne(
                'SELECT id, password_hash, status
                 FROM users
                 WHERE school_id = :school_id
                   AND email = :email
                   AND deleted_at IS NULL
                 LIMIT 1',
                [
                    'school_id' => $schoolId,
                    'email' => $email,
                ]
            );

            if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
                throw new \RuntimeException('The email or password you entered is incorrect.');
            }

            if (($user['status'] ?? 'active') !== 'active') {
                throw new \RuntimeException('Your account is not currently active. Please contact your school administrator.');
            }

            $this->db->execute(
                'UPDATE users SET updated_at = updated_at WHERE id = :id',
                ['id' => (int) $user['id']]
            );

            $this->auth->login((int) $user['id']);

            return Response::redirect('/dashboard');
        } catch (\App\Core\ForbiddenException $e) {
            return Response::view('auth.login', [
                'school' => Tenant::current(),
                'errors' => ['Your session expired. Please refresh the page and try again.'],
                'old' => ['email' => $request->input('email')],
            ], 403);
        } catch (\Throwable $e) {
            return Response::view('auth.login', [
                'school' => Tenant::current(),
                'errors' => [$e->getMessage()],
                'old' => ['email' => $request->input('email')],
            ], 422);
        }
    }

    public function destroy(Request $request, array $params): Response
    {
        $this->auth->logout();
        return Response::redirect('/login');
    }
}
