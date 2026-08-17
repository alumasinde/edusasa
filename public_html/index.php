<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Application;

$app = new Application(dirname(__DIR__));
$config = require dirname(__DIR__) . '/app/Config/app.php';
$app->loadModules($config['modules']);
$app->run();
