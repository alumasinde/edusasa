<?php

declare(strict_types=1);

use App\Core\Router;
use Modules\Platform\Controllers\OnboardingController;
use Modules\Platform\Controllers\PlatformController;

/** @var Router $router */
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
});
