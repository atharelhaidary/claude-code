<?php

namespace App\Http\Resources;

use App\Models\RequestReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RequestReport */
class RequestReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'maintenance_request_id' => $this->maintenance_request_id,
            'notes' => $this->notes,
            'parts_used' => $this->parts_used,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
