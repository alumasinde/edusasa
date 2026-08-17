<?php

declare(strict_types=1);
namespace App\Core;
class UnauthorizedException extends \RuntimeException { }
class ForbiddenException extends \RuntimeException { }
class NotFoundException extends \RuntimeException { }
class TenantNotResolvedException extends \RuntimeException { }
class ValidationException extends \RuntimeException { public function __construct(private readonly array $validationErrors){parent::__construct('Validation failed.');} public function errors():array{return $this->validationErrors;} }
