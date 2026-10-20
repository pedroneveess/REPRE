<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Novo Usuario',
            'birthdate' => '2000-01-01',
            'email' => 'novo@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'data' => ['user' => ['id', 'name'], 'token']]);
        $this->assertDatabaseHas('users', ['email' => 'novo@example.com']);
    }

    public function test_register_validates_required_fields(): void
    {
        $response = $this->postJson('/api/auth/register', []);
        $response->assertStatus(422);
    }

    public function test_register_rejects_short_password(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Novo', 'birthdate' => '2000-01-01', 'email' => 'a@b.com',
            'password' => 'short', 'password_confirmation' => 'short',
        ]);
        $response->assertStatus(422);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com', 'password' => bcrypt('password123')]);

        $response = $this->postJson('/api/auth/login', ['email' => 'test@example.com', 'password' => 'password123']);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'data' => ['user' => ['id'], 'token']]);
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', ['email' => 'nonexistent@example.com', 'password' => 'wrong']);
        $response->assertStatus(401);
    }

    public function test_login_is_throttled(): void
    {
        foreach (range(1, 10) as $i) {
            $this->postJson('/api/auth/login', ['email' => 'x@y.com', 'password' => 'bad']);
        }
        $response = $this->postJson('/api/auth/login', ['email' => 'x@y.com', 'password' => 'bad']);
        $response->assertStatus(429);
    }

    public function test_authenticated_user_can_get_own_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->asUser($user)->getJson('/api/auth/me');

        $response->assertStatus(200);
        $response->assertJsonPath('data.user.id', $user->id);
    }

    public function test_unauthenticated_user_cannot_access_protected_route(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_invalid_token_returns_401(): void
    {
        $this->asToken('invalid-token-value')->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_authenticated_user_can_logout_and_token_is_revoked(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('t')->plainTextToken;

        $this->asToken($token)->postJson('/api/auth/logout')->assertStatus(204);

        $this->asToken($token)->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_forgot_password_returns_generic_response_for_known_and_unknown_emails(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'known@example.com']);

        $known = $this->postJson('/api/auth/forgot-password', ['email' => 'known@example.com']);
        $unknown = $this->postJson('/api/auth/forgot-password', ['email' => 'unknown@example.com']);

        $known->assertStatus(200);
        $unknown->assertStatus(200);
        $this->assertSame($known->json('message'), $unknown->json('message'));
    }

    public function test_reset_password_revokes_tokens_and_old_token_stops_working(): void
    {
        $user = User::factory()->create();
        $oldToken = $user->createToken('old')->plainTextToken;
        $resetToken = Password::broker()->createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => $resetToken,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->asToken($oldToken)->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => 'invalid', 'email' => $user->email,
            'password' => 'newpassword123', 'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_password_rejects_token_reuse(): void
    {
        $user = User::factory()->create();
        $resetToken = Password::broker()->createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token' => $resetToken, 'email' => $user->email,
            'password' => 'first1234', 'password_confirmation' => 'first1234',
        ])->assertStatus(200);

        $this->postJson('/api/auth/reset-password', [
            'token' => $resetToken, 'email' => $user->email,
            'password' => 'second1234', 'password_confirmation' => 'second1234',
        ])->assertStatus(422);
    }
}
