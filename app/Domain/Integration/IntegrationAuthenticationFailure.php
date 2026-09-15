<?php

namespace App\Domain\Integration;

enum IntegrationAuthenticationFailure: string
{
    case Malformed = 'malformed_credential';
    case Unknown = 'unknown_credential';
    case WrongSecret = 'wrong_secret';
    case Revoked = 'revoked_credential';
    case Expired = 'expired_credential';
    case InactiveClient = 'inactive_client';
}
