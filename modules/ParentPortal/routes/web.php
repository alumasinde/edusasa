<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */
use Modules\ParentPortal\Controllers\ParentPortalController;

$router->group(['prefix'=>'/parent-portal','middleware'=>['tenant','auth']],function($router){
    $router->get('',[ParentPortalController::class,'index'],['permission:parentportal.view']);
    $router->get('/children/{id}',[ParentPortalController::class,'child'],['permission:parentportal.view']);
    $router->get('/notifications',[ParentPortalController::class,'notifications'],['permission:parentportal.notifications']);
    $router->post('/notifications/{id}/read',[ParentPortalController::class,'readNotification'],['csrf','permission:parentportal.notifications']);
    $router->get('/profile',[ParentPortalController::class,'profile'],['permission:parentportal.profile']);
    $router->post('/profile',[ParentPortalController::class,'updateProfile'],['csrf','permission:parentportal.profile']);
});
