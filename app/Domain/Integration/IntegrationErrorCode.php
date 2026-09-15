<?php

namespace App\Domain\Integration;

enum IntegrationErrorCode: string
{
    case AuthenticationFailed = 'integration_authentication_failed';
    case ScopeDenied = 'integration_scope_denied';
    case RateLimited = 'integration_rate_limited';
    case RequestInvalid = 'integration_request_invalid';
    case IdempotencyKeyRequired = 'integration_idempotency_key_required';
    case IdempotencyConflict = 'integration_idempotency_conflict';
    case IdempotencyInProgress = 'integration_idempotency_in_progress';
    case CorrespondenceNotFound = 'correspondence_not_found';

    public function message(): string
    {
        return match ($this) {
            self::AuthenticationFailed => 'The integration credential could not be authenticated.',
            self::ScopeDenied => 'The client is not permitted to perform this operation.',
            self::RateLimited => 'The client has exceeded its request limit.',
            self::RequestInvalid => 'The integration request is invalid.',
            self::IdempotencyKeyRequired => 'This operation requires a valid Idempotency-Key header.',
            self::IdempotencyConflict => 'The idempotency key was already used with a different request.',
            self::IdempotencyInProgress => 'A request with this idempotency key is already in progress.',
            self::CorrespondenceNotFound => 'The requested correspondence status is not available to this client.',
        };
    }
}
