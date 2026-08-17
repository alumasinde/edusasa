<?php

declare(strict_types=1);

use App\Core\Router;
use Modules\Platform\Controllers\PlatformController;

/** @var Router $router */
$router->group([
    'prefix' => '/platform',
    'middleware' => ['platform_host', 'platform_auth'],
], function (Router $router): void {
    $router->get('/', [PlatformController::class, 'dashboard']);
    $router->get('/schools', [PlatformController::class, 'schoolPage']);
});
