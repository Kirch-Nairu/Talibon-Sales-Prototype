<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class RequireActiveAccount
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->is_active) {
            return $next($request);
        }

        $this->audit->record(
            $user,
            'auth.account.deactivated_forced_logout',
            'Authenticated session was terminated because the account is inactive.',
            'denied',
            User::class,
            $user->id,
        );

        $this->logout($request);

        return $this->inactiveResponse();
    }

    private function logout(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    private function inactiveResponse(): RedirectResponse
    {
        return redirect()->route('login')->withErrors([
            'email' => 'This session is no longer active. Please sign in again or contact an administrator.',
        ]);
    }
}
