<?php

namespace App\Http\Middleware;

use App\Domain\Integration\IntegrationClientContext;
use App\Domain\Integration\IntegrationErrorCode;
use App\Domain\Integration\IntegrationRequestAttributes;
use App\Domain\Integration\IntegrationScope;
use App\Services\AuditLogger;
use App\Services\IntegrationErrorResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireIntegrationScope
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly IntegrationErrorResponse $errors,
    ) {
    }

    public function handle(Request $request, Closure $next, string $requiredScope): Response
    {
        $context = $request->attributes->get(IntegrationRequestAttributes::CLIENT_CONTEXT);
        $scope = IntegrationScope::tryFrom($requiredScope);

        if (! $context instanceof IntegrationClientContext || $scope === null || ! $context->hasScope($scope)) {
            $this->audit->recordIntegration(
                $context instanceof IntegrationClientContext ? $context->client : null,
                'integration.scope.denied',
                'Integration scope authorization denied for '.$requiredScope.'.',
                'denied',
            );

            return $this->errors->make($request, IntegrationErrorCode::ScopeDenied, 403);
        }

        return $next($request);
    }
}
