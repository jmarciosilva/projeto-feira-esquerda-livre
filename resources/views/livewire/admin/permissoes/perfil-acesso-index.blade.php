<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
        {{ session('success') }}
    </div>
    @endif

    <div class="mb-6">
        <h2 class="text-lg font-bold text-gray-900">Perfis de Acesso</h2>
        <p class="text-sm text-gray-500">Configure quais ações cada papel interno pode executar no painel.</p>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="lg:col-span-1">
            <x-admin.card title="Perfil">
                <div class="space-y-2">
                    @foreach($roles as $role)
                    <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer"
                           style="{{ $selectedRole === $role->name ? 'border-color:#1a472a; background:#f0fdf4;' : 'border-color:#e5e7eb;' }}">
                        <input type="radio" wire:model.live="selectedRole" value="{{ $role->name }}" class="w-4 h-4" style="accent-color:#1a472a;">
                        <span>
                            <span class="block text-sm font-semibold text-gray-900">{{ ucfirst($role->name) }}</span>
                            <span class="block text-xs text-gray-500">{{ $role->permissions_count ?? $role->permissions()->count() }} permissões</span>
                        </span>
                    </label>
                    @endforeach
                </div>
            </x-admin.card>
        </div>

        <div class="lg:col-span-3">
            <x-admin.card title="Permissões" description="Administrador é protegido e sempre possui acesso total.">
                @if($selectedRole === 'administrador')
                <div class="mb-4 p-4 rounded-xl border border-yellow-200 text-sm" style="background:#FFFBEB; color:#7A5C00;">
                    O perfil Administrador mantém todas as permissões por regra de segurança.
                </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($permissionGroups as $group => $permissions)
                    <div class="rounded-xl border border-gray-100 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3">{{ $group }}</p>
                        <div class="space-y-2">
                            @foreach($permissions as $permission)
                            <label class="flex items-center gap-3 text-sm text-gray-700">
                                <input
                                    type="checkbox"
                                    wire:model="selectedPermissions"
                                    value="{{ $permission->name }}"
                                    @disabled($selectedRole === 'administrador')
                                    class="w-4 h-4 rounded border-gray-300"
                                    style="accent-color:#1a472a;">
                                <span>{{ $permission->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-6 flex justify-end">
                    <x-admin.button type="submit" @disabled($selectedRole === 'administrador')>
                        Salvar Permissões
                    </x-admin.button>
                </div>
            </x-admin.card>
        </div>
    </form>
</div>
