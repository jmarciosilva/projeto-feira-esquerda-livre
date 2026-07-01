<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
        {{ session('success') }}
    </div>
    @endif

    @error('users')
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
        {{ $message }}
    </div>
    @enderror

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Usuários Internos</h2>
            <p class="text-sm text-gray-500">Equipe com acesso ao painel administrativo.</p>
        </div>

        @can('usuarios.gerenciar')
        <a href="{{ route('admin.usuarios.create') }}">
            <x-admin.button>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Novo Usuário
            </x-admin.button>
        </a>
        @endcan
    </div>

    <x-admin.card>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
            <x-admin.input
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por nome ou e-mail"
                label="Busca"
            />

            <x-admin.select wire:model.live="role" label="Papel">
                <option value="">Todos</option>
                @foreach($roles as $roleOption)
                <option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</option>
                @endforeach
            </x-admin.select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                        <th class="py-3 pr-4">Usuário</th>
                        <th class="py-3 pr-4">Papel</th>
                        <th class="py-3 pr-4">Status</th>
                        <th class="py-3 pr-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($users as $internalUser)
                    <tr>
                        <td class="py-3 pr-4">
                            <p class="font-semibold text-gray-900">{{ $internalUser->name }}</p>
                            <p class="text-sm text-gray-500">{{ $internalUser->email }}</p>
                        </td>
                        <td class="py-3 pr-4">
                            <div class="flex flex-wrap gap-1.5">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700">
                                    {{ $internalUser->role?->label() }}
                                </span>
                                @if($internalUser->customerProfile)
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">
                                    Cliente
                                </span>
                                @endif
                                @if($internalUser->expositor)
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-orange-50 text-orange-700">
                                    Lojista
                                </span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3 pr-4">
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $internalUser->is_active ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $internalUser->is_active ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="py-3 pr-4">
                            <div class="flex justify-end gap-2">
                                @can('usuarios.gerenciar')
                                <a href="{{ route('admin.usuarios.edit', $internalUser) }}">
                                    <x-admin.button variant="secondary" size="sm">Editar</x-admin.button>
                                </a>

                                <button
                                    @click="$dispatch('open-confirm', {
                                        title: 'Redefinir senha',
                                        message: 'Uma nova senha temporária será gerada e enviada para {{ $internalUser->email }} por e-mail.',
                                        confirmText: 'Redefinir',
                                        variant: 'warning',
                                        action: () => $wire.resetPassword({{ $internalUser->id }})
                                    })"
                                    class="inline-flex items-center gap-2 font-medium border rounded-lg transition-colors duration-150 px-3 py-1.5 text-xs bg-white hover:bg-gray-50 text-gray-700 border-gray-300"
                                >
                                    Redefinir senha
                                </button>

                                <button
                                    @click="$dispatch('open-confirm', {
                                        title: '{{ $internalUser->is_active ? 'Desativar usuário' : 'Reativar usuário' }}',
                                        message: '{{ $internalUser->is_active
                                            ? 'O usuário perderá o acesso ao painel administrativo imediatamente.'
                                            : 'O usuário voltará a ter acesso ao painel administrativo.' }}',
                                        confirmText: '{{ $internalUser->is_active ? 'Desativar' : 'Reativar' }}',
                                        variant: '{{ $internalUser->is_active ? 'danger' : 'success' }}',
                                        action: () => $wire.toggleActive({{ $internalUser->id }})
                                    })"
                                    class="inline-flex items-center gap-2 font-medium border border-transparent rounded-lg transition-colors duration-150 px-3 py-1.5 text-xs {{ $internalUser->is_active ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-[#52b788] hover:bg-[#2d6a4f] text-white' }}"
                                >
                                    {{ $internalUser->is_active ? 'Desativar' : 'Ativar' }}
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-10 text-center text-sm text-gray-400">
                            Nenhum usuário interno encontrado.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </x-admin.card>

    <x-admin.confirm-modal />
</div>
