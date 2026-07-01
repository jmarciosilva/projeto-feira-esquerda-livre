<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\Usuarios\UsuarioForm;
use App\Mail\InternalUserAccessCreated;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserGovernanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_internal_users_area(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->makeUser(UserRole::Admin);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index'))
            ->assertOk();
    }

    public function test_internal_user_without_permission_cannot_access_users_area(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $supervisor = $this->makeUser(UserRole::Supervisor);

        $this->actingAs($supervisor)
            ->get(route('admin.usuarios.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_internal_user_with_role_and_credentials_email(): void
    {
        Mail::fake();
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->makeUser(UserRole::Admin);

        Livewire::actingAs($admin)
            ->test(UsuarioForm::class)
            ->set('name', 'Supervisor Teste')
            ->set('email', 'supervisor@example.com')
            ->set('whatsapp', '(11) 99999-9999')
            ->set('role', UserRole::Supervisor->value)
            ->set('is_active', true)
            ->set('send_credentials', true)
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'supervisor@example.com')->firstOrFail();

        $this->assertSame(UserRole::Supervisor, $user->role);
        $this->assertTrue($user->hasRole(UserRole::Supervisor->spatieRole()));
        $this->assertTrue($user->is_active);

        Mail::assertSent(InternalUserAccessCreated::class, function (InternalUserAccessCreated $mail) use ($user) {
            return $mail->hasTo('supervisor@example.com')
                && $mail->user->is($user)
                && strlen($mail->temporaryPassword) === 12;
        });
    }

    private function makeUser(UserRole $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);

        $user->assignRole($role->spatieRole());

        return $user;
    }
}
