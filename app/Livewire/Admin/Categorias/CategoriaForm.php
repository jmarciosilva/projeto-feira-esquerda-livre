<?php

namespace App\Livewire\Admin\Categorias;

use App\Enums\ItemType;
use App\Models\ContentCategory;
use Illuminate\Support\Str;
use Livewire\Component;

class CategoriaForm extends Component
{
    public ?ContentCategory $categoria = null;

    public string  $name        = '';
    public string  $slug        = '';
    public string  $description = '';
    public string  $eixo        = '';
    public ?int    $parent_id   = null;
    public bool    $is_active   = true;

    public function mount(?ContentCategory $categoria = null): void
    {
        if ($categoria && $categoria->exists) {
            $this->categoria   = $categoria;
            $this->name        = $categoria->name;
            $this->slug        = $categoria->slug;
            $this->description = $categoria->description ?? '';
            $this->eixo         = $categoria->eixo ?? '';
            $this->parent_id    = $categoria->parent_id;
            $this->is_active    = $categoria->is_active;
        }
    }

    public function updatedName(): void
    {
        if (! $this->categoria) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function save(): void
    {
        $this->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'eixo'        => 'nullable|in:produto,servico,cuidado',
            'parent_id'   => 'nullable|exists:content_categories,id',
        ]);

        $data = [
            'name'        => $this->name,
            'slug'        => $this->slug ?: $this->name,
            'description' => $this->description,
            'eixo'        => $this->eixo ?: null,
            'parent_id'   => $this->parent_id ?: null,
            'is_active'   => $this->is_active,
        ];

        if ($this->categoria && $this->categoria->exists) {
            $this->categoria->update($data);
            session()->flash('success', 'Categoria atualizada.');
        } else {
            $categoria = ContentCategory::create($data);
            session()->flash('success', 'Categoria criada.');
            $this->redirect(route('admin.categorias.edit', $categoria));
        }
    }

    public function render(): \Illuminate\View\View
    {
        $parentOptions = ContentCategory::query()
            ->when($this->categoria, fn ($q) => $q->where('id', '!=', $this->categoria->id))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.categorias.categoria-form', [
            'itemTypes'     => ItemType::cases(),
            'parentOptions' => $parentOptions,
        ])->layout('admin.layouts.app', ['title' => $this->categoria ? 'Editar Categoria' : 'Nova Categoria']);
    }
}
