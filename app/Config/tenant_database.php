<?php

declare(strict_types=1);

use App\Core\Config;

return [
    'host' => Config::env('TENANT_DB_HOST', Config::env('DB_HOST', '127.0.0.1')),
    'port' => (int) Config::env('TENANT_DB_PORT', Config::env('DB_PORT', '3306')),
    'charset' => Config::env('TENANT_DB_CHARSET', 'utf8mb4'),
    'username' => Config::env('TENANT_DB_USERNAME', ''),
    'password' => Config::env('TENANT_DB_PASSWORD', ''),
];
