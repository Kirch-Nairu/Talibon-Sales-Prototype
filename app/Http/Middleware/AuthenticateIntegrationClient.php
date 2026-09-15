<?php

namespace App\Http\Middleware;

use App\Domain\Integration\IntegrationAuthenticationException;
use App\Domain\Integration\IntegrationErrorCode;
use App\Domain\Integration\IntegrationRequestAttributes;
use App\Services\AuditLogger;
use App\Services\IntegrationCredentialService;
use App\Services\IntegrationErrorResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateIntegrationClient
{
    public function __construct(
        private readonly IntegrationCredentialService $credentials,
        private readonly AuditLogger $audit,
        private readonly IntegrationErrorResponse $errors,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $context = $this->credentials->authenticate((string) $request->bearerToken());
        } catch (IntegrationAuthenticationException $exception) {
            $this->audit->recordIntegration(
                $exception->client,
                'integration.authentication.failed',
                'Integration authentication denied: '.$exception->reason->value.'.',
                'denied',
            );

            return $this->errors->make($request, IntegrationErrorCode::AuthenticationFailed, 401);
        }

        $request->attributes->set(IntegrationRequestAttributes::CLIENT_CONTEXT, $context);
        $this->audit->recordIntegration(
            $context->client,
            'integration.authentication.succeeded',
            'Integration client credential authenticated.',
        );

        return $next($request);
    }
}
