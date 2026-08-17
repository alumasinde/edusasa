<?php

declare(strict_types=1);

use App\Core\Config;

return [
    'name' => Config::env('APP_NAME', 'EduSasa'),
    'env' => Config::env('APP_ENV', 'production'),
    'debug' => Config::env('APP_DEBUG', false),
    'url' => Config::env('APP_URL', 'https://edusasa.co.ke'),
    'timezone' => Config::env('APP_TIMEZONE', 'Africa/Nairobi'),
    'modules' => [
        'Auth',
        'Dashboard',
        'Academic',
        'Students',
        'Teachers',
        'Users',
        'Attendance',
        'Fees',
        'Exams',
        'Reports',
        'Timetable',
        'ParentPortal',
        'StudentPortal',
        'Communication',
        'Platform',
        'Settings',
    ],
];
