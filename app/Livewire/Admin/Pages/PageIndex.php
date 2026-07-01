<?php

namespace App\Livewire\Admin\Pages;

use App\Livewire\Admin\Concerns\AuthorizesAdminActions;
use App\Models\Page;
use App\Services\PageService;
use Livewire\Component;
use Livewire\WithPagination;

class PageIndex extends Component
{
    use AuthorizesAdminActions, WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function deletePage(PageService $service, int $id): void
    {
        $this->authorizeAdminAction('cms.editar');

        $service->delete(Page::findOrFail($id));

        session()->flash('success', 'Página removida com sucesso.');
    }

    public function toggleActive(int $id): void
    {
        $this->authorizeAdminAction('cms.editar');

        $page = Page::findOrFail($id);
        $page->update(['is_active' => !$page->is_active]);
    }

    public function render(): \Illuminate\View\View
    {
        $pages = Page::query()
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.pages.page-index', compact('pages'))
            ->layout('admin.layouts.app', ['title' => 'Páginas']);
    }
}
