<?php

use App\Http\Middleware\AssignIntegrationCorrelationId;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\AuditIntegrationRequest;
use App\Http\Middleware\AuthenticateIntegrationClient;
use App\Http\Middleware\ExecuteIdempotentIntegrationRequest;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequireActiveAccount;
use App\Http\Middleware\RequireHrisAdmin;
use App\Http\Middleware\RequireIntegrationScope;
use App\Http\Middleware\RequireMfaAssurance;
use App\Http\Middleware\RequireMfaSubject;
use App\Http\Middleware\ThrottleIntegrationClient;
use App\Services\ApplicationErrorResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AssignRequestId::class);
        $middleware->web(append: [HandleInertiaRequests::class]);
        $middleware->alias([
            'active' => RequireActiveAccount::class,
            'mfa.assured' => RequireMfaAssurance::class,
            'mfa.subject' => RequireMfaSubject::class,
            'hris.admin' => RequireHrisAdmin::class,
            'integration.correlation' => AssignIntegrationCorrelationId::class,
            'integration.auth' => AuthenticateIntegrationClient::class,
            'integration.scope' => RequireIntegrationScope::class,
            'integration.rate' => ThrottleIntegrationClient::class,
            'integration.idempotency' => ExecuteIdempotentIntegrationRequest::class,
            'integration.audit' => AuditIntegrationRequest::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            return app(ApplicationErrorResponse::class)->respond($response, $exception, $request);
        });
    })->create();
