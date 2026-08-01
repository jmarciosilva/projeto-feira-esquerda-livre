<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/v1/auth/registrar', [
            'name' => 'Maria Compradora',
            'email' => 'maria@example.com',
            'whatsapp' => '11999998888',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'maria@example.com')
            ->assertJsonPath('user.role', 'user')
            ->assertJsonStructure(['user', 'token']);

        $this->assertDatabaseHas('users', ['email' => 'maria@example.com', 'role' => 'user']);
    }

    public function test_register_requires_valid_data(): void
    {
        $this->postJson('/api/v1/auth/registrar', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'whatsapp', 'password']);
    }

    public function test_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('segredo123')]);

        $response = $this->postJson('/api/v1/auth/entrar', [
            'email' => $user->email,
            'password' => 'segredo123',
        ]);

        $response->assertOk()->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('segredo123')]);

        $this->postJson('/api/v1/auth/entrar', [
            'email' => $user->email,
            'password' => 'senha-errada',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_for_inactive_user(): void
    {
        $user = User::factory()->create(['password' => bcrypt('segredo123'), 'is_active' => false]);

        $this->postJson('/api/v1/auth/entrar', [
            'email' => $user->email,
            'password' => 'segredo123',
        ])->assertStatus(422);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/eu')->assertStatus(401);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);
        $token = $user->createToken('teste')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/eu')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('teste')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/sair')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
