<?php

namespace App\Http\Requests;

use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', MaintenanceRequest::class);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'title' => ['required', 'string', 'max:190'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(MaintenanceRequest::PRIORITIES)],
            // Local time as the customer booked it, e.g. "2026-10-01 10:00".
            'scheduled_at' => ['required', 'date_format:Y-m-d H:i'],
        ];
    }
}
