<?php

namespace App\Domain\Correspondence;

enum CorrespondenceClassification: string
{
    case Public = 'public';
    case Internal = 'internal';
    case Confidential = 'confidential';
    case Restricted = 'restricted';

    public function externalStatusValue(): ?string
    {
        return match ($this) {
            self::Public, self::Internal => $this->value,
            self::Confidential, self::Restricted => null,
        };
    }
}
