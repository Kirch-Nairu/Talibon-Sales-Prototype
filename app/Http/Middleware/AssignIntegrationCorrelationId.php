<?php

namespace App\Http\Middleware;

use App\Domain\Integration\IntegrationRequestAttributes;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignIntegrationCorrelationId
{
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = (string) Str::uuid();
        $request->attributes->set(IntegrationRequestAttributes::CORRELATION_ID, $correlationId);

        $response = $next($request);
        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}
