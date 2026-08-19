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
        $debug = Config::env('APP_DEBUG', false) === true;
        set_exception_handler(function (\Throwable $e) use ($debug): void {
            Logger::error($e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $wantsJson = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');
            http_response_code(500);
            if ($wantsJson) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $debug ? $e->getMessage() : 'An unexpected error occurred.']);
                return;
            }
            echo $debug ? '<pre>' . htmlspecialchars((string) $e) . '</pre>' : 'Something went wrong. Please try again shortly.';
        });
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
            $response = $request->isApi()
                ? Response::json(['success' => false, 'errors' => $e->errors()], 422)
                : Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
            if (!$request->isApi()) {
                Session::flash('error', array_values($e->errors())[0][0] ?? 'Please check the form and try again.');
            }
        } catch (UnauthorizedException) {
            $response = Response::redirect('/login');
        } catch (ForbiddenException $e) {
            $response = $request->isApi()
                ? Response::json(['success' => false, 'message' => $e->getMessage()], 403)
                : Response::view('errors.forbidden', ['message' => $e->getMessage()], 403);
        } catch (NotFoundException $e) {
            $response = $request->isApi()
                ? Response::json(['success' => false, 'message' => $e->getMessage()], 404)
                : Response::html('Not found', 404);
        }
        $response->send();
    }
}
