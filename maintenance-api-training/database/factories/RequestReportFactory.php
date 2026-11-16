<?php

namespace Database\Factories;

use App\Models\MaintenanceRequest;
use App\Models\RequestReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequestReport>
 */
class RequestReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'maintenance_request_id' => MaintenanceRequest::factory(),
            'technician_id' => User::factory()->technician(),
            'notes' => fake()->sentence(15),
            'parts_used' => null,
        ];
    }
}
