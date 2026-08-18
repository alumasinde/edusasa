<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */

use Modules\Exams\Controllers\ExaminationController;
use Modules\Exams\Controllers\ExaminationPaperController;

$router->group([
    'prefix'=>'/exams',
    'middleware'=>['tenant','auth','permission:exams.view,exams.manage'],
],function($router){
    $router->get('',[ExaminationController::class,'index']);
    $router->get('/create',[ExaminationController::class,'create'],['permission:exams.manage']);
    $router->post('',[ExaminationController::class,'store'],['csrf','permission:exams.manage']);
    $router->get('/{id}/papers',[ExaminationPaperController::class,'index'],['permission:exams.papers.view,exams.papers.manage']);
    $router->post('/{id}/papers',[ExaminationPaperController::class,'store'],['csrf','permission:exams.papers.manage']);
    $router->post('/{id}/papers/status',[ExaminationPaperController::class,'status'],['csrf','permission:exams.papers.manage']);
    $router->post('/{id}/papers/delete',[ExaminationPaperController::class,'delete'],['csrf','permission:exams.papers.manage']);
    $router->get('/{id}',[ExaminationController::class,'show']);
    $router->post('/{id}/status',[ExaminationController::class,'status'],['csrf','permission:exams.manage']);
});
