<?php

declare(strict_types=1);

use Modules\Settings\Controllers\AdministrationController;

$router->group(['prefix'=>'/settings','middleware'=>['tenant','auth']],function($router){
    $router->get('',[AdministrationController::class,'index'],['permission:settings.view']);
    $router->post('',[AdministrationController::class,'update'],['csrf','permission:settings.manage']);
    $router->get('/audit',[AdministrationController::class,'audit'],['permission:settings.audit']);
});
