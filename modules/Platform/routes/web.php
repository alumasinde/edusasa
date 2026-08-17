<?php

declare(strict_types=1);

use App\Core\Router;
use Modules\Platform\Controllers\OnboardingController;
use Modules\Platform\Controllers\PlatformAccessController;
use Modules\Platform\Controllers\PlatformController;
use Modules\Platform\Controllers\SchoolAdminActivationController;
use Modules\Platform\Controllers\SubscriptionController;

/** @var Router $router */
$router->get('/school-admin/setup', [SchoolAdminActivationController::class, 'create']);
$router->post('/school-admin/setup', [SchoolAdminActivationController::class, 'store']);

$router->group([
    'prefix'=>'/platform',
    'middleware'=>['platform_host','platform_auth'],
],function(Router $router):void{
    $router->get('/',[PlatformController::class,'dashboard']);
    $router->get('/schools',[PlatformController::class,'schoolPage']);
    $router->get('/schools/new',[OnboardingController::class,'create']);
    $router->post('/schools',[OnboardingController::class,'store']);

    $router->get('/entitlements',[PlatformController::class,'entitlementPage']);
    $router->get('/plans/{id}',[PlatformController::class,'planPage']);
    $router->post('/plans',[PlatformController::class,'savePlan']);
    $router->post('/plans/{id}',[PlatformController::class,'savePlan']);
    $router->post('/plans/{plan}/features/{feature}',[PlatformController::class,'setPlanFeature']);
    $router->get('/schools/{id}/entitlements',[PlatformController::class,'schoolEntitlementsPage']);
    $router->post('/schools/{id}/entitlements',[PlatformController::class,'saveSchoolOverride']);
    $router->delete('/schools/{id}/entitlements',[PlatformController::class,'removeSchoolOverride']);

    $router->get('/subscriptions',[SubscriptionController::class,'index']);
    $router->post('/subscriptions/{schoolId}/plan',[SubscriptionController::class,'changePlan']);
    $router->post('/subscriptions/{id}/status',[SubscriptionController::class,'status']);

    $router->get('/access',[PlatformAccessController::class,'index']);
    $router->post('/access/roles',[PlatformAccessController::class,'saveRole']);
    $router->post('/access/roles/{id}',[PlatformAccessController::class,'saveRole']);
    $router->post('/access/users/{id}/roles',[PlatformAccessController::class,'assignRoles']);
});
