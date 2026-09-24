<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTechnicianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assign', $this->route('maintenanceRequest'));
    }

    public function rules(): array
    {
        return [
            'technician_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
