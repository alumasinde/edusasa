<?php

declare(strict_types=1);
/** @var \App\Core\Router $router */
use Modules\Finance\Controllers\FinanceController;
$router->group(['prefix'=>'/finance','middleware'=>['tenant','auth','permission:finance.view,finance.manage']],function($router){
    $router->get('',[FinanceController::class,'index']);
    $router->get('/categories',[FinanceController::class,'categories'],['permission:finance.manage']);
    $router->post('/categories',[FinanceController::class,'storeCategory'],['csrf','permission:finance.manage']);
    $router->get('/invoices/create',[FinanceController::class,'createInvoice'],['permission:finance.manage']);
    $router->post('/invoices',[FinanceController::class,'storeInvoice'],['csrf','permission:finance.manage']);
    $router->get('/payments/create',[FinanceController::class,'createPayment'],['permission:finance.payments']);
    $router->post('/payments',[FinanceController::class,'storePayment'],['csrf','permission:finance.payments']);
    $router->get('/students',[FinanceController::class,'students'],['permission:finance.view']);
});
