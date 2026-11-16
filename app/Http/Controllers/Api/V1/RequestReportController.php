<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequestReportRequest;
use App\Http\Resources\RequestReportResource;
use App\Models\MaintenanceRequest;
use Illuminate\Http\JsonResponse;

class RequestReportController extends Controller
{
    public function store(StoreRequestReportRequest $request, MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        $report = $maintenanceRequest->report()->create([
            'technician_id' => $request->user()->id,
            'notes' => $request->validated('notes'),
            'parts_used' => $request->validated('parts_used'),
        ]);

        return (new RequestReportResource($report))->response()->setStatusCode(201);
    }
}
