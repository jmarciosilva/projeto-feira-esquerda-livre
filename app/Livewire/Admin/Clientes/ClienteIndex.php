<?php

namespace App\Livewire\Admin\Clientes;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ClienteIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('clientes.visualizar'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $clients = User::query()
            ->where('role', UserRole::User->value)
            ->withCount(['addresses', 'orders'])
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('whatsapp', 'like', "%{$this->search}%");
            }))
            ->when($this->status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.clientes.cliente-index', [
            'clients' => $clients,
        ])->layout('admin.layouts.app', ['title' => 'Clientes']);
    }
}
