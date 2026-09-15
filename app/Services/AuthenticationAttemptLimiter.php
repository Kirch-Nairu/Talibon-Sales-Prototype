<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class AuthenticationAttemptLimiter
{
    public function loginLimited(Request $request): bool
    {
        return RateLimiter::tooManyAttempts($this->loginKey($request), $this->limit('login'));
    }

    public function hitLogin(Request $request): void
    {
        RateLimiter::hit($this->loginKey($request), $this->decay('login'));
    }

    public function clearLogin(Request $request): void
    {
        RateLimiter::clear($this->loginKey($request));
    }

    public function mfaLimited(Request $request, User $user): bool
    {
        return RateLimiter::tooManyAttempts($this->mfaKey($request, $user), $this->limit('mfa'));
    }

    public function hitMfa(Request $request, User $user): void
    {
        RateLimiter::hit($this->mfaKey($request, $user), $this->decay('mfa'));
    }

    public function clearMfa(Request $request, User $user): void
    {
        RateLimiter::clear($this->mfaKey($request, $user));
    }

    private function loginKey(Request $request): string
    {
        $identity = Str::lower((string) $request->input('email'));

        return 'auth:login:'.hash('sha256', $identity.'|'.$request->ip());
    }

    private function mfaKey(Request $request, User $user): string
    {
        return 'auth:mfa:'.$user->id.':'.hash('sha256', (string) $request->ip());
    }

    private function limit(string $bucket): int
    {
        return (int) config("identity.rate_limits.{$bucket}.attempts", 5);
    }

    private function decay(string $bucket): int
    {
        return (int) config("identity.rate_limits.{$bucket}.decay_seconds", 60);
    }
}
