<?php

namespace App\Livewire\Admin\Categorias;

use App\Enums\ItemType;
use App\Livewire\Admin\Concerns\AuthorizesAdminActions;
use App\Models\ContentCategory;
use Livewire\Component;
use Livewire\WithPagination;

class CategoriaIndex extends Component
{
    use AuthorizesAdminActions, WithPagination;

    public string $search        = '';
    public string $filterEixo    = '';
    public bool   $confirmDelete = false;
    public ?int   $deleteId      = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorizeAdminAction('cms.editar');

        $this->deleteId      = $id;
        $this->confirmDelete = true;
    }

    public function deleteCategoria(): void
    {
        $this->authorizeAdminAction('cms.editar');

        ContentCategory::findOrFail($this->deleteId)->delete();

        $this->confirmDelete = false;
        $this->deleteId      = null;

        session()->flash('success', 'Categoria removida.');
    }

    public function toggleActive(int $id): void
    {
        $this->authorizeAdminAction('cms.editar');

        $categoria = ContentCategory::findOrFail($id);
        $categoria->update(['is_active' => ! $categoria->is_active]);
    }

    public function render(): \Illuminate\View\View
    {
        $categorias = ContentCategory::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterEixo, fn ($q) => $q->where('eixo', $this->filterEixo))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.categorias.categoria-index', [
            'categorias' => $categorias,
            'itemTypes'  => ItemType::cases(),
        ])->layout('admin.layouts.app', ['title' => 'Categorias']);
    }
}
