<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateRequestTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'customer_id' => Customer::factory()->create()->id,
            'title' => 'AC not cooling',
            'description' => 'The living room unit blows warm air.',
            'priority' => 'high',
            'scheduled_at' => '2026-10-01 10:00',
        ], $overrides);
    }

    public function test_a_dispatcher_can_create_a_request(): void
    {
        $payload = $this->payload();

        $this->actingAs(User::factory()->dispatcher()->create())
            ->postJson('/api/v1/requests', $payload)
            ->assertCreated()
            ->assertJsonPath('data.status', 'new');

        $this->assertDatabaseHas('maintenance_requests', [
            'customer_id' => $payload['customer_id'],
            'title' => 'AC not cooling',
            'status' => 'new',
        ]);
    }

    public function test_the_required_fields_are_validated(): void
    {
        $this->actingAs(User::factory()->dispatcher()->create())
            ->postJson('/api/v1/requests', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_id', 'title', 'description', 'priority', 'scheduled_at']);
    }

    public function test_a_technician_cannot_create_a_request(): void
    {
        $this->actingAs(User::factory()->technician()->create())
            ->postJson('/api/v1/requests', $this->payload())
            ->assertForbidden();
    }

    public function test_a_new_request_keeps_the_chosen_priority(): void
    {
        $this->actingAs(User::factory()->dispatcher()->create())
            ->postJson('/api/v1/requests', $this->payload(['priority' => 'high']))
            ->assertCreated();

        $this->assertDatabaseHas('maintenance_requests', ['title' => 'AC not cooling', 'priority' => 'high']);
    }
}
