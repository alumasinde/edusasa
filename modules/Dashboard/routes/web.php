<?php

declare(strict_types=1);

use App\Core\Router;
use Modules\Dashboard\Controllers\DashboardController;

/** @var Router $router */
$router->group([
    'middleware' => ['tenant', 'auth'],
], function (Router $router): void {
    $router->get('/dashboard', [DashboardController::class, 'index']);
    $router->get('/', [DashboardController::class, 'index']);
});
