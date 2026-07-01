<?php

namespace App\Livewire\Admin\Permissoes;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PerfilAcessoIndex extends Component
{
    public string $selectedRole = 'gerente';

    public array $selectedPermissions = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('permissoes.gerenciar'), 403);
        $this->loadRolePermissions();
    }

    public function updatedSelectedRole(): void
    {
        $this->loadRolePermissions();
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('permissoes.gerenciar'), 403);

        if ($this->selectedRole === 'administrador') {
            session()->flash('success', 'O perfil Administrador sempre mantém acesso total.');

            return;
        }

        $role = Role::findByName($this->selectedRole, 'web');
        $permissions = Permission::whereIn('name', $this->selectedPermissions)->pluck('name')->all();

        $role->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        session()->flash('success', 'Permissões do perfil atualizadas.');
    }

    public function render(): View
    {
        return view('livewire.admin.permissoes.perfil-acesso-index', [
            'roles' => $this->roles(),
            'permissionGroups' => $this->permissionGroups(),
        ])->layout('admin.layouts.app', ['title' => 'Perfis de Acesso']);
    }

    private function loadRolePermissions(): void
    {
        $role = Role::where('name', $this->selectedRole)->first();

        $this->selectedPermissions = $role
            ? $role->permissions()->pluck('name')->values()->all()
            : [];
    }

    /**
     * @return Collection<int, Role>
     */
    private function roles(): Collection
    {
        $order = array_keys(RolePermissionSeeder::rolePermissions());

        return Role::whereIn('name', $order)
            ->get()
            ->sortBy(fn (Role $role) => array_search($role->name, $order, true))
            ->values();
    }

    /**
     * @return Collection<string, Collection<int, Permission>>
     */
    private function permissionGroups(): Collection
    {
        return Permission::query()
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission) => str($permission->name)->before('.')->toString());
    }
}
