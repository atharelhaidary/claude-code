<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\MaintenanceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceRequest>
 */
class MaintenanceRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'technician_id' => null,
            'title' => fake()->randomElement(['AC not cooling', 'Water leak under sink', 'Power socket sparking', 'Washing machine not draining', 'Door lock jammed']),
            'description' => fake()->sentence(12),
            'status' => 'new',
            'priority' => fake()->randomElement(MaintenanceRequest::PRIORITIES),
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+2 weeks')->format('Y-m-d H:00:00'),
            'completed_at' => null,
        ];
    }
}
