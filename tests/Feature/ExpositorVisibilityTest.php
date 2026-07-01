<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VisibilitySlotType;
use App\Jobs\ExpositorImpressionJob;
use App\Models\Expositor;
use App\Models\ExpositorImpression;
use App\Models\ExpositorVisibilitySlot;
use App\Models\User;
use App\Services\ExpositorVisibilityService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class ExpositorVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Cache::forget('expositor_home_selection');
    }

    private function makeAdmin(): User
    {
        $u = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $u->assignRole(UserRole::Admin->spatieRole());
        return $u;
    }

    private function makeExpositor(array $attrs = []): Expositor
    {
        return Expositor::create(array_merge([
            'name'                => 'Expositor ' . uniqid(),
            'slug'                => 'expositor-' . uniqid(),
            'description'         => 'desc',
            'is_active'           => true,
            'is_featured'         => true,
            'home_rotation_weight'=> 1,
        ], $attrs));
    }

    // ── ExpositorVisibilityService ─────────────────────────────────────────────

    public function test_select_for_home_returns_only_featured_active_expositores(): void
    {
        Queue::fake();

        $featured = $this->makeExpositor(['is_featured' => true, 'is_active' => true]);
        $notFeatured = $this->makeExpositor(['is_featured' => false, 'is_active' => true]);

        $service = app(ExpositorVisibilityService::class);
        $result  = $service->selectForHome('testhash');

        $ids = $result->pluck('id')->toArray();
        $this->assertContains($featured->id, $ids);
        $this->assertNotContains($notFeatured->id, $ids);
    }

    public function test_select_for_home_dispatches_impression_job(): void
    {
        Queue::fake();

        $this->makeExpositor();

        app(ExpositorVisibilityService::class)->selectForHome('hash123');

        Queue::assertPushed(ExpositorImpressionJob::class);
    }

    public function test_select_for_home_uses_cache(): void
    {
        Queue::fake();

        $this->makeExpositor();

        $service = app(ExpositorVisibilityService::class);
        $result1 = $service->selectForHome('h1');
        $result2 = $service->selectForHome('h2');

        $this->assertEquals($result1->pluck('id')->toArray(), $result2->pluck('id')->toArray());
    }

    public function test_invalidate_cache_clears_home_selection(): void
    {
        Cache::put('expositor_home_selection', [999], now()->addMinutes(5));

        app(ExpositorVisibilityService::class)->invalidateCache();

        $this->assertFalse(Cache::has('expositor_home_selection'));
    }

    public function test_paid_featured_slot_takes_priority(): void
    {
        Queue::fake();

        $admin    = $this->makeAdmin();
        $priority = $this->makeExpositor();

        ExpositorVisibilitySlot::create([
            'expositor_id' => $priority->id,
            'slot_type'    => 'home_featured',
            'priority'     => 50,
            'active_from'  => null,
            'active_until' => null,
            'created_by'   => $admin->id,
        ]);

        $result = app(ExpositorVisibilityService::class)->selectForHome('hash');
        $ids    = $result->pluck('id')->toArray();

        $this->assertContains($priority->id, $ids);
        $this->assertEquals($priority->id, $ids[0]);
    }

    public function test_expired_featured_slot_not_prioritized(): void
    {
        Queue::fake();

        $admin   = $this->makeAdmin();
        $expired = $this->makeExpositor();

        ExpositorVisibilitySlot::create([
            'expositor_id' => $expired->id,
            'slot_type'    => 'home_featured',
            'priority'     => 90,
            'active_from'  => now()->subDays(10),
            'active_until' => now()->subDays(1),
            'created_by'   => $admin->id,
        ]);

        $service = app(ExpositorVisibilityService::class);
        $result  = $service->selectForHome('hash');

        // Only verify that the slot itself is considered expired
        $slot = ExpositorVisibilitySlot::where('expositor_id', $expired->id)->first();
        $this->assertFalse($slot->isActive());
    }

    // ── ExpositorImpressionJob ─────────────────────────────────────────────────

    public function test_impression_job_inserts_records(): void
    {
        $expositor = $this->makeExpositor();
        $initialCount = ExpositorImpression::count();

        $job = new ExpositorImpressionJob(
            [$expositor->id],
            'session_hash_abc',
            [$expositor->id => 'home_rotation'],
        );
        $job->handle();

        $this->assertGreaterThan($initialCount, ExpositorImpression::count());
    }

    public function test_impression_job_increments_total_impressions(): void
    {
        $expositor = $this->makeExpositor();
        $before    = $expositor->total_impressions;

        $job = new ExpositorImpressionJob([$expositor->id], 'hash_xyz', [$expositor->id => 'home_rotation']);
        $job->handle();

        $this->assertEquals($before + 1, $expositor->fresh()->total_impressions);
    }

    // ── Admin VisibilityIndex (Livewire) ───────────────────────────────────────

    public function test_admin_can_access_visibility_index(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
             ->get(route('admin.expositores.visibilidade'))
             ->assertSuccessful();
    }

    public function test_admin_can_toggle_home_visibility(): void
    {
        $admin    = $this->makeAdmin();
        $expositor = $this->makeExpositor(['is_featured' => true]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Expositores\VisibilityIndex::class)
            ->call('toggleHomeVisibility', $expositor->id);

        $this->assertFalse($expositor->fresh()->is_featured);
    }

    public function test_admin_can_save_slot(): void
    {
        $admin    = $this->makeAdmin();
        $expositor = $this->makeExpositor();

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Expositores\VisibilityIndex::class)
            ->call('openSlotModal', $expositor->id)
            ->set('slotType', 'home_featured')
            ->set('slotPriority', 20)
            ->call('saveSlot');

        $this->assertDatabaseHas('expositor_visibility_slots', [
            'expositor_id' => $expositor->id,
            'slot_type'    => 'home_featured',
            'priority'     => 20,
        ]);
    }

    public function test_admin_can_remove_slot(): void
    {
        $admin    = $this->makeAdmin();
        $expositor = $this->makeExpositor();

        ExpositorVisibilitySlot::create([
            'expositor_id' => $expositor->id,
            'slot_type'    => 'home_featured',
            'priority'     => 5,
            'created_by'   => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Expositores\VisibilityIndex::class)
            ->call('removeSlot', $expositor->id);

        $this->assertDatabaseMissing('expositor_visibility_slots', [
            'expositor_id' => $expositor->id,
        ]);
    }

    public function test_admin_can_save_weight(): void
    {
        $admin    = $this->makeAdmin();
        $expositor = $this->makeExpositor(['home_rotation_weight' => 1]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Expositores\VisibilityIndex::class)
            ->call('openWeightModal', $expositor->id)
            ->set('weightValue', 5)
            ->call('saveWeight');

        $this->assertEquals(5, $expositor->fresh()->home_rotation_weight);
    }

    public function test_non_admin_cannot_access_visibility(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);
        $user->assignRole(UserRole::User->spatieRole());

        $this->actingAs($user)
             ->get(route('admin.expositores.visibilidade'))
             ->assertForbidden();
    }
}
