<?php

namespace App\Services;

use App\Domain\Identity\ConfirmedMfaEnrollment;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use LogicException;
use PragmaRX\Google2FA\Google2FA;

final class MfaService
{
    public function __construct(private readonly Google2FA $totp)
    {
    }

    public function ensureEnrollmentSecret(User $user): string
    {
        return DB::transaction(function () use ($user): string {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            if (filled($locked->mfa_secret)) {
                return $locked->mfa_secret;
            }

            $secret = $this->totp->generateSecretKey();
            $locked->forceFill([
                'mfa_secret' => $secret,
                'mfa_confirmed_at' => null,
                'mfa_recovery_codes' => null,
                'mfa_recovery_codes_generated_at' => null,
                'mfa_version' => $this->nextVersion($locked),
            ])->save();

            return $secret;
        });
    }

    public function provisioningUri(User $user): string
    {
        if (! filled($user->mfa_secret)) {
            throw new LogicException('MFA enrollment secret is not initialized.');
        }

        return $this->totp->getQRCodeUrl(
            (string) config('identity.mfa.issuer'),
            $user->email,
            $user->mfa_secret,
        );
    }

    public function verifyTotp(User $user, string $code): bool
    {
        if (! filled($user->mfa_secret) || ! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        return (bool) $this->totp->verifyKey(
            $user->mfa_secret,
            $code,
            (int) config('identity.mfa.totp_window', 1),
        );
    }

    public function confirmEnrollment(User $user, string $code): ?ConfirmedMfaEnrollment
    {
        return DB::transaction(function () use ($user, $code): ?ConfirmedMfaEnrollment {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($locked->mfa_confirmed_at !== null || ! $this->verifyTotp($locked, $code)) {
                return null;
            }

            $codes = $this->newRecoveryCodes();
            $version = $this->nextVersion($locked);
            $this->persistRecoveryCodes($locked, $codes, now());
            $locked->forceFill([
                'mfa_confirmed_at' => now(),
                'mfa_version' => $version,
            ])->save();

            return new ConfirmedMfaEnrollment((int) $locked->id, $version, $codes);
        });
    }

    public function verifyChallenge(User $user, ?string $totpCode, ?string $recoveryCode): ?ConfirmedMfaEnrollment
    {
        return DB::transaction(function () use ($user, $totpCode, $recoveryCode): ?ConfirmedMfaEnrollment {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($locked->mfa_confirmed_at === null || ! filled($locked->mfa_secret)) {
                return null;
            }

            $accepted = filled($recoveryCode)
                ? $this->consumeRecoveryCodeFromLockedUser($locked, (string) $recoveryCode)
                : $this->verifyTotp($locked, (string) $totpCode);

            return $accepted
                ? new ConfirmedMfaEnrollment((int) $locked->id, (int) $locked->mfa_version)
                : null;
        });
    }

    /**
     * @return array<int, string>
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        return DB::transaction(function () use ($user): array {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($locked->mfa_confirmed_at === null) {
                throw new LogicException('MFA must be confirmed before recovery codes can be regenerated.');
            }

            $codes = $this->newRecoveryCodes();
            $this->persistRecoveryCodes($locked, $codes, now());

            return $codes;
        });
    }

    public function resetEnrollment(User $user): string
    {
        return DB::transaction(function () use ($user): string {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $secret = $this->totp->generateSecretKey();
            $locked->forceFill([
                'mfa_secret' => $secret,
                'mfa_confirmed_at' => null,
                'mfa_recovery_codes' => null,
                'mfa_recovery_codes_generated_at' => null,
                'mfa_version' => $this->nextVersion($locked),
            ])->save();

            return $secret;
        });
    }

    public function disable(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $locked->forceFill([
                'mfa_secret' => null,
                'mfa_confirmed_at' => null,
                'mfa_recovery_codes' => null,
                'mfa_recovery_codes_generated_at' => null,
                'mfa_version' => $this->nextVersion($locked),
            ])->save();
        });
    }

    /**
     * @return array<int, string>
     */
    private function newRecoveryCodes(): array
    {
        $count = (int) config('identity.mfa.recovery_code_count', 10);

        return collect(range(1, $count))
            ->map(fn (): string => Str::upper(Str::random(6)).'-'.Str::upper(Str::random(6)))
            ->all();
    }

    private function consumeRecoveryCodeFromLockedUser(User $user, string $code): bool
    {
        $hashes = array_values($user->mfa_recovery_codes ?? []);
        $needle = $this->normalizeRecoveryCode($code);

        foreach ($hashes as $index => $hash) {
            if (! Hash::check($needle, $hash)) {
                continue;
            }

            unset($hashes[$index]);
            $user->forceFill(['mfa_recovery_codes' => array_values($hashes)])->save();

            return true;
        }

        return false;
    }

    /**
     * @param  array<int, string>  $codes
     */
    private function persistRecoveryCodes(User $user, array $codes, DateTimeInterface $generatedAt): void
    {
        $hashes = array_map(
            fn (string $code): string => Hash::make($this->normalizeRecoveryCode($code)),
            $codes,
        );

        $user->forceFill([
            'mfa_recovery_codes' => $hashes,
            'mfa_recovery_codes_generated_at' => $generatedAt,
        ])->save();
    }

    private function normalizeRecoveryCode(string $code): string
    {
        return Str::upper(str_replace(' ', '', trim($code)));
    }

    private function nextVersion(User $user): int
    {
        return ((int) $user->mfa_version) + 1;
    }
}
