<?php

namespace Tests\Feature;

use App\Domain\Integration\IntegrationErrorCode;
use App\Domain\Integration\IntegrationScope;
use App\Models\AuditLog;
use App\Models\IntegrationClient;
use App\Models\IntegrationClientCredential;
use App\Models\User;
use App\Services\IntegrationClientService;
use App\Services\IntegrationCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class IntegrationFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_valid_client_authenticates_and_self_endpoint_exposes_only_safe_identity(): void
    {
        [$client, $credential, $token] = $this->credential([IntegrationScope::SelfRead->value]);

        $response = $this->withToken($token)->getJson('/api/v1/integration/self');

        $response->assertOk()
            ->assertJsonPath('client.public_id', $client->public_id)
            ->assertJsonPath('client.name', $client->name)
            ->assertJsonPath('credential.public_id', $credential->public_id)
            ->assertJsonPath('credential.scopes.0', IntegrationScope::SelfRead->value)
            ->assertJsonMissingPath('client.id')
            ->assertJsonMissingPath('credential.id')
            ->assertJsonMissingPath('credential.secret_hash');
        $this->assertNotNull($response->headers->get('X-Correlation-ID'));
        $this->assertGuest();
        $this->assertFalse(Auth::check());
    }

    public function test_wrong_secret_returns_generic_401(): void
    {
        [, $credential] = $this->credential([IntegrationScope::SelfRead->value]);
        $token = $credential->public_id.'.'.str_repeat('x', 43);

        $this->assertAuthenticationFailure($token);
    }

    public function test_unknown_credential_returns_generic_401(): void
    {
        $token = (string) Str::uuid().'.'.str_repeat('x', 43);

        $this->assertAuthenticationFailure($token);
    }

    public function test_malformed_credential_returns_generic_401(): void
    {
        $this->assertAuthenticationFailure('malformed-token');
    }

    public function test_revoked_credential_returns_generic_401(): void
    {
        [, $credential, $token] = $this->credential([IntegrationScope::SelfRead->value]);
        app(IntegrationCredentialService::class)->revoke($credential);

        $this->assertAuthenticationFailure($token);
    }

    public function test_expired_credential_returns_generic_401(): void
    {
        [, $credential, $token] = $this->credential([IntegrationScope::SelfRead->value]);
        $credential->forceFill(['expires_at' => now()->subMinute()->utc()])->save();

        $this->assertAuthenticationFailure($token);
    }

    public function test_inactive_client_returns_generic_401(): void
    {
        [$client, , $token] = $this->credential([IntegrationScope::SelfRead->value]);
        $client->forceFill(['is_active' => false])->save();

        $this->assertAuthenticationFailure($token);
    }

    public function test_plaintext_secret_is_never_persisted_or_serialized(): void
    {
        [, $credential, $token] = $this->credential([IntegrationScope::SelfRead->value]);
        [, $secret] = explode('.', $token, 2);
        $raw = DB::table('integration_client_credentials')->where('id', $credential->id)->first();

        $this->assertSame(hash('sha256', $secret), $raw->secret_hash);
        $this->assertNotSame($secret, $raw->secret_hash);
        $this->assertNotSame($token, $raw->secret_hash);
        $this->assertStringNotContainsString($secret, json_encode($raw, JSON_THROW_ON_ERROR));
        $this->assertArrayNotHasKey('secret_hash', $credential->fresh()->toArray());
        $this->assertStringNotContainsString($secret, json_encode($credential->fresh()->toArray(), JSON_THROW_ON_ERROR));
    }

    public function test_missing_scope_returns_403_without_hiding_authorization_failure(): void
    {
        [$client, , $token] = $this->credential([]);

        $response = $this->withToken($token)->getJson('/api/v1/integration/self');

        $response->assertForbidden()
            ->assertJsonPath('error.code', IntegrationErrorCode::ScopeDenied->value);
        $this->assertNotNull($response->headers->get('X-Correlation-ID'));
        $this->assertDatabaseHas('audit_logs', [
            'integration_client_id' => $client->id,
            'action' => 'integration.scope.denied',
            'outcome' => 'denied',
        ]);
    }

    public function test_unknown_scope_cannot_be_issued(): void
    {
        $client = app(IntegrationClientService::class)->create('Unknown Scope Test');

        $this->expectException(InvalidArgumentException::class);
        app(IntegrationCredentialService::class)->issue($client, ['unknown.scope']);
    }

    public function test_rate_limit_is_enforced_by_authenticated_client_identity(): void
    {
        [$client, , $token] = $this->credential([IntegrationScope::SelfRead->value], 1);

        $this->withToken($token)->getJson('/api/v1/integration/self')->assertOk();
        $response = $this->withToken($token)->getJson('/api/v1/integration/self');

        $response->assertStatus(429)
            ->assertJsonPath('error.code', IntegrationErrorCode::RateLimited->value);
        $this->assertNotNull($response->headers->get('Retry-After'));
        $this->assertNotNull($response->headers->get('X-Correlation-ID'));
        $this->assertDatabaseHas('audit_logs', [
            'integration_client_id' => $client->id,
            'action' => 'integration.rate_limit.denied',
            'outcome' => 'denied',
        ]);
    }

    public function test_request_correlation_is_shared_across_audit_records_and_separate_per_request(): void
    {
        [$client, , $token] = $this->credential([IntegrationScope::SelfRead->value], 60);

        $first = $this->withToken($token)->getJson('/api/v1/integration/self');
        $second = $this->withToken($token)->getJson('/api/v1/integration/self');
        $firstId = (string) $first->headers->get('X-Correlation-ID');
        $secondId = (string) $second->headers->get('X-Correlation-ID');

        $this->assertNotSame($firstId, $secondId);
        $this->assertSame($firstId, $first->json('correlation_id'));
        $logs = AuditLog::query()->where('correlation_id', $firstId)->get();
        $this->assertGreaterThanOrEqual(2, $logs->count());
        $this->assertTrue($logs->every(fn (AuditLog $log): bool => (int) $log->integration_client_id === (int) $client->id));
        $this->assertTrue($logs->contains('action', 'integration.authentication.succeeded'));
        $this->assertTrue($logs->contains('action', 'integration.request.succeeded'));
    }

    public function test_caller_supplied_correlation_id_is_not_trusted_as_authoritative(): void
    {
        [, , $token] = $this->credential([IntegrationScope::SelfRead->value]);
        $external = (string) Str::uuid();

        $response = $this->withHeader('X-Correlation-ID', $external)
            ->withToken($token)
            ->getJson('/api/v1/integration/self');

        $this->assertNotSame($external, $response->headers->get('X-Correlation-ID'));
        $this->assertNotSame($external, $response->json('correlation_id'));
    }

    public function test_authorization_header_and_token_never_appear_in_audit_summaries(): void
    {
        [, , $token] = $this->credential([IntegrationScope::SelfRead->value]);
        $this->withToken($token)->getJson('/api/v1/integration/self')->assertOk();

        $summaries = AuditLog::query()->pluck('summary')->implode("\n");
        $this->assertStringNotContainsString($token, $summaries);
        $this->assertStringNotContainsString('Authorization:', $summaries);
        $this->assertStringNotContainsString('Bearer '.$token, $summaries);
    }

    public function test_integration_client_is_not_represented_as_user(): void
    {
        [$client, , $token] = $this->credential([IntegrationScope::SelfRead->value]);
        $context = app(IntegrationCredentialService::class)->authenticate($token);

        $this->assertInstanceOf(IntegrationClient::class, $context->client);
        $this->assertNotInstanceOf(User::class, $context->client);
        $this->assertSame($client->id, $context->client->id);
        $this->assertGuest();
    }

    public function test_unexpected_input_uses_stable_validation_error_envelope(): void
    {
        [, , $token] = $this->credential([IntegrationScope::SelfRead->value]);

        $response = $this->withToken($token)->getJson('/api/v1/integration/self?unexpected=value');

        $response->assertStatus(422)
            ->assertJsonPath('error.code', IntegrationErrorCode::RequestInvalid->value)
            ->assertJsonStructure(['error' => ['code', 'message'], 'correlation_id']);
        $correlationId = $response->headers->get('X-Correlation-ID');
        $this->assertNotNull($correlationId);
        $this->assertSame($correlationId, $response->json('correlation_id'));
    }

    public function test_rotation_creates_new_credential_without_implicitly_revoking_old_one(): void
    {
        [$client, $credential, $token] = $this->credential([IntegrationScope::SelfRead->value]);
        $rotated = app(IntegrationCredentialService::class)->rotate($credential);

        $this->assertNotSame($credential->public_id, $rotated->publicId);
        $this->assertSame(2, IntegrationClientCredential::query()->where('integration_client_id', $client->id)->count());
        $this->withToken($token)->getJson('/api/v1/integration/self')->assertOk();
        $this->withToken($rotated->plainTextToken)->getJson('/api/v1/integration/self')->assertOk();
    }

    /**
     * @param  array<int, string>  $scopes
     * @return array{0: IntegrationClient, 1: IntegrationClientCredential, 2: string}
     */
    private function credential(array $scopes, int $requestsPerMinute = 60): array
    {
        $client = app(IntegrationClientService::class)->create(
            'Integration Test Client '.Str::uuid(),
            $requestsPerMinute,
        );
        $issued = app(IntegrationCredentialService::class)->issue($client, $scopes);
        $credential = IntegrationClientCredential::query()
            ->where('public_id', $issued->publicId)
            ->firstOrFail();

        return [$client, $credential, $issued->plainTextToken];
    }

    private function assertAuthenticationFailure(string $token): void
    {
        $response = $this->withToken($token)->getJson('/api/v1/integration/self');

        $response->assertUnauthorized()
            ->assertJsonPath('error.code', IntegrationErrorCode::AuthenticationFailed->value)
            ->assertJsonStructure(['error' => ['code', 'message'], 'correlation_id']);
        $correlationId = $response->headers->get('X-Correlation-ID');
        $this->assertNotNull($correlationId);
        $this->assertSame($correlationId, $response->json('correlation_id'));

        $log = AuditLog::query()->latest('id')->firstOrFail();
        $this->assertSame('integration.authentication.failed', $log->action);
        $this->assertSame('denied', $log->outcome);
        $this->assertSame($correlationId, $log->correlation_id);
        $this->assertStringNotContainsString($token, $log->summary);
    }
}
