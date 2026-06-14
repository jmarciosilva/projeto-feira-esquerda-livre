<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use App\Services\PageService;
use Livewire\Component;
use Livewire\WithPagination;

class PageIndex extends Component
{
    use WithPagination;

    public string $search    = '';
    public bool   $confirmDelete = false;
    public ?int   $deleteId  = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId      = $id;
        $this->confirmDelete = true;
    }

    public function deletePage(PageService $service): void
    {
        $page = Page::findOrFail($this->deleteId);
        $service->delete($page);

        $this->confirmDelete = false;
        $this->deleteId      = null;

        session()->flash('success', 'Página removida com sucesso.');
    }

    public function toggleActive(int $id): void
    {
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
