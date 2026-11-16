<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_log_in_and_receive_a_token(): void
    {
        $user = User::factory()->dispatcher()->create(['email' => 'd@example.com']);

        $this->postJson('/api/v1/auth/login', ['email' => 'd@example.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.role', 'dispatcher')
            ->assertJsonStructure(['token']);
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'd@example.com']);

        $this->postJson('/api/v1/auth/login', ['email' => 'd@example.com', 'password' => 'wrong'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_the_api_requires_a_token(): void
    {
        $this->getJson('/api/v1/requests')->assertUnauthorized();
    }
}
