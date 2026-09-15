<?php

namespace App\Services;

use App\Models\User;

final class SystemAdministrationAccess
{
    public function allowed(User $user): bool
    {
        return $user->is_active && $user->isRole('system_admin');
    }

    public function authorize(User $user): void
    {
        abort_unless($this->allowed($user), 403);
    }
}
