<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\CsrfMiddleware;
use App\Core\Middleware\ModuleMiddleware;
use App\Core\Middleware\PermissionMiddleware;
use App\Core\Middleware\PlatformAuthMiddleware;
use App\Core\Middleware\PlatformHostMiddleware;
use App\Core\Middleware\RoleMiddleware;
use App\Core\Middleware\TenantResolver;
use Modules\Platform\Services\SchoolEntitlementService;
use PDOException;

class Application
{
    public readonly Container $container;
    public readonly Router $router;

    public function __construct(private readonly string $basePath)
    {
        Config::boot($basePath . '/app/Config');
        $this->container = Container::getInstance();
        $this->registerCoreBindings();
        $this->router = new Router();
        $this->registerMiddlewareAliases();
        Session::start();
        $this->registerErrorHandling();
    }

    private function registerCoreBindings(): void
    {
        $this->container->singleton(Database::class, fn () => Database::getInstance());
        $this->container->singleton(Auth::class, fn (Container $c) => new Auth($c->make(Database::class)));
        $this->container->singleton(Authorization::class, fn (Container $c) => new Authorization($c->make(Auth::class), $c->make(Database::class)));
        $this->container->singleton(
            TenantResolver::class,
            fn (Container $c) => new TenantResolver(
                $c->make(Database::class),
                $c->make(SchoolEntitlementService::class)
            )
        );
        Notifications::register('log', new LogChannel());
        Notifications::register('email', new EmailChannel());
    }

    private function registerMiddlewareAliases(): void
    {
        $this->router->registerMiddleware('tenant', TenantResolver::class);
        $this->router->registerMiddleware('auth', AuthMiddleware::class);
        $this->router->registerMiddleware('role', RoleMiddleware::class);
        $this->router->registerMiddleware('permission', PermissionMiddleware::class);
        $this->router->registerMiddleware('module', ModuleMiddleware::class);
        $this->router->registerMiddleware('platform_host', PlatformHostMiddleware::class);
        $this->router->registerMiddleware('platform_auth', PlatformAuthMiddleware::class);
        $this->router->registerMiddleware('csrf', CsrfMiddleware::class);
    }

    private function registerErrorHandling(): void
    {
        set_exception_handler(function (\Throwable $e): void {
            $reference = $this->newErrorReference();
            $this->logException($e, $reference);
            $this->sendSafeError($e, new Request(), $reference);
        });

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            $exception = new \ErrorException($message, 0, $severity, $file, $line);
            $this->logException($exception, $this->newErrorReference());
            return false;
        });
    }

    private function newErrorReference(): string
    {
        try {
            return strtoupper(bin2hex(random_bytes(4)));
        } catch (\Throwable) {
            return strtoupper(substr(hash('sha256', microtime(true) . uniqid('', true)), 0, 8));
        }
    }

    private function logException(\Throwable $e, ?string $reference = null): void
    {
        Logger::error($e->getMessage(), [
            'reference' => $reference,
            'exception' => $e::class,
            'trace' => $e->getTraceAsString(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '/',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
        ]);
    }

    private function sendSafeError(\Throwable $e, Request $request, ?string $reference = null): void
    {
        $status = $this->statusForException($e);
        $message = $this->userMessageForException($e, $status);
        $reference ??= $this->newErrorReference();

        if ($request->isApi()) {
            Response::json([
                'success' => false,
                'message' => $message,
                'reference' => $reference,
            ], $status)->send();
            return;
        }

        $view = match ($status) {
            401 => 'errors.unauthorized',
            403 => 'errors.forbidden',
            404 => 'errors.404',
            503 => 'errors.503',
            default => 'errors.500',
        };

        try {
            Response::view($view, [
                'message' => $message,
                'reference' => $reference,
                'host' => $_SERVER['HTTP_HOST'] ?? '',
            ], $status)->send();
        } catch (\Throwable $renderError) {
            $this->logException($renderError, $reference);
            Response::html(
                '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>EduSasa</title></head><body style="font-family:system-ui;text-align:center;padding:60px;color:#172033"><h1>' .
                htmlspecialchars($status === 404 ? 'Page not found' : 'Something went wrong', ENT_QUOTES, 'UTF-8') .
                '</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p><p>Reference: <strong>' .
                htmlspecialchars($reference, ENT_QUOTES, 'UTF-8') . '</strong></p></body></html>',
                $status
            )->send();
        }
    }

    private function statusForException(\Throwable $e): int
    {
        return match (true) {
            $e instanceof UnauthorizedException => 401,
            $e instanceof ForbiddenException => 403,
            $e instanceof NotFoundException,
            $e instanceof TenantNotResolvedException => 404,
            $e instanceof PDOException => 503,
            default => 500,
        };
    }

    private function userMessageForException(\Throwable $e, int $status): string
    {
        return match ($status) {
            401 => 'Please sign in to continue.',
            403 => 'You do not have permission to access this page.',
            404 => 'The page or school you requested could not be found.',
            503 => 'EduSasa is temporarily unable to complete that request. Please try again shortly.',
            default => 'Something went wrong while processing your request. Please try again shortly.',
        };
    }

    public function loadModules(array $modules): void
    {
        ModuleLoader::load($this->router, $modules, $this->basePath . '/modules');
    }

    public function run(): void
    {
        $request = new Request();
        try {
            $response = $this->router->dispatch($request);
        } catch (ValidationException $e) {
            if ($request->isApi()) {
                $response = Response::json(['success' => false, 'errors' => $e->errors()], 422);
            } else {
                $firstError = array_values($e->errors())[0][0] ?? 'Please check the form and try again.';
                Session::flash('error', $firstError);
                $response = Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
            }
        } catch (\Throwable $e) {
            $reference = $this->newErrorReference();
            $this->logException($e, $reference);
            $this->sendSafeError($e, $request, $reference);
            return;
        }
        $response->send();
    }
}
