<?php

declare(strict_types=1);
/** @var \App\Core\Router $router */
use Modules\Communication\Controllers\CommunicationController;
$router->group(['prefix'=>'/communication','middleware'=>['tenant','auth','module:communication','permission:communication.view,communication.inbox']],function($router){
 $router->get('',[CommunicationController::class,'index'],['permission:communication.view']);
 $router->get('/create',[CommunicationController::class,'create'],['permission:communication.manage']);
 $router->post('',[CommunicationController::class,'store'],['csrf','permission:communication.manage']);
 $router->get('/inbox',[CommunicationController::class,'inbox'],['permission:communication.inbox']);
 $router->post('/{id}/read',[CommunicationController::class,'read'],['csrf','permission:communication.inbox']);
 $router->get('/{id}',[CommunicationController::class,'show']);
 $router->post('/{id}/publish',[CommunicationController::class,'publish'],['csrf','permission:communication.send']);
 $router->post('/{id}/archive',[CommunicationController::class,'archive'],['csrf','permission:communication.manage']);
});
