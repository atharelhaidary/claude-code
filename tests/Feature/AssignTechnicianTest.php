<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignTechnicianTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatcher_can_assign_available_technician(): void
    {
        $request = MaintenanceRequest::factory()->create(['status' => 'new']);
        $tech = User::factory()->technician()->create();

        $this->actingAs(User::factory()->dispatcher()->create())
            ->postJson("/api/v1/requests/{$request->id}/assign", ['technician_id' => $tech->id])
            ->assertOk();

        $this->assertDatabaseHas('maintenance_requests', [
            'id' => $request->id,
            'technician_id' => $tech->id,
            'status' => 'assigned',
        ]);
    }

    public function test_a_technician_with_a_visit_at_the_same_time_is_refused(): void
    {
        $tech = User::factory()->technician()->create();
        $first = MaintenanceRequest::factory()->create(['technician_id' => $tech->id, 'status' => 'assigned', 'scheduled_at' => '2026-10-01 07:00:00']);
        $second = MaintenanceRequest::factory()->create(['status' => 'new', 'scheduled_at' => $first->scheduled_at]);

        $this->actingAs(User::factory()->dispatcher()->create())
            ->postJson("/api/v1/requests/{$second->id}/assign", ['technician_id' => $tech->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('technician_id');
    }
}
