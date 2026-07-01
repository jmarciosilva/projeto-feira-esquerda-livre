<?php

namespace App\Livewire\Admin\Expositores;

use App\Livewire\Admin\Concerns\AuthorizesAdminActions;
use App\Models\Expositor;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ExpositoresIndex extends Component
{
    use AuthorizesAdminActions, WithPagination;

    public string $search       = '';
    public string $filterStatus = '';
    public ?int   $viewId       = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function toggleFeatured(int $id): void
    {
        $this->authorizeAdminAction('cms.editar');

        $expositor = Expositor::findOrFail($id);
        $expositor->is_featured = ! $expositor->is_featured;
        $expositor->save();
    }

    public function toggleActive(int $id): void
    {
        $this->authorizeAdminAction('cms.editar');

        $expositor = Expositor::findOrFail($id);
        $expositor->is_active = ! $expositor->is_active;
        $expositor->save();
    }

    public function openView(int $id): void
    {
        $this->viewId = $id;
    }

    public function closeView(): void
    {
        $this->viewId = null;
    }

    public function render(): View
    {
        $expositores = Expositor::query()
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('city', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->when($this->filterStatus === 'featured', fn ($q) => $q->where('is_featured', true))
            ->when($this->filterStatus === 'active',   fn ($q) => $q->where('is_active',   true))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        $viewExpositor = $this->viewId ? Expositor::find($this->viewId) : null;

        return view('livewire.admin.expositores.expositores-index', compact('expositores', 'viewExpositor'))
            ->layout('admin.layouts.app', ['title' => 'Expositores']);
    }
}
