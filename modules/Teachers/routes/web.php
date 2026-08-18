<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */

use Modules\Teachers\Controllers\TeacherController;
use Modules\Teachers\Controllers\TeacherSubjectAutoAssignController;

$router->group([
    'prefix' => '/teachers',
    'middleware' => ['tenant', 'auth', 'permission:teachers.view,teachers.manage'],
], function ($router) {
    $router->get('', [TeacherController::class, 'index']);
    $router->get('/create', [TeacherController::class, 'create'], ['permission:teachers.manage']);
    $router->post('', [TeacherController::class, 'store'], ['csrf', 'permission:teachers.manage']);
    $router->get('/{id}', [TeacherController::class, 'show']);
    $router->get('/{id}/edit', [TeacherController::class, 'edit'], ['permission:teachers.manage']);
    $router->post('/{id}', [TeacherController::class, 'update'], ['csrf', 'permission:teachers.manage']);
    $router->post('/{id}/status', [TeacherController::class, 'changeStatus'], ['csrf', 'permission:teachers.manage']);
    $router->get('/{id}/subjects', [TeacherController::class, 'subjects'], ['permission:teachers.view,teachers.manage']);
    $router->post('/{id}/subjects', [TeacherController::class, 'updateSubjects'], ['csrf', 'permission:teachers.manage']);
    $router->get('/{id}/classes', [TeacherController::class, 'classes'], ['permission:teachers.view,teachers.manage']);
    $router->post('/{id}/classes', [TeacherController::class, 'updateClasses'], ['csrf', 'permission:teachers.manage']);
});
