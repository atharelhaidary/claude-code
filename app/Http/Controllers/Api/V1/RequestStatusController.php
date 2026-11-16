<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRequestStatusRequest;
use App\Http\Resources\MaintenanceRequestResource;
use App\Models\MaintenanceRequest;

class RequestStatusController extends Controller
{
    public function update(UpdateRequestStatusRequest $request, MaintenanceRequest $maintenanceRequest): MaintenanceRequestResource
    {
        $status = $request->validated('status');

        $maintenanceRequest->update([
            'status' => $status,
            'completed_at' => $status === 'done' ? now() : null,
        ]);

        return new MaintenanceRequestResource($maintenanceRequest->fresh());
    }
}
