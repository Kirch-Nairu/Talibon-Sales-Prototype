<?php

namespace App\Services;

use App\Domain\Integration\IntegrationAuthenticationException;
use App\Domain\Integration\IntegrationAuthenticationFailure;
use App\Domain\Integration\IntegrationClientContext;
use App\Domain\Integration\IntegrationScope;
use App\Domain\Integration\IssuedIntegrationCredential;
use App\Models\IntegrationClient;
use App\Models\IntegrationClientCredential;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class IntegrationCredentialService
{
    public function issue(
        IntegrationClient $client,
        array $scopes,
        ?CarbonInterface $expiresAt = null,
        ?User $issuedBy = null,
    ): IssuedIntegrationCredential {
        if (! $client->is_active) {
            throw new InvalidArgumentException('Credentials cannot be issued to an inactive integration client.');
        }

        if ($expiresAt !== null && $expiresAt->isPast()) {
            throw new InvalidArgumentException('Credential expiration must be in the future.');
        }

        $normalizedScopes = $this->normalizeScopes($scopes);
        $publicId = (string) Str::uuid();
        $secret = $this->generateSecret();

        $this->persistCredential(
            $client,
            $publicId,
            $secret,
            $normalizedScopes,
            $expiresAt,
            $issuedBy,
        );

        return new IssuedIntegrationCredential(
            $publicId,
            $publicId.'.'.$secret,
            $normalizedScopes,
            $expiresAt,
        );
    }

    public function rotate(
        IntegrationClientCredential $current,
        ?CarbonInterface $expiresAt = null,
        ?User $issuedBy = null,
    ): IssuedIntegrationCredential {
        $current->loadMissing('client');

        return $this->issue(
            $current->client,
            $current->scopes ?? [],
            $expiresAt,
            $issuedBy,
        );
    }

    public function revoke(IntegrationClientCredential $credential): IntegrationClientCredential
    {
        return DB::transaction(function () use ($credential): IntegrationClientCredential {
            $locked = IntegrationClientCredential::query()
                ->lockForUpdate()
                ->findOrFail($credential->id);

            if ($locked->revoked_at === null) {
                $locked->forceFill(['revoked_at' => now()])->save();
            }

            return $locked;
        });
    }

    public function authenticate(string $token): IntegrationClientContext
    {
        [$publicId, $secret] = $this->parseToken($token);
        $credential = IntegrationClientCredential::query()
            ->with('client')
            ->where('public_id', $publicId)
            ->first();

        if ($credential === null) {
            throw new IntegrationAuthenticationException(IntegrationAuthenticationFailure::Unknown);
        }

        $this->assertCredentialState($credential, $secret);
        $credential->newQuery()->whereKey($credential->id)->update(['last_used_at' => now()]);

        return new IntegrationClientContext(
            $credential->client,
            $credential,
            $credential->scopes ?? [],
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseToken(string $token): array
    {
        $parts = explode('.', trim($token), 2);

        if (count($parts) !== 2 || ! Str::isUuid($parts[0]) || strlen($parts[1]) < 32) {
            throw new IntegrationAuthenticationException(IntegrationAuthenticationFailure::Malformed);
        }

        return [$parts[0], $parts[1]];
    }

    private function assertCredentialState(IntegrationClientCredential $credential, string $secret): void
    {
        $candidateHash = hash('sha256', $secret);

        if (! hash_equals($credential->secret_hash, $candidateHash)) {
            throw new IntegrationAuthenticationException(IntegrationAuthenticationFailure::WrongSecret, $credential->client);
        }

        if ($credential->revoked_at !== null) {
            throw new IntegrationAuthenticationException(IntegrationAuthenticationFailure::Revoked, $credential->client);
        }

        if ($credential->expires_at !== null && $credential->expires_at->isPast()) {
            throw new IntegrationAuthenticationException(IntegrationAuthenticationFailure::Expired, $credential->client);
        }

        if (! $credential->client->is_active) {
            throw new IntegrationAuthenticationException(IntegrationAuthenticationFailure::InactiveClient, $credential->client);
        }
    }

    /**
     * @param  array<int, IntegrationScope|string>  $scopes
     * @return array<int, string>
     */
    private function normalizeScopes(array $scopes): array
    {
        $normalized = [];

        foreach ($scopes as $scope) {
            $value = $scope instanceof IntegrationScope ? $scope->value : trim((string) $scope);
            if (IntegrationScope::tryFrom($value) === null) {
                throw new InvalidArgumentException('Unknown integration scope: '.$value);
            }
            $normalized[] = $value;
        }

        sort($normalized);

        return array_values(array_unique($normalized));
    }

    /**
     * @param  array<int, string>  $scopes
     */
    private function persistCredential(
        IntegrationClient $client,
        string $publicId,
        string $secret,
        array $scopes,
        ?CarbonInterface $expiresAt,
        ?User $issuedBy,
    ): void {
        DB::transaction(function () use ($client, $publicId, $secret, $scopes, $expiresAt, $issuedBy): void {
            IntegrationClientCredential::query()->create([
                'public_id' => $publicId,
                'integration_client_id' => $client->id,
                'secret_hash' => hash('sha256', $secret),
                'scopes' => $scopes,
                'issued_at' => now(),
                'expires_at' => $expiresAt?->copy()->setTimezone(config('app.timezone')),
                'issued_by_user_id' => $issuedBy?->id,
            ]);
        });
    }

    private function generateSecret(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
