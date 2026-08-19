<?php

declare(strict_types=1);

use App\Core\Router;
use Modules\Auth\Controllers\LoginController;

/** @var Router $router */
$router->group([
    'middleware' => ['tenant'],
], function (Router $router): void {
    $router->get('/login', [LoginController::class, 'create']);
    $router->post('/login', [LoginController::class, 'store']);
    $router->post('/logout', [LoginController::class, 'destroy'], ['auth']);
});
