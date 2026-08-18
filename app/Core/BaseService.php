<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Shared base for application services.
 *
 * Services use the protected database accessor so transactions and queries
 * consistently go through the application's Database abstraction.
 */
abstract class BaseService
{
    protected function db(): Database
    {
        return Database::getInstance();
    }
}
