<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequestReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('report', $this->route('maintenanceRequest'));
    }

    public function rules(): array
    {
        return [
            'notes' => ['required', 'string', 'max:5000'],
            'parts_used' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
