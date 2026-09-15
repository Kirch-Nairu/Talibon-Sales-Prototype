<?php

namespace App\Http\Middleware;

use App\Domain\Integration\IntegrationClientContext;
use App\Domain\Integration\IntegrationErrorCode;
use App\Domain\Integration\IntegrationRequestAttributes;
use App\Services\AuditLogger;
use App\Services\IntegrationErrorResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleIntegrationClient
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly IntegrationErrorResponse $errors,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $context = $request->attributes->get(IntegrationRequestAttributes::CLIENT_CONTEXT);
        if (! $context instanceof IntegrationClientContext) {
            return $this->errors->make($request, IntegrationErrorCode::AuthenticationFailed, 401);
        }

        $key = $this->key($context);
        $limit = max(1, (int) $context->client->requests_per_minute);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $retryAfter = max(1, RateLimiter::availableIn($key));
            $this->audit->recordIntegration(
                $context->client,
                'integration.rate_limit.denied',
                'Integration client request rate exceeded configured limit.',
                'denied',
            );

            return $this->errors->make(
                $request,
                IntegrationErrorCode::RateLimited,
                429,
                ['Retry-After' => $retryAfter],
            );
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }

    private function key(IntegrationClientContext $context): string
    {
        return 'integration:client-rate:'.$context->client->public_id;
    }
}
