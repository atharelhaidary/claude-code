<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_assigned_technician_can_start_work(): void
    {
        $technician = User::factory()->technician()->create();
        $request = MaintenanceRequest::factory()->create(['technician_id' => $technician->id, 'status' => 'assigned']);

        $this->actingAs($technician)
            ->patchJson("/api/v1/requests/{$request->id}/status", ['status' => 'in_progress'])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_finishing_a_request_records_when_it_was_completed(): void
    {
        $technician = User::factory()->technician()->create();
        $request = MaintenanceRequest::factory()->create(['technician_id' => $technician->id, 'status' => 'in_progress']);

        $response = $this->actingAs($technician)
            ->patchJson("/api/v1/requests/{$request->id}/status", ['status' => 'done'])
            ->assertOk()
            ->assertJsonPath('data.status', 'done');

        // Clients parse this as an ISO 8601 timestamp.
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})$/',
            (string) $response->json('data.completed_at'),
        );
    }
}
