<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */
use Modules\Timetable\Controllers\TimetableController;

$router->group(['prefix'=>'/timetable','middleware'=>['tenant','auth','module:timetable','permission:timetable.view,timetable.manage']],function($router){
    $router->get('',[TimetableController::class,'index']);
    $router->get('/create',[TimetableController::class,'create'],['permission:timetable.manage']);
    $router->post('',[TimetableController::class,'store'],['csrf','permission:timetable.manage']);
    $router->get('/{id}',[TimetableController::class,'show']);
    $router->post('/{id}/generate',[TimetableController::class,'generate'],['csrf','permission:timetable.manage']);
    $router->post('/{id}/publish',[TimetableController::class,'publish'],['csrf','permission:timetable.manage']);
    $router->post('/{id}/clear',[TimetableController::class,'clear'],['csrf','permission:timetable.manage']);
    $router->post('/{id}/entries',[TimetableController::class,'add'],['csrf','permission:timetable.manage']);
});
