<?php

namespace App\Services;

use App\Domain\Identity\ConfirmedMfaEnrollment;
use App\Domain\Identity\PrivilegedRolePolicy;
use App\Models\User;
use Illuminate\Http\Request;
use LogicException;

final class AuthenticationAssurance
{
    public const SESSION_USER_KEY = 'auth.assurance.mfa_user_id';

    public const SESSION_VERIFIED_AT_KEY = 'auth.assurance.mfa_verified_at';

    public const SESSION_VERSION_KEY = 'auth.assurance.mfa_version';

    public function __construct(private readonly PrivilegedRolePolicy $roles)
    {
    }

    public function requiresMfa(User $user): bool
    {
        return $this->roles->requiresMfa($user);
    }

    public function enrollmentExists(User $user): bool
    {
        return filled($user->mfa_secret) && $user->mfa_confirmed_at !== null;
    }

    public function isSatisfied(Request $request, User $user): bool
    {
        $current = $user->fresh();

        if (! $current) {
            return false;
        }

        if (! $this->requiresMfa($current)) {
            return true;
        }

        if (! $this->enrollmentExists($current)) {
            return false;
        }

        return (int) $request->session()->get(self::SESSION_USER_KEY) === (int) $current->id
            && (int) $request->session()->get(self::SESSION_VERSION_KEY, -1) === (int) $current->mfa_version
            && $request->session()->has(self::SESSION_VERIFIED_AT_KEY);
    }

    public function markSatisfied(Request $request, User $user, ConfirmedMfaEnrollment $proof): void
    {
        if ((int) $user->id !== $proof->userId) {
            throw new LogicException('MFA assurance proof does not belong to the authenticated user.');
        }

        $request->session()->put([
            self::SESSION_USER_KEY => $proof->userId,
            self::SESSION_VERSION_KEY => $proof->mfaVersion,
            self::SESSION_VERIFIED_AT_KEY => now()->getTimestamp(),
        ]);
    }

    public function clear(Request $request): void
    {
        $request->session()->forget([
            self::SESSION_USER_KEY,
            self::SESSION_VERSION_KEY,
            self::SESSION_VERIFIED_AT_KEY,
        ]);
    }

    public function requiredRoute(User $user): string
    {
        $current = $user->fresh();

        return $current && $this->enrollmentExists($current) ? 'mfa.challenge' : 'mfa.enroll';
    }
}
