<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestListTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_dispatcher_sees_every_request(): void
    {
        MaintenanceRequest::factory()->count(3)->create();

        $this->actingAs(User::factory()->dispatcher()->create())
            ->getJson('/api/v1/requests')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_a_technician_sees_only_their_own_requests(): void
    {
        $technician = User::factory()->technician()->create();
        MaintenanceRequest::factory()->count(2)->create(['technician_id' => $technician->id, 'status' => 'assigned']);
        MaintenanceRequest::factory()->count(3)->create();

        $this->actingAs($technician)
            ->getJson('/api/v1/requests')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
