<?php

namespace App\Http\Middleware;

use App\Services\AuthenticationAssurance;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireMfaSubject
{
    public function __construct(private readonly AuthenticationAssurance $assurance)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $this->assurance->requiresMfa($user)) {
            return $next($request);
        }

        return redirect()->route('dashboard');
    }
}
