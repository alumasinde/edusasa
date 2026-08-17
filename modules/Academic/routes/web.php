<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */

use Modules\Academic\Controllers\AcademicYearController;
use Modules\Academic\Controllers\CbcLevelController;
use Modules\Academic\Controllers\DepartmentController;
use Modules\Academic\Controllers\SchoolClassController;
use Modules\Academic\Controllers\StreamController;
use Modules\Academic\Controllers\SubjectController;
use Modules\Academic\Controllers\TermController;

$router->group([
    'prefix' => '/academic',
    'middleware' => ['tenant', 'auth', 'permission:academic.view,academic.manage'],
], function ($router) {
    $router->get('/years', [AcademicYearController::class, 'index']);
    $router->get('/years/create', [AcademicYearController::class, 'create'], ['permission:academic.manage']);
    $router->post('/years', [AcademicYearController::class, 'store'], ['csrf', 'permission:academic.manage']);
    $router->get('/years/{id}/edit', [AcademicYearController::class, 'edit'], ['permission:academic.manage']);
    $router->post('/years/{id}', [AcademicYearController::class, 'update'], ['csrf', 'permission:academic.manage']);
    $router->post('/years/{id}/delete', [AcademicYearController::class, 'destroy'], ['csrf', 'permission:academic.manage']);
    $router->get('/terms', [TermController::class, 'index']);
    $router->get('/terms/create', [TermController::class, 'create'], ['permission:academic.manage']);
    $router->post('/terms', [TermController::class, 'store'], ['csrf', 'permission:academic.manage']);
    $router->get('/terms/{id}/edit', [TermController::class, 'edit'], ['permission:academic.manage']);
    $router->post('/terms/{id}', [TermController::class, 'update'], ['csrf', 'permission:academic.manage']);
    $router->post('/terms/{id}/delete', [TermController::class, 'destroy'], ['csrf', 'permission:academic.manage']);
    $router->get('/classes', [SchoolClassController::class, 'index']);
    $router->get('/classes/create', [SchoolClassController::class, 'create'], ['permission:academic.manage']);
    $router->post('/classes', [SchoolClassController::class, 'store'], ['csrf', 'permission:academic.manage']);
    $router->get('/classes/{id}/edit', [SchoolClassController::class, 'edit'], ['permission:academic.manage']);
    $router->post('/classes/{id}', [SchoolClassController::class, 'update'], ['csrf', 'permission:academic.manage']);
    $router->get('/classes/{id}/subjects', [SchoolClassController::class, 'subjects'], ['permission:academic.manage']);
    $router->post('/classes/{id}/subjects', [SchoolClassController::class, 'updateSubjects'], ['csrf', 'permission:academic.manage']);
    $router->post('/classes/{id}/subjects/auto-assign', [\Modules\Teachers\Controllers\TeacherSubjectAutoAssignController::class, 'assignForClass'], ['csrf', 'permission:academic.manage']);
    $router->post('/classes/{id}/subjects/repair', [SchoolClassController::class, 'repairAssignments'], ['csrf', 'permission:academic.manage']);
    $router->post('/classes/{id}/delete', [SchoolClassController::class, 'destroy'], ['csrf', 'permission:academic.manage']);
    $router->get('/streams', [StreamController::class, 'index']);
    $router->get('/streams/create', [StreamController::class, 'create'], ['permission:academic.manage']);
    $router->post('/streams', [StreamController::class, 'store'], ['csrf', 'permission:academic.manage']);
    $router->get('/streams/{id}/edit', [StreamController::class, 'edit'], ['permission:academic.manage']);
    $router->post('/streams/{id}', [StreamController::class, 'update'], ['csrf', 'permission:academic.manage']);
    $router->post('/streams/{id}/delete', [StreamController::class, 'destroy'], ['csrf', 'permission:academic.manage']);
    $router->get('/subjects', [SubjectController::class, 'index']);
    $router->get('/subjects/create', [SubjectController::class, 'create'], ['permission:academic.manage']);
    $router->post('/subjects', [SubjectController::class, 'store'], ['csrf', 'permission:academic.manage']);
    $router->get('/subjects/{id}/edit', [SubjectController::class, 'edit'], ['permission:academic.manage']);
    $router->post('/subjects/{id}', [SubjectController::class, 'update'], ['csrf', 'permission:academic.manage']);
    $router->post('/subjects/{id}/delete', [SubjectController::class, 'destroy'], ['csrf', 'permission:academic.manage']);
    $router->get('/departments', [DepartmentController::class, 'index']);
    $router->get('/departments/create', [DepartmentController::class, 'create'], ['permission:academic.manage']);
    $router->post('/departments', [DepartmentController::class, 'store'], ['csrf', 'permission:academic.manage']);
    $router->get('/departments/{id}/edit', [DepartmentController::class, 'edit'], ['permission:academic.manage']);
    $router->post('/departments/{id}', [DepartmentController::class, 'update'], ['csrf', 'permission:academic.manage']);
    $router->post('/departments/{id}/delete', [DepartmentController::class, 'destroy'], ['csrf', 'permission:academic.manage']);
    $router->get('/cbc-levels', [CbcLevelController::class, 'index']);
    $router->get('/cbc-levels/create', [CbcLevelController::class, 'create'], ['permission:academic.manage']);
    $router->post('/cbc-levels', [CbcLevelController::class, 'store'], ['csrf', 'permission:academic.manage']);
    $router->get('/cbc-levels/{id}/edit', [CbcLevelController::class, 'edit'], ['permission:academic.manage']);
    $router->post('/cbc-levels/{id}', [CbcLevelController::class, 'update'], ['csrf', 'permission:academic.manage']);
    $router->post('/cbc-levels/{id}/delete', [CbcLevelController::class, 'destroy'], ['csrf', 'permission:academic.manage']);
});
