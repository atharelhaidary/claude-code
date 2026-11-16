<?php

namespace App\Models;

use Database\Factories\RequestReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestReport extends Model
{
    /** @use HasFactory<RequestReportFactory> */
    use HasFactory;

    protected $fillable = ['maintenance_request_id', 'technician_id', 'notes', 'parts_used'];

    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
