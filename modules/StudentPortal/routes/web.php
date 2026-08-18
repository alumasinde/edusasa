<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */
use Modules\StudentPortal\Controllers\StudentPortalController;

$router->group(['prefix'=>'/student-portal','middleware'=>['tenant','auth']],function($router){
    $router->get('',[StudentPortalController::class,'index'],['permission:studentportal.view']);
    $router->get('/academics',[StudentPortalController::class,'academics'],['permission:studentportal.view']);
    $router->get('/timetable',[StudentPortalController::class,'timetable'],['permission:studentportal.view']);
    $router->get('/attendance',[StudentPortalController::class,'attendance'],['permission:studentportal.view']);
    $router->get('/finance',[StudentPortalController::class,'finance'],['permission:studentportal.view']);
    $router->get('/notifications',[StudentPortalController::class,'notifications'],['permission:studentportal.notifications']);
    $router->post('/notifications/{id}/read',[StudentPortalController::class,'readNotification'],['csrf','permission:studentportal.notifications']);
    $router->get('/profile',[StudentPortalController::class,'profile'],['permission:studentportal.profile']);
    $router->post('/profile',[StudentPortalController::class,'updateProfile'],['csrf','permission:studentportal.profile']);
});
