<?php

namespace App\Domain\Correspondence;

enum CorrespondenceLifecycleState: string
{
    case Received = 'received';
    case Registered = 'registered';
    case Classified = 'classified';

    // Reserved vocabulary only; Core A does not implement these transitions.
    case Routed = 'routed';
    case InAction = 'in_action';
    case Released = 'released';
    case Archived = 'archived';
}
