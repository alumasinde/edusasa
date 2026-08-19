<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\DatabaseConnectionConfig;

return new DatabaseConnectionConfig(
    host: Config::env('PLATFORM_DB_HOST', Config::env('DB_HOST', '127.0.0.1')),
    port: (int) Config::env('PLATFORM_DB_PORT', Config::env('DB_PORT', '3306')),
    database: Config::env('PLATFORM_DB_DATABASE', 'edusasa_platform'),
    username: Config::env('PLATFORM_DB_USERNAME', Config::env('DB_USERNAME', 'root')),
    password: Config::env('PLATFORM_DB_PASSWORD', Config::env('DB_PASSWORD', '')),
    charset: Config::env('PLATFORM_DB_CHARSET', 'utf8mb4'),
);
