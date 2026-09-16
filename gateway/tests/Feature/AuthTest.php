<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_receives_a_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'ada@example.com')
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);
    }

    public function test_registration_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->postJson('/api/register', [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_a_user_can_log_in(): void
    {
        User::factory()->create([
            'email' => 'ada@example.com',
            'password' => 'secret-password',
        ]);

        $this->postJson('/api/login', [
            'email' => 'ada@example.com',
            'password' => 'secret-password',
        ])->assertOk()->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->postJson('/api/login', [
            'email' => 'ada@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_an_authenticated_user_can_read_their_profile_and_log_out(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('email', $user->email);

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_the_profile_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_a_guest_gets_401_even_without_the_json_accept_header(): void
    {
        $this->get('/api/me')->assertUnauthorized();
    }
}
