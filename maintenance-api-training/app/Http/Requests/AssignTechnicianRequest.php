<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTechnicianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(
            'assign',
            $this->route('maintenanceRequest')
        );
    }

    public function rules(): array
    {
        return [
            'technician_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $maintenanceRequest = $this->route('maintenanceRequest');

            if (
                $maintenanceRequest &&
                in_array($maintenanceRequest->status, ['done', 'cancelled'], true)
            ) {
                $validator->errors()->add(
                    'maintenanceRequest',
                    'Cannot assign a technician to a completed or cancelled request.'
                );
            }
        });
    }
}