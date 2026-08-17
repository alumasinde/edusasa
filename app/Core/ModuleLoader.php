<?php

declare(strict_types=1);
namespace App\Core;
final class ModuleLoader {
 public static function load(Router $router,array $modules,string $modulesPath):void{foreach($modules as $module){foreach(['web','api'] as $type){$file="{$modulesPath}/{$module}/routes/{$type}.php";if(is_file($file))require $file;}}}
}
