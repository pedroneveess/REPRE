<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/signUp', [
            'name' => 'Novo Usuario',
            'birthdate' => '2000-01-01',
            'email' => 'novo@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'user',
            'token',
        ]);
        $this->assertDatabaseHas('users', ['email' => 'novo@example.com']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/signIn', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'user',
            'token',
        ]);
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        $response = $this->postJson('/api/signIn', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_get_own_profile(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->get('/api/loggedUser');

        $response->assertStatus(200);
        $response->assertJson([
            'user' => ['id' => $user->id],
        ]);
    }

    public function test_unauthenticated_user_cannot_access_protected_route(): void
    {
        $response = $this->getJson('/api/loggedUser');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->post('/api/logoutUser');

        $response->assertStatus(200);
    }
}