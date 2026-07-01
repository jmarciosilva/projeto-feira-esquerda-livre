<?php

namespace App\Livewire\Admin\Pages;

use App\Livewire\Admin\Concerns\AuthorizesAdminActions;
use App\Models\Page;
use App\Services\PageService;
use Livewire\Component;

class PageForm extends Component
{
    use AuthorizesAdminActions;

    public ?Page $page = null;

    public string $title            = '';
    public string $slug             = '';
    public string $meta_title       = '';
    public string $meta_description = '';
    public bool   $is_homepage      = false;
    public bool   $is_active        = true;

    public function mount(?Page $page = null): void
    {
        if ($page && $page->exists) {
            $this->page             = $page;
            $this->title            = $page->title;
            $this->slug             = $page->slug;
            $this->meta_title       = $page->meta_title ?? '';
            $this->meta_description = $page->meta_description ?? '';
            $this->is_homepage      = $page->is_homepage;
            $this->is_active        = $page->is_active;
        }
    }

    public function updatedTitle(): void
    {
        if (!$this->page) {
            $this->slug = \Illuminate\Support\Str::slug($this->title);
        }
    }

    public function save(PageService $service): void
    {
        $this->authorizeAdminAction('cms.editar');

        $this->validate([
            'title'            => 'required|string|max:255',
            'slug'             => 'nullable|string|max:255',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $data = [
            'title'            => $this->title,
            'slug'             => $this->slug ?: $this->title,
            'meta_title'       => $this->meta_title,
            'meta_description' => $this->meta_description,
            'is_homepage'      => $this->is_homepage,
            'is_active'        => $this->is_active,
        ];

        if ($this->page && $this->page->exists) {
            $service->update($this->page, $data);
            session()->flash('success', 'Página atualizada com sucesso.');
        } else {
            $page = $service->create($data);
            session()->flash('success', 'Página criada com sucesso.');
            $this->redirect(route('admin.pages.edit', $page));
            return;
        }

        $this->dispatch('saved');
    }

    public function render(): \Illuminate\View\View
    {
        $title = $this->page ? 'Editar Página' : 'Nova Página';

        return view('livewire.admin.pages.page-form')
            ->layout('admin.layouts.app', ['title' => $title]);
    }
}
