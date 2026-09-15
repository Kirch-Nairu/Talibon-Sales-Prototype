<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\AuthenticationAssurance;
use App\Services\AuthenticationAttemptLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly AuthenticationAssurance $assurance,
        private readonly AuthenticationAttemptLimiter $limiter,
        private readonly AuditLogger $audit,
    ) {
    }

    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->rejectLimitedLogin($request);

        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            $this->recordFailedLogin($request);
        }

        $this->limiter->clearLogin($request);
        $request->session()->regenerate();
        $this->assurance->clear($request);
        $user = $request->user();

        if (! $this->assurance->requiresMfa($user)) {
            return redirect()->intended(route('dashboard'));
        }

        $this->audit->record(
            $user,
            'auth.password.succeeded',
            'Privileged password authentication succeeded; MFA assurance is still required.',
        );

        return redirect()->route($this->assurance->requiredRoute($user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function rejectLimitedLogin(Request $request): void
    {
        if (! $this->limiter->loginLimited($request)) {
            return;
        }

        $this->audit->recordAnonymous(
            'auth.login.rate_limited',
            'Password authentication was rate limited.',
            'denied',
        );

        throw ValidationException::withMessages([
            'email' => 'Sign in could not be completed. Please wait and try again.',
        ]);
    }

    private function recordFailedLogin(Request $request): never
    {
        $this->limiter->hitLogin($request);
        $this->audit->recordAnonymous(
            'auth.login.failed',
            'Password authentication failed.',
            'denied',
        );

        throw ValidationException::withMessages([
            'email' => 'The provided credentials are invalid or the account is inactive.',
        ]);
    }
}
