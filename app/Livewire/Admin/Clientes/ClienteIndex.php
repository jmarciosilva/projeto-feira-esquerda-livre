<?php

namespace App\Livewire\Admin\Clientes;

use App\Enums\MarketplaceStatus;
use App\Livewire\Admin\Concerns\AuthorizesAdminActions;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ClienteIndex extends Component
{
    use AuthorizesAdminActions;
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

    public function inactivateCustomer(int $userId): void
    {
        $this->authorizeAdminAction('clientes.gerenciar');

        $user = User::findOrFail($userId);

        CustomerProfile::updateOrCreate(
            ['user_id' => $userId],
            ['marketplace_status' => MarketplaceStatus::Inactive]
        );

        session()->flash('success', "Cliente {$user->name} inativado no marketplace.");
    }

    public function activateCustomer(int $userId): void
    {
        $this->authorizeAdminAction('clientes.gerenciar');

        $user = User::findOrFail($userId);

        CustomerProfile::updateOrCreate(
            ['user_id' => $userId],
            ['marketplace_status' => MarketplaceStatus::Active]
        );

        session()->flash('success', "Cliente {$user->name} reativado no marketplace.");
    }

    public function render(): View
    {
        $clients = User::query()
            ->whereHas('customerProfile')
            ->with('customerProfile')
            ->withCount(['addresses', 'orders'])
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('whatsapp', 'like', "%{$this->search}%");
            }))
            ->when($this->status === 'active', fn ($query) => $query->whereHas(
                'customerProfile',
                fn ($q) => $q->where('marketplace_status', MarketplaceStatus::Active->value)
            ))
            ->when($this->status === 'inactive', fn ($query) => $query->whereHas(
                'customerProfile',
                fn ($q) => $q->where('marketplace_status', MarketplaceStatus::Inactive->value)
            ))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.clientes.cliente-index', [
            'clients' => $clients,
        ])->layout('admin.layouts.app', ['title' => 'Clientes']);
    }
}
