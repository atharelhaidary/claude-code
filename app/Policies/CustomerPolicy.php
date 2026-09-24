<?php

namespace App\Policies;

use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOffice();
    }

    public function create(User $user): bool
    {
        return $user->isOffice();
    }
}
