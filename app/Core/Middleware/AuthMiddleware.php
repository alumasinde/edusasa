<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

final class AuthMiddleware
{
    public function __construct(private readonly Auth $auth) {}

    public function handle(Request $request, \Closure $next): Response
    {
        if ($this->auth->check()) {
            return $next($request);
        }

        // Browser requests should enter the tenant login flow instead of
        // exposing an authentication error page. APIs still receive 401.
        if (!$request->isApi()) {
            return Response::redirect('/login');
        }

        throw new \App\Core\UnauthorizedException('Authentication required.');
    }
}
