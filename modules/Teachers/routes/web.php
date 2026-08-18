<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */
use Modules\Teachers\Controllers\TeacherController;
use Modules\Teachers\Controllers\TeacherSubjectMatrixController;

$router->group(['prefix'=>'/teachers','middleware'=>['tenant','auth','module:teachers','permission:teachers.view,teachers.manage']],function($router){
    $router->get('',[TeacherController::class,'index']);
    $router->get('/export',[TeacherController::class,'export'],['permission:teachers.manage']);
    $router->get('/subject-assignments/matrix',[TeacherSubjectMatrixController::class,'show'],['permission:teachers.manage']);
    $router->post('/subject-assignments/matrix',[TeacherSubjectMatrixController::class,'update'],['csrf','permission:teachers.manage']);
    $router->get('/create',[TeacherController::class,'create'],['permission:teachers.manage']);
    $router->post('',[TeacherController::class,'store'],['csrf','permission:teachers.manage']);
    $router->get('/{id}',[TeacherController::class,'show']);
    $router->get('/{id}/edit',[TeacherController::class,'edit'],['permission:teachers.manage']);
    $router->post('/{id}',[TeacherController::class,'update'],['csrf','permission:teachers.manage']);
    $router->post('/{id}/status',[TeacherController::class,'setStatus'],['csrf','permission:teachers.manage']);
    $router->post('/{id}/delete',[TeacherController::class,'destroy'],['csrf','permission:teachers.manage','role:administrator']);
    $router->post('/{id}/subjects',[TeacherController::class,'assignSubject'],['csrf','permission:teachers.manage']);
    $router->post('/{id}/subjects/{assignmentId}/delete',[TeacherController::class,'unassignSubject'],['csrf','permission:teachers.manage']);
});
