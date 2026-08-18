<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */
use Modules\Finance\Controllers\StudentLedgerController;

$router->group(['prefix'=>'/finance/students','middleware'=>['tenant','auth','permission:finance.view,finance.manage']], function($router){
    $router->get('/{id}/ledger',[StudentLedgerController::class,'ledger'],['permission:finance.view']);
    $router->get('/{id}/statement',[StudentLedgerController::class,'statement'],['permission:finance.view']);
});
