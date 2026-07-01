<?php

namespace Tests\Feature;

use App\Enums\MarketplaceStatus;
use App\Enums\UserRole;
use App\Livewire\Admin\Clientes\ClienteIndex;
use App\Livewire\Admin\Pages\PageIndex;
use App\Livewire\Admin\Pedidos\PedidoIndex;
use App\Livewire\Admin\Permissoes\PerfilAcessoIndex;
use App\Livewire\Admin\Usuarios\UsuarioForm;
use App\Livewire\Admin\Usuarios\UsuarioIndex;
use App\Mail\InternalUserAccessCreated;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\Page;
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

    public function test_admin_can_access_permission_profiles_area(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->makeUser(UserRole::Admin);

        $this->actingAs($admin)
            ->get(route('admin.permissoes.index'))
            ->assertOk()
            ->assertSee('name="selectedRole"', false)
            ->assertSee('permission-profile-role-', false)
            ->assertSee('Salvar Permiss');
    }

    public function test_admin_can_view_registered_clients_area(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->makeUser(UserRole::Admin);
        $client = $this->makeUser(UserRole::User, [
            'name' => 'Cliente Teste',
            'email' => 'cliente@example.com',
        ]);
        $lojista = $this->makeUser(UserRole::Lojista, [
            'name' => 'Lojista Fora da Lista',
            'email' => 'lojista-lista@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.clientes.index'))
            ->assertOk()
            ->assertSee($client->name)
            ->assertSee($client->email)
            ->assertDontSee($lojista->name);
    }

    public function test_internal_user_without_clients_permission_cannot_access_clients_area(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $editor = $this->makeUser(UserRole::Editor);

        $this->actingAs($editor)
            ->get(route('admin.clientes.index'))
            ->assertForbidden();
    }

    public function test_permission_profile_selection_uses_single_active_role(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->makeUser(UserRole::Admin);

        Livewire::actingAs($admin)
            ->test(PerfilAcessoIndex::class)
            ->assertSet('selectedRole', 'gerente')
            ->set('selectedRole', 'supervisor')
            ->assertSet('selectedRole', 'supervisor')
            ->set('selectedRole', 'cliente')
            ->assertSet('selectedRole', 'cliente')
            ->assertSet('selectedPermissions', []);
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

    public function test_supervisor_can_view_cms_but_cannot_see_write_shortcuts(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $supervisor = $this->makeUser(UserRole::Supervisor);

        $this->actingAs($supervisor)
            ->get(route('admin.pages.index'))
            ->assertOk()
            ->assertDontSee(route('admin.pages.create'));
    }

    public function test_supervisor_cannot_access_cms_create_route_directly(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $supervisor = $this->makeUser(UserRole::Supervisor);

        $this->actingAs($supervisor)
            ->get(route('admin.pages.create'))
            ->assertForbidden();
    }

    public function test_supervisor_cannot_trigger_cms_write_action_through_livewire(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $supervisor = $this->makeUser(UserRole::Supervisor);
        $page = Page::create([
            'title' => 'Pagina protegida',
            'slug' => 'pagina-protegida',
            'is_active' => true,
        ]);

        Livewire::actingAs($supervisor)
            ->test(PageIndex::class)
            ->call('toggleActive', $page->id)
            ->assertForbidden();
    }

    public function test_internal_user_with_order_view_only_cannot_update_order_status(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $supervisor = $this->makeUser(UserRole::Supervisor);
        $supervisor->roles()->first()->revokePermissionTo('pedidos.atualizar_status');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $order = Order::create([
            'customer_name' => 'Cliente Teste',
            'customer_whatsapp' => '(11) 99999-9999',
            'address_cep' => '01001-000',
            'address_rua' => 'Rua Teste',
            'address_numero' => '100',
            'address_bairro' => 'Centro',
            'address_cidade' => 'Sao Paulo',
            'address_estado' => 'SP',
            'items_total' => 100,
            'shipping_total' => 0,
            'total_amount' => 100,
        ]);

        $this->actingAs($supervisor)
            ->get(route('admin.pedidos.index'))
            ->assertOk();

        Livewire::actingAs($supervisor)
            ->test(PedidoIndex::class)
            ->call('updateStatus', $order->id, 'concluido')
            ->assertForbidden();
    }

    public function test_admin_user_with_customer_profile_appears_in_clients_list(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->makeUser(UserRole::Admin);

        CustomerProfile::create([
            'user_id' => $admin->id,
            'marketplace_status' => MarketplaceStatus::Active,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.clientes.index'))
            ->assertOk()
            ->assertSee($admin->name);
    }

    public function test_gerente_can_inactivate_customer(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $gerente = $this->makeUser(UserRole::Gerente);
        $client = $this->makeUser(UserRole::User, ['name' => 'Cliente para Inativar']);

        Livewire::actingAs($gerente)
            ->test(ClienteIndex::class)
            ->call('inactivateCustomer', $client->id)
            ->assertHasNoErrors();

        $this->assertSame(
            MarketplaceStatus::Inactive,
            $client->fresh()->customerProfile->marketplace_status
        );
    }

    public function test_inactivating_customer_who_is_also_admin_preserves_admin_access(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->makeUser(UserRole::Admin);

        CustomerProfile::create([
            'user_id' => $admin->id,
            'marketplace_status' => MarketplaceStatus::Active,
        ]);

        Livewire::actingAs($admin)
            ->test(ClienteIndex::class)
            ->call('inactivateCustomer', $admin->id)
            ->assertHasNoErrors();

        // is_active do usuário permanece true — acesso ao painel preservado
        $this->assertTrue($admin->fresh()->is_active);

        // marketplace_status é inativo
        $this->assertSame(
            MarketplaceStatus::Inactive,
            $admin->fresh()->customerProfile->marketplace_status
        );

        // Admin ainda acessa o painel normalmente
        $this->actingAs($admin->fresh())
            ->get(route('admin.clientes.index'))
            ->assertOk();
    }

    public function test_supervisor_cannot_inactivate_customer_without_manage_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $supervisor = $this->makeUser(UserRole::Supervisor);
        $client = $this->makeUser(UserRole::User);

        Livewire::actingAs($supervisor)
            ->test(ClienteIndex::class)
            ->call('inactivateCustomer', $client->id)
            ->assertForbidden();
    }

    public function test_status_filter_uses_marketplace_status_not_global_is_active(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->makeUser(UserRole::Admin);

        $activeClient = $this->makeUser(UserRole::User, ['name' => 'Cliente Ativo']);
        $inactiveClient = $this->makeUser(UserRole::User, ['name' => 'Cliente Inativo']);
        $inactiveClient->customerProfile()->update(['marketplace_status' => MarketplaceStatus::Inactive]);

        Livewire::actingAs($admin)
            ->test(ClienteIndex::class)
            ->set('status', 'active')
            ->assertSee($activeClient->name)
            ->assertDontSee($inactiveClient->name);

        Livewire::actingAs($admin)
            ->test(ClienteIndex::class)
            ->set('status', 'inactive')
            ->assertSee($inactiveClient->name)
            ->assertDontSee($activeClient->name);
    }

    public function test_save_shows_promote_modal_when_email_belongs_to_customer(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin   = $this->makeUser(UserRole::Admin);
        $cliente = $this->makeUser(UserRole::User, ['email' => 'cliente-existente@example.com']);

        Livewire::actingAs($admin)
            ->test(UsuarioForm::class)
            ->set('name', 'Novo Nome')
            ->set('email', 'cliente-existente@example.com')
            ->set('role', 'gerente')
            ->call('save')
            ->assertSet('showPromoteModal', true)
            ->assertSet('candidateUserId', $cliente->id)
            ->assertSet('promoteRole', 'gerente');
    }

    public function test_promote_customer_to_internal_user_preserves_customer_profile_and_orders(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin   = $this->makeUser(UserRole::Admin);
        $cliente = $this->makeUser(UserRole::User);

        $this->assertNotNull($cliente->fresh()->customerProfile);

        Livewire::actingAs($admin)
            ->test(UsuarioForm::class)
            ->set('candidateUserId', $cliente->id)
            ->set('promoteRole', 'gerente')
            ->call('promoteToInternal');

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $fresh = $cliente->fresh()->load('roles');

        // Papel interno atribuído
        $this->assertSame(UserRole::Gerente, $fresh->role);
        $this->assertTrue($fresh->isInternalUser());
        $this->assertTrue($fresh->hasRole('gerente'));
        $this->assertTrue($fresh->can('admin.acessar'));

        // CustomerProfile intacto
        $this->assertNotNull($fresh->customerProfile);
        $this->assertSame(MarketplaceStatus::Active, $fresh->customerProfile->marketplace_status);

        // Aparece na lista de clientes (ClienteIndex usa whereHas('customerProfile'))
        $this->actingAs($admin)->get(route('admin.clientes.index'))->assertOk()->assertSee($fresh->name);
    }

    public function test_supervisor_cannot_access_promote_form(): void
    {
        // Supervisor não tem usuarios.gerenciar → o formulário retorna 403 no mount()
        $this->seed(RolePermissionSeeder::class);
        $supervisor = $this->makeUser(UserRole::Supervisor);

        $this->actingAs($supervisor)
            ->get(route('admin.usuarios.create'))
            ->assertForbidden();
    }

    public function test_edit_form_can_add_customer_profile_to_internal_user(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin    = $this->makeUser(UserRole::Admin);
        $gerente  = $this->makeUser(UserRole::Gerente);

        // Gerente sem CustomerProfile inicialmente
        $this->assertNull($gerente->fresh()->customerProfile);

        Livewire::actingAs($admin)
            ->test(UsuarioForm::class, ['user' => $gerente])
            ->assertSet('isAlsoCustomer', false)
            ->set('isAlsoCustomer', true)
            ->call('save');

        $this->assertNotNull($gerente->fresh()->customerProfile);
    }

    public function test_edit_form_can_remove_customer_profile_from_internal_user(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin   = $this->makeUser(UserRole::Admin);
        $gerente = $this->makeUser(UserRole::Gerente);

        // Cria CustomerProfile manualmente
        CustomerProfile::create(['user_id' => $gerente->id, 'marketplace_status' => MarketplaceStatus::Active]);
        $this->assertNotNull($gerente->fresh()->customerProfile);

        Livewire::actingAs($admin)
            ->test(UsuarioForm::class, ['user' => $gerente])
            ->assertSet('isAlsoCustomer', true)
            ->set('isAlsoCustomer', false)
            ->call('save');

        $this->assertNull($gerente->fresh()->customerProfile);
    }

    public function test_hybrid_user_shows_customer_badge_in_usuario_index(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin   = $this->makeUser(UserRole::Admin);
        $gerente = $this->makeUser(UserRole::Gerente, ['name' => 'Gerente Híbrido']);

        // Sem CustomerProfile: badge não deve aparecer
        Livewire::actingAs($admin)
            ->test(UsuarioIndex::class)
            ->assertSee('Gerente Híbrido')
            ->assertDontSee('Cliente'); // nenhum badge cliente ainda

        // Adiciona CustomerProfile: badge deve aparecer
        CustomerProfile::create(['user_id' => $gerente->id, 'marketplace_status' => MarketplaceStatus::Active]);

        Livewire::actingAs($admin)
            ->test(UsuarioIndex::class)
            ->assertSee('Gerente Híbrido')
            ->assertSee('Cliente');
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function makeUser(UserRole $role, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => $role,
            'is_active' => true,
        ], $attributes));

        $user->assignRole($role->spatieRole());

        return $user;
    }
}
