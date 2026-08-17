<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */

use Modules\Attendance\Controllers\AttendanceController;

$router->group([
    'prefix' => '/attendance',
    'middleware' => ['tenant', 'auth', 'module:attendance', 'permission:attendance.view,attendance.manage'],
], function ($router) {
    $router->get('', [AttendanceController::class, 'index']);
    $router->get('/take', [AttendanceController::class, 'take']);
    $router->post('/take', [AttendanceController::class, 'store'], ['csrf', 'permission:attendance.manage']);
    $router->get('/report', [AttendanceController::class, 'report']);
});
