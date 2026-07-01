<?php

namespace App\Livewire\Admin\Menus;

use App\Livewire\Admin\Concerns\AuthorizesAdminActions;
use App\Models\Menu;
use App\Models\MenuItem;
use Livewire\Component;

class MenuForm extends Component
{
    use AuthorizesAdminActions;

    public Menu $menu;

    public bool   $showItemForm = false;
    public ?int   $editItemId   = null;
    public string $item_title   = '';
    public string $item_url     = '';
    public string $item_icon    = '';
    public string $item_target  = '_self';
    public int    $item_order   = 0;
    public ?int   $item_parent  = null;
    public bool   $item_active  = true;
    public bool   $confirmDelete = false;
    public ?int   $deleteId     = null;

    public function mount(Menu $menu): void
    {
        $this->menu = $menu;
    }

    public function openAddItem(): void
    {
        $this->authorizeAdminAction('cms.editar');

        $this->reset(['item_title', 'item_url', 'item_icon', 'item_target', 'item_order', 'item_parent', 'editItemId']);
        $this->item_target = '_self';
        $this->item_active = true;
        $this->showItemForm = true;
    }

    public function openEditItem(int $id): void
    {
        $this->authorizeAdminAction('cms.editar');

        $item = MenuItem::findOrFail($id);
        $this->editItemId  = $id;
        $this->item_title  = $item->title;
        $this->item_url    = $item->url;
        $this->item_icon   = $item->icon ?? '';
        $this->item_target = $item->target;
        $this->item_order  = $item->sort_order;
        $this->item_parent = $item->parent_id;
        $this->item_active = $item->is_active;
        $this->showItemForm = true;
    }

    public function saveItem(): void
    {
        $this->authorizeAdminAction('cms.editar');

        $this->validate([
            'item_title' => 'required|string|max:100',
            'item_url'   => 'required|string|max:500',
            'item_target' => 'in:_self,_blank',
        ]);

        $data = [
            'menu_id'    => $this->menu->id,
            'parent_id'  => $this->item_parent,
            'title'      => $this->item_title,
            'url'        => $this->item_url,
            'icon'       => $this->item_icon,
            'target'     => $this->item_target,
            'sort_order' => $this->item_order,
            'is_active'  => $this->item_active,
        ];

        if ($this->editItemId) {
            MenuItem::findOrFail($this->editItemId)->update($data);
        } else {
            MenuItem::create($data);
        }

        $this->showItemForm = false;
    }

    public function confirmDelete(int $id): void
    {
        $this->authorizeAdminAction('cms.editar');

        $this->deleteId      = $id;
        $this->confirmDelete = true;
    }

    public function deleteItem(): void
    {
        $this->authorizeAdminAction('cms.editar');

        MenuItem::findOrFail($this->deleteId)->delete();
        $this->confirmDelete = false;
        $this->deleteId      = null;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.menus.menu-form', [
            'items' => MenuItem::where('menu_id', $this->menu->id)
                ->with('children')
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->get(),
            'allItems' => MenuItem::where('menu_id', $this->menu->id)->orderBy('sort_order')->get(),
        ])->layout('admin.layouts.app', ['title' => 'Gerenciar Menu: ' . $this->menu->name]);
    }
}
