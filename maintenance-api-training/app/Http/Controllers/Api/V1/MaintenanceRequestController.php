<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceRequestRequest;
use App\Http\Resources\MaintenanceRequestResource;
use App\Models\MaintenanceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class MaintenanceRequestController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = MaintenanceRequest::query()->latest('scheduled_at');

        if ($request->user()->isTechnician()) {
            $query->where('technician_id', $request->user()->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return MaintenanceRequestResource::collection($query->get());
    }

    public function store(StoreMaintenanceRequestRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Stored in UTC; the customer books in local time.
        $data['scheduled_at'] = Carbon::createFromFormat('Y-m-d H:i', $data['scheduled_at'], config('app.local_timezone'))->utc();
        $data['status'] = 'new';

        $maintenanceRequest = MaintenanceRequest::create($data);

        return (new MaintenanceRequestResource($maintenanceRequest->fresh()))->response()->setStatusCode(201);
    }

    public function show(MaintenanceRequest $maintenanceRequest): MaintenanceRequestResource
    {
        return new MaintenanceRequestResource($maintenanceRequest);
    }
}
