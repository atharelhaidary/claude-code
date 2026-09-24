<?php

namespace App\Actions;

use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AssignTechnicianAction
{
    public function execute(MaintenanceRequest $request, User $technician): MaintenanceRequest
    {
        if (! $technician->isTechnician()) {
            throw ValidationException::withMessages([
                'technician_id' => 'The selected user is not a technician.',
            ]);
        }

        $busy = MaintenanceRequest::query()
            ->where('technician_id', $technician->id)
            ->where('scheduled_at', $request->scheduled_at)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereKeyNot($request->id)
            ->exists();

        if ($busy) {
            throw ValidationException::withMessages([
                'technician_id' => 'The technician already has a visit at this time.',
            ]);
        }

        $request->update([
            'technician_id' => $technician->id,
            'status' => 'assigned',
        ]);

        return $request;
    }
}
