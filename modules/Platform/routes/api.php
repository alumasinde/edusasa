<?php

declare(strict_types=1);

use App\Core\Router;
use Modules\Platform\Controllers\PlatformController;

/** @var Router $router */
$router->group([
    'prefix' => '/api/platform',
    'middleware' => ['platform_host','platform_auth'],
], function (Router $router): void {
    $router->get('/schools', [PlatformController::class,'schools']);
    $router->post('/schools', [PlatformController::class,'createSchool']);
    $router->patch('/schools/{id}/status', [PlatformController::class,'status']);
    $router->get('/schools/{id}/features/{feature}', [PlatformController::class,'feature']);
});
