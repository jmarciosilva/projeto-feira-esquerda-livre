<?php

namespace App\Livewire\Admin\Expositores;

use App\Enums\VisibilitySlotType;
use App\Livewire\Admin\Concerns\AuthorizesAdminActions;
use App\Models\Expositor;
use App\Models\ExpositorVisibilitySlot;
use App\Services\ExpositorVisibilityService;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class VisibilityIndex extends Component
{
    use AuthorizesAdminActions, WithPagination;

    public string $search = '';

    // Modal de slot
    public bool   $showSlotModal  = false;
    public ?int   $slotExpositorId = null;
    public string $slotType       = 'home_featured';
    public int    $slotPriority   = 10;
    public string $slotActiveFrom = '';
    public string $slotActiveUntil = '';

    // Modal de peso
    public bool  $showWeightModal    = false;
    public ?int  $weightExpositorId  = null;
    public int   $weightValue        = 1;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openSlotModal(int $expositorId): void
    {
        $this->authorizeAdminAction('expositores.visibilidade');

        $expositor = Expositor::findOrFail($expositorId);
        $slot      = $expositor->activeSlot();

        $this->slotExpositorId = $expositorId;
        $this->slotType        = $slot?->slot_type->value ?? 'home_featured';
        $this->slotPriority    = $slot?->priority ?? 10;
        $this->slotActiveFrom  = $slot?->active_from?->format('Y-m-d\TH:i') ?? '';
        $this->slotActiveUntil = $slot?->active_until?->format('Y-m-d\TH:i') ?? '';
        $this->showSlotModal   = true;
    }

    public function saveSlot(): void
    {
        $this->authorizeAdminAction('expositores.visibilidade');

        $this->validate([
            'slotType'        => 'required|in:home_featured,home_rotation',
            'slotPriority'    => 'required|integer|min:0|max:100',
            'slotActiveFrom'  => 'nullable|date',
            'slotActiveUntil' => 'nullable|date|after_or_equal:slotActiveFrom',
        ]);

        ExpositorVisibilitySlot::updateOrCreate(
            ['expositor_id' => $this->slotExpositorId],
            [
                'slot_type'    => $this->slotType,
                'priority'     => $this->slotPriority,
                'active_from'  => $this->slotActiveFrom  ?: null,
                'active_until' => $this->slotActiveUntil ?: null,
                'created_by'   => auth()->id(),
            ],
        );

        app(ExpositorVisibilityService::class)->invalidateCache();

        $this->showSlotModal = false;
        session()->flash('success', 'Slot de visibilidade atualizado. Cache da home invalidado.');
    }

    public function removeSlot(int $expositorId): void
    {
        $this->authorizeAdminAction('expositores.visibilidade');

        ExpositorVisibilitySlot::where('expositor_id', $expositorId)->delete();
        app(ExpositorVisibilityService::class)->invalidateCache();

        session()->flash('success', 'Slot removido. Expositor voltou para rotação democrática padrão.');
    }

    public function openWeightModal(int $expositorId): void
    {
        $this->authorizeAdminAction('expositores.visibilidade');

        $expositor             = Expositor::findOrFail($expositorId);
        $this->weightExpositorId = $expositorId;
        $this->weightValue     = $expositor->home_rotation_weight;
        $this->showWeightModal = true;
    }

    public function saveWeight(): void
    {
        $this->authorizeAdminAction('expositores.visibilidade');

        $this->validate(['weightValue' => 'required|integer|min:1|max:10']);

        Expositor::where('id', $this->weightExpositorId)
            ->update(['home_rotation_weight' => $this->weightValue]);

        app(ExpositorVisibilityService::class)->invalidateCache();

        $this->showWeightModal = false;
        session()->flash('success', 'Peso de rotação atualizado.');
    }

    public function toggleHomeVisibility(int $expositorId): void
    {
        $this->authorizeAdminAction('expositores.visibilidade');

        $expositor = Expositor::findOrFail($expositorId);
        $expositor->update(['is_featured' => ! $expositor->is_featured]);
        app(ExpositorVisibilityService::class)->invalidateCache();
    }

    public function render(): View
    {
        $expositores = Expositor::where('is_active', true)
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->with(['visibilitySlots'])
            ->orderByDesc('is_featured')
            ->orderByDesc('total_impressions')
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.expositores.visibility-index', [
            'expositores'     => $expositores,
            'slotTypes'       => VisibilitySlotType::cases(),
        ])->layout('admin.layouts.app', ['title' => 'Gestão de Visibilidade']);
    }
}
