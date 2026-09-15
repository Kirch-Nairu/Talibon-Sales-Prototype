<?php

namespace App\Domain\Identity;

use App\Models\User;

final class PrivilegedRolePolicy
{
    public function requiresMfa(User $user): bool
    {
        return in_array($user->role, config('identity.privileged_roles', []), true);
    }
}
