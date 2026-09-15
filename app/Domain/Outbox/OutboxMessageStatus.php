<?php

namespace App\Domain\Outbox;

enum OutboxMessageStatus: string
{
    case Pending = 'pending';
    case Claimed = 'claimed';
}
