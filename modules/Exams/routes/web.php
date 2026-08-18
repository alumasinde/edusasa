<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */
use Modules\Exams\Controllers\ExaminationController;
use Modules\Exams\Controllers\ExaminationPaperController;
use Modules\Exams\Controllers\MarksEntryController;
use Modules\Exams\Controllers\GradingController;
use Modules\Exams\Controllers\ResultPublishingController;
use Modules\Exams\Controllers\ReportCardController;
use Modules\Exams\Controllers\ReportCardRenderController;
use Modules\Exams\Controllers\ExaminationAnalyticsController;

$router->group(['prefix'=>'/exams','middleware'=>['tenant','auth','permission:exams.view,exams.manage']],function($router){
$router->get('',[ExaminationController::class,'index']);$router->get('/create',[ExaminationController::class,'create'],['permission:exams.manage']);$router->post('',[ExaminationController::class,'store'],['csrf','permission:exams.manage']);
$router->get('/{id}/papers',[ExaminationPaperController::class,'index'],['permission:exams.papers.view,exams.papers.manage']);$router->post('/{id}/papers',[ExaminationPaperController::class,'store'],['csrf','permission:exams.papers.manage']);$router->post('/{id}/papers/status',[ExaminationPaperController::class,'status'],['csrf','permission:exams.papers.manage']);$router->post('/{id}/papers/delete',[ExaminationPaperController::class,'delete'],['csrf','permission:exams.papers.manage']);
$router->get('/{id}/papers/{paper_id}/marks',[MarksEntryController::class,'index'],['permission:exams.marks.view,exams.marks.manage']);$router->post('/{id}/papers/{paper_id}/marks',[MarksEntryController::class,'save'],['csrf','permission:exams.marks.manage']);$router->post('/{id}/papers/{paper_id}/marks/submit',[MarksEntryController::class,'submit'],['csrf','permission:exams.marks.manage']);$router->post('/{id}/papers/{paper_id}/marks/lock',[MarksEntryController::class,'lock'],['csrf','permission:exams.marks.manage']);
$router->get('/{id}/grading',[GradingController::class,'index'],['permission:exams.grading.view,exams.grading.manage']);$router->post('/{id}/grading/scales',[GradingController::class,'createScale'],['csrf','permission:exams.grading.manage']);$router->post('/{id}/grading/calculate',[GradingController::class,'calculate'],['csrf','permission:exams.grading.manage']);
$router->get('/{id}/results',[ResultPublishingController::class,'index'],['permission:exams.results.approve,exams.results.publish']);$router->post('/{id}/results/approve',[ResultPublishingController::class,'approve'],['csrf','permission:exams.results.approve']);$router->post('/{id}/results/publish',[ResultPublishingController::class,'publish'],['csrf','permission:exams.results.publish']);$router->post('/{id}/results/return',[ResultPublishingController::class,'returnForCorrection'],['csrf','permission:exams.results.approve']);
$router->get('/{id}/report-cards',[ReportCardController::class,'index'],['permission:exams.report_cards.view,exams.report_cards.manage']);$router->post('/{id}/report-cards/generate',[ReportCardController::class,'generate'],['csrf','permission:exams.report_cards.manage']);$router->post('/{id}/report-cards/publish',[ReportCardController::class,'publish'],['csrf','permission:exams.report_cards.publish']);
$router->get('/{id}/report-cards/preview',[ReportCardRenderController::class,'preview'],['permission:exams.report_cards.view,exams.report_cards.manage']);$router->get('/{id}/report-cards/pdf',[ReportCardRenderController::class,'pdf'],['permission:exams.report_cards.view,exams.report_cards.manage']);
$router->get('/report-cards/{card_id}/review',[ReportCardController::class,'review'],['permission:exams.report_cards.review']);$router->post('/report-cards/{card_id}/remarks',[ReportCardController::class,'saveRemarks'],['csrf','permission:exams.report_cards.review']);$router->post('/report-cards/{card_id}/approve',[ReportCardController::class,'approve'],['csrf','permission:exams.report_cards.review']);
$router->get('/{id}/analytics',[ExaminationAnalyticsController::class,'index'],['permission:exams.analytics.view']);$router->get('/{id}/analytics/export',[ExaminationAnalyticsController::class,'csv'],['permission:exams.analytics.export']);
$router->get('/{id}',[ExaminationController::class,'show']);$router->post('/{id}/status',[ExaminationController::class,'status'],['csrf','permission:exams.manage']);});
