<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Three users per role, ten customers and thirty requests in mixed states.
     * Every seeded account's password is "password".
     */
    public function run(): void
    {
        User::factory()->admin()->create(['name' => 'Admin', 'email' => 'admin@example.com']);
        User::factory()->admin()->count(2)->create();

        User::factory()->dispatcher()->create(['name' => 'Dispatcher', 'email' => 'dispatcher@example.com']);
        User::factory()->dispatcher()->count(2)->create();

        $technicians = collect([
            User::factory()->technician()->create(['name' => 'Technician', 'email' => 'technician@example.com']),
        ])->merge(User::factory()->technician()->count(2)->create());

        $customers = Customer::factory()->count(10)->create();

        foreach (range(1, 30) as $i) {
            $status = MaintenanceRequest::STATUSES[$i % count(MaintenanceRequest::STATUSES)];
            $assigned = $status !== 'new';

            MaintenanceRequest::factory()->create([
                'customer_id' => $customers->random()->id,
                'technician_id' => $assigned ? $technicians[$i % 3]->id : null,
                'status' => $status,
                'scheduled_at' => now()->utc()->addDays($i % 14)->setTime(6 + $i % 10, 0),
                'completed_at' => $status === 'done' ? now()->utc()->subDays($i % 5) : null,
            ]);
        }
    }
}
