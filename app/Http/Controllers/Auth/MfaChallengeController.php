<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\AuthenticationAssurance;
use App\Services\AuthenticationAttemptLimiter;
use App\Services\MfaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class MfaChallengeController extends Controller
{
    public function __construct(
        private readonly AuthenticationAssurance $assurance,
        private readonly AuthenticationAttemptLimiter $limiter,
        private readonly MfaService $mfa,
        private readonly AuditLogger $audit,
    ) {
    }

    public function create(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->challengeRedirect($request)) {
            return $redirect;
        }

        return Inertia::render('Auth/MfaChallenge');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($redirect = $this->challengeRedirect($request)) {
            return $redirect;
        }

        $user = $request->user();
        $data = $request->validate([
            'code' => ['nullable', 'digits:6', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'max:64', 'required_without:code'],
        ]);

        $this->rejectLimitedAttempt($request);
        $usedRecovery = filled($data['recovery_code'] ?? null);
        $proof = $this->mfa->verifyChallenge(
            $user,
            $data['code'] ?? null,
            $data['recovery_code'] ?? null,
        );

        if ($proof === null) {
            $this->rejectInvalidAttempt($request, $usedRecovery);
        }

        $this->limiter->clearMfa($request, $user);
        $request->session()->regenerate();
        $this->assurance->markSatisfied($request, $user, $proof);
        $this->recordSuccess($user, $usedRecovery);

        return redirect()->intended(route('dashboard'));
    }

    private function challengeRedirect(Request $request): ?RedirectResponse
    {
        $user = $request->user();
        $user->refresh();

        if (! $this->assurance->requiresMfa($user)) {
            return redirect()->route('dashboard');
        }

        if (! $this->assurance->enrollmentExists($user)) {
            return redirect()->route('mfa.enroll');
        }

        return $this->assurance->isSatisfied($request, $user)
            ? redirect()->intended(route('dashboard'))
            : null;
    }

    private function rejectLimitedAttempt(Request $request): void
    {
        $user = $request->user();

        if (! $this->limiter->mfaLimited($request, $user)) {
            return;
        }

        $this->audit->record($user, 'auth.mfa.challenge.rate_limited', 'MFA challenge was rate limited.', 'denied');
        throw ValidationException::withMessages(['code' => 'Verification could not be completed. Please wait and try again.']);
    }

    private function rejectInvalidAttempt(Request $request, bool $usedRecovery): never
    {
        $user = $request->user();
        $this->limiter->hitMfa($request, $user);
        $this->audit->record($user, 'auth.mfa.challenge.failed', 'MFA challenge failed.', 'denied');
        $field = $usedRecovery ? 'recovery_code' : 'code';

        throw ValidationException::withMessages([$field => 'The verification code could not be accepted.']);
    }

    private function recordSuccess(User $user, bool $usedRecovery): void
    {
        if ($usedRecovery) {
            $this->audit->record($user, 'auth.mfa.recovery_code.consumed', 'A one-time MFA recovery code was consumed.');
        }

        $this->audit->record($user, 'auth.mfa.challenge.succeeded', 'MFA challenge succeeded.');
        $this->audit->record($user, 'auth.assurance.satisfied', 'Required privileged MFA assurance was satisfied.');
    }
}
