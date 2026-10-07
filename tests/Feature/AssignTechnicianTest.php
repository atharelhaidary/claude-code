
<!-- test -->
<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignTechnicianTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatcher_cannot_assign_technician_to_done_request(): void
    {
        $dispatcher = User::factory()->dispatcher()->create();

        $technician = User::factory()->technician()->create();

        $request = MaintenanceRequest::factory()->create([
            'status' => 'done',
            'technician_id' => null,
        ]);

        $this->actingAs($dispatcher)
            ->postJson(
                "/api/v1/requests/{$request->id}/assign",
                ['technician_id' => $technician->id]
            )
            ->assertStatus(422);

        $this->assertDatabaseHas('maintenance_requests', [
            'id' => $request->id,
            'status' => 'done',
            'technician_id' => null,
        ]);
    }

    public function test_dispatcher_cannot_assign_technician_to_cancelled_request(): void
    {
        $dispatcher = User::factory()->dispatcher()->create();

        $technician = User::factory()->technician()->create();

        $request = MaintenanceRequest::factory()->create([
            'status' => 'cancelled',
            'technician_id' => null,
        ]);

        $this->actingAs($dispatcher)
            ->postJson(
                "/api/v1/requests/{$request->id}/assign",
                ['technician_id' => $technician->id]
            )
            ->assertStatus(422);

        $this->assertDatabaseHas('maintenance_requests', [
            'id' => $request->id,
            'status' => 'cancelled',
            'technician_id' => null,
        ]);
    }
}