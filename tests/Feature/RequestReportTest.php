<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_assigned_technician_can_file_a_report(): void
    {
        $technician = User::factory()->technician()->create();
        $request = MaintenanceRequest::factory()->create(['technician_id' => $technician->id, 'status' => 'done']);

        $this->actingAs($technician)
            ->postJson("/api/v1/requests/{$request->id}/report", ['notes' => 'Replaced the capacitor.'])
            ->assertCreated();

        $this->assertDatabaseHas('request_reports', [
            'maintenance_request_id' => $request->id,
            'technician_id' => $technician->id,
        ]);
    }
}
