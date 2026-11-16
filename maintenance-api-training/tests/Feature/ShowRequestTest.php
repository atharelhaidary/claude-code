<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_technician_can_open_a_request_assigned_to_them(): void
    {
        $technician = User::factory()->technician()->create();
        $request = MaintenanceRequest::factory()->create(['technician_id' => $technician->id, 'status' => 'assigned']);

        $this->actingAs($technician)
            ->getJson("/api/v1/requests/{$request->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $request->id)
            ->assertJsonPath('data.technician.id', $technician->id);
    }
}
