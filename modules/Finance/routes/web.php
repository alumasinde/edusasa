<?php

declare(strict_types=1);
/** @var \App\Core\Router $router */
use Modules\Finance\Controllers\FinanceController;
use Modules\Finance\Controllers\FeeStructureController;
use Modules\Finance\Controllers\StudentLedgerController;
use Modules\Finance\Controllers\ReconciliationController;
$router->group(['prefix'=>'/finance','middleware'=>['tenant','auth','permission:finance.view,finance.manage']],function($router){
    $router->get('',[FinanceController::class,'index']);
    $router->get('/categories',[FinanceController::class,'categories'],['permission:finance.manage']);
    $router->post('/categories',[FinanceController::class,'storeCategory'],['csrf','permission:finance.manage']);
    $router->get('/invoices/create',[FinanceController::class,'createInvoice'],['permission:finance.manage']);
    $router->post('/invoices',[FinanceController::class,'storeInvoice'],['csrf','permission:finance.manage']);
    $router->get('/payments/create',[FinanceController::class,'createPayment'],['permission:finance.payments']);
    $router->post('/payments',[FinanceController::class,'storePayment'],['csrf','permission:finance.payments']);
    $router->get('/students',[FinanceController::class,'students'],['permission:finance.view']);
    $router->get('/students/{id}/ledger',[StudentLedgerController::class,'ledger'],['permission:finance.view']);
    $router->get('/students/{id}/statement',[StudentLedgerController::class,'statement'],['permission:finance.view']);
    $router->get('/fee-structures',[FeeStructureController::class,'index'],['permission:finance.view']);
    $router->get('/fee-structures/create',[FeeStructureController::class,'create'],['permission:finance.manage']);
    $router->post('/fee-structures',[FeeStructureController::class,'store'],['csrf','permission:finance.manage']);
    $router->post('/fee-structures/publish',[FeeStructureController::class,'publish'],['csrf','permission:finance.manage']);
    $router->post('/fee-structures/generate',[FeeStructureController::class,'generate'],['csrf','permission:finance.manage']);
    $router->get('/reconciliation',[ReconciliationController::class,'index'],['permission:finance.reports']);
    $router->post('/reconciliation/save',[ReconciliationController::class,'save'],['csrf','permission:finance.reports']);
    $router->post('/reconciliation/confirm',[ReconciliationController::class,'confirm'],['csrf','permission:finance.reports']);
});
