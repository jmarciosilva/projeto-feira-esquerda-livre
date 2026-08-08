<?php

namespace Tests\Feature\CustomerIntelligence;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get(route('admin.customer-intelligence.dashboard'));

        $response->assertOk();
        $response->assertSee('Inteligência de Cliente');
    }

    public function test_gerente_can_access_dashboard(): void
    {
        $gerente = User::factory()->create(['role' => UserRole::Gerente]);
        $gerente->assignRole('gerente');

        $response = $this->actingAs($gerente)->get(route('admin.customer-intelligence.dashboard'));

        $response->assertOk();
    }

    public function test_editor_without_permission_is_forbidden(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $editor->assignRole('editor');

        $response = $this->actingAs($editor)->get(route('admin.customer-intelligence.dashboard'));

        $response->assertForbidden();
    }

    public function test_customer_cannot_access_dashboard(): void
    {
        $customer = User::factory()->create(['role' => UserRole::User]);

        $response = $this->actingAs($customer)->get(route('admin.customer-intelligence.dashboard'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.customer-intelligence.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_access_docs_page_rendered_as_html(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get(route('admin.customer-intelligence.docs'));

        $response->assertOk();
        // Markdown convertido para HTML de verdade, não texto cru dentro de <pre>.
        $response->assertSee('<h1>', false);
        $response->assertDontSee('# Integração JMF Customer Intelligence');
    }

    public function test_editor_without_permission_cannot_access_docs_page(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $editor->assignRole('editor');

        $response = $this->actingAs($editor)->get(route('admin.customer-intelligence.docs'));

        $response->assertForbidden();
    }
}
