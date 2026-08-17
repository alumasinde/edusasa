<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */

use Modules\Students\Controllers\StudentAchievementController;
use Modules\Students\Controllers\StudentController;
use Modules\Students\Controllers\StudentDisciplineController;
use Modules\Students\Controllers\StudentDocumentController;
use Modules\Students\Controllers\StudentGuardianController;
use Modules\Students\Controllers\StudentMedicalController;

$router->group([
    'prefix' => '/students',
    'middleware' => ['tenant', 'auth', 'permission:students.view,students.manage'],
], function ($router) {
    $router->get('', [StudentController::class, 'index']);
    $router->get('/export', [StudentController::class, 'export'], ['permission:students.manage']);
    $router->get('/import', [\Modules\Students\Controllers\StudentImportController::class, 'show'], ['permission:students.manage']);
    $router->get('/import/template', [\Modules\Students\Controllers\StudentImportController::class, 'template'], ['permission:students.manage']);
    $router->post('/import/preview', [\Modules\Students\Controllers\StudentImportController::class, 'preview'], ['csrf', 'permission:students.manage']);
    $router->post('/import/commit', [\Modules\Students\Controllers\StudentImportController::class, 'commit'], ['csrf', 'permission:students.manage']);
    $router->get('/create', [StudentController::class, 'create'], ['permission:students.manage']);
    $router->post('', [StudentController::class, 'store'], ['csrf', 'permission:students.manage']);
    $router->get('/{id}', [StudentController::class, 'show']);
    $router->get('/{id}/edit', [StudentController::class, 'edit'], ['permission:students.manage']);
    $router->post('/{id}', [StudentController::class, 'update'], ['csrf', 'permission:students.manage']);
    $router->post('/{id}/status', [StudentController::class, 'changeStatus'], ['csrf', 'permission:students.manage']);
    $router->post('/{id}/delete', [StudentController::class, 'destroy'], ['csrf', 'permission:students.manage', 'role:administrator']);
    $router->post('/{id}/guardians', [StudentGuardianController::class, 'store'], ['csrf', 'permission:students.manage']);
    $router->post('/{id}/guardians/{guardianLinkId}/delete', [StudentGuardianController::class, 'destroy'], ['csrf', 'permission:students.manage']);
    $router->post('/{id}/medical', [StudentMedicalController::class, 'update'], ['csrf', 'permission:students.manage']);
    $router->post('/{id}/discipline', [StudentDisciplineController::class, 'store'], ['csrf', 'permission:students.manage']);
    $router->post('/{id}/achievements', [StudentAchievementController::class, 'store'], ['csrf', 'permission:students.manage']);
    $router->post('/{id}/documents', [StudentDocumentController::class, 'store'], ['csrf', 'permission:students.manage']);
    $router->get('/{id}/documents/{documentId}', [StudentDocumentController::class, 'download'], ['permission:students.view,students.manage']);
    $router->post('/{id}/documents/{documentId}/delete', [StudentDocumentController::class, 'destroy'], ['csrf', 'permission:students.manage']);
});