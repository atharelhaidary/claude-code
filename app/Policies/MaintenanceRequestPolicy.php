<?php

namespace App\Policies;

use App\Models\MaintenanceRequest;
use App\Models\User;

class MaintenanceRequestPolicy
{
    public function create(User $user): bool
    {
        return $user->isOffice();
    }

    public function assign(User $user, MaintenanceRequest $request): bool
    {
        return $user->isOffice();
    }

    public function updateStatus(User $user, MaintenanceRequest $request): bool
    {
        return $user->isOffice() || $request->technician_id === $user->id;
    }

    public function report(User $user, MaintenanceRequest $request): bool
    {
        return $user->isTechnician() && $request->technician_id === $user->id;
    }
}
