<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Config\PlatformConfig;
use App\Core\ForbiddenException;
use App\Core\Request;
use App\Core\Response;

final class PlatformHostMiddleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        if (!PlatformConfig::requireHost($request->host())) {
            throw new ForbiddenException('Platform administration is only available on an approved platform host.');
        }
        return $next($request);
    }
}
