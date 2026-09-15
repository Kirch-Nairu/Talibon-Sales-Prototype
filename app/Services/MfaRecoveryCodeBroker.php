<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Throwable;

final class MfaRecoveryCodeBroker
{
    /**
     * @param  array<int, string>  $codes
     */
    public function seal(array $codes): string
    {
        return Crypt::encryptString(json_encode($codes, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<int, string>
     */
    public function open(?string $payload): array
    {
        if (! $payload) {
            return [];
        }

        try {
            $codes = json_decode(Crypt::decryptString($payload), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return [];
        }

        return is_array($codes)
            ? array_values(array_filter($codes, 'is_string'))
            : [];
    }
}
