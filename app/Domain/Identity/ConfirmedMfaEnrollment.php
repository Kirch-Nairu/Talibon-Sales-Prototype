<?php

namespace App\Domain\Identity;

final readonly class ConfirmedMfaEnrollment
{
    /**
     * @param  array<int, string>  $recoveryCodes
     */
    public function __construct(
        public int $userId,
        public int $mfaVersion,
        public array $recoveryCodes = [],
    ) {
    }
}
