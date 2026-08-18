<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */

use Modules\Exams\Controllers\ExaminationController;

$router->group([
    'prefix'=>'/exams',
    'middleware'=>['tenant','auth','permission:exams.view,exams.manage'],
],function($router){
    $router->get('',[ExaminationController::class,'index']);
    $router->get('/create',[ExaminationController::class,'create'],['permission:exams.manage']);
    $router->post('',[ExaminationController::class,'store'],['csrf','permission:exams.manage']);
    $router->get('/{id}',[ExaminationController::class,'show']);
    $router->post('/{id}/status',[ExaminationController::class,'status'],['csrf','permission:exams.manage']);
});
