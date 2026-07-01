<?php

namespace App\Livewire\Admin\Menus;

use App\Livewire\Admin\Concerns\AuthorizesAdminActions;
use App\Models\Menu;
use Livewire\Component;
use Livewire\WithPagination;

class MenuIndex extends Component
{
    use AuthorizesAdminActions, WithPagination;

    public bool  $showForm = false;
    public string $name   = '';
    public string $location = 'header';
    public bool   $is_active = true;
    public ?int  $editId    = null;

    public function openCreate(): void
    {
        $this->authorizeAdminAction('cms.editar');

        $this->reset(['name', 'location', 'is_active', 'editId']);
        $this->location  = 'header';
        $this->is_active = true;
        $this->showForm  = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorizeAdminAction('cms.editar');

        $menu = Menu::findOrFail($id);
        $this->editId    = $id;
        $this->name      = $menu->name;
        $this->location  = $menu->location->value;
        $this->is_active = $menu->is_active;
        $this->showForm  = true;
    }

    public function save(): void
    {
        $this->authorizeAdminAction('cms.editar');

        $this->validate([
            'name'     => 'required|string|max:100',
            'location' => 'required|in:header,footer,sidebar,mobile',
        ]);

        $data = [
            'name'      => $this->name,
            'location'  => $this->location,
            'is_active' => $this->is_active,
        ];

        if ($this->editId) {
            Menu::findOrFail($this->editId)->update($data);
            session()->flash('success', 'Menu atualizado.');
        } else {
            Menu::create($data);
            session()->flash('success', 'Menu criado.');
        }

        $this->showForm = false;
    }

    public function deleteMenu(int $id): void
    {
        $this->authorizeAdminAction('cms.editar');

        Menu::findOrFail($id)->delete();

        session()->flash('success', 'Menu removido.');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.menus.menu-index', [
            'menus' => Menu::withCount('allItems')->latest()->paginate(15),
        ])->layout('admin.layouts.app', ['title' => 'Menus']);
    }
}
