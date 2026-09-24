<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\AssignTechnicianAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignTechnicianRequest;
use App\Http\Resources\MaintenanceRequestResource;
use App\Models\MaintenanceRequest;
use App\Models\User;

class AssignmentController extends Controller
{
    public function store(AssignTechnicianRequest $request, MaintenanceRequest $maintenanceRequest, AssignTechnicianAction $assign): MaintenanceRequestResource
    {
        $technician = User::query()->findOrFail($request->validated('technician_id'));

        return new MaintenanceRequestResource($assign->execute($maintenanceRequest, $technician)->fresh());
    }
}
