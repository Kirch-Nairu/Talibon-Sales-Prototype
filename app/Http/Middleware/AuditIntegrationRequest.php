<?php

namespace App\Http\Middleware;

use App\Domain\Integration\IntegrationClientContext;
use App\Domain\Integration\IntegrationRequestAttributes;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditIntegrationRequest
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $context = $request->attributes->get(IntegrationRequestAttributes::CLIENT_CONTEXT);

        if ($context instanceof IntegrationClientContext && $response->getStatusCode() < 400) {
            $this->audit->recordIntegration(
                $context->client,
                'integration.request.succeeded',
                'Authorized integration request completed: '.$request->method().' '.$request->path().'.',
            );
        }

        return $response;
    }
}
