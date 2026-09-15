<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use App\Services\AuthenticationAssurance;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireMfaAssurance
{
    public function __construct(
        private readonly AuthenticationAssurance $assurance,
        private readonly AuditLogger $audit,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if ($this->assurance->isSatisfied($request, $user)) {
            return $next($request);
        }

        if ($request->isMethodSafe()) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        $route = $this->assurance->requiredRoute($user);
        $this->audit->record(
            $user,
            'auth.assurance.denied',
            'Privileged application access was denied pending required MFA assurance.',
            'denied',
            'AuthenticationAssurance',
        );

        return redirect()->route($route);
    }
}
