<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequestStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateStatus', $this->route('maintenanceRequest'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['in_progress', 'done', 'cancelled'])],
        ];
    }
}
