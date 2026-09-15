<?php

namespace App\Domain\TravelOrders;

enum TravelOrderStatus: string
{
    case Approved = 'approved';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $next): bool
    {
        return $this === self::Approved
            && in_array($next, [self::Completed, self::Cancelled], true);
    }
}
