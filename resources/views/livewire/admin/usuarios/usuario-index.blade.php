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
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700">
                                {{ $internalUser->role?->label() }}
                            </span>
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

                                <x-admin.button
                                    variant="secondary"
                                    size="sm"
                                    wire:click="resetPassword({{ $internalUser->id }})"
                                    wire:confirm="Redefinir a senha deste usuário e enviar por e-mail?">
                                    Redefinir senha
                                </x-admin.button>

                                <x-admin.button
                                    variant="{{ $internalUser->is_active ? 'danger' : 'success' }}"
                                    size="sm"
                                    wire:click="toggleActive({{ $internalUser->id }})">
                                    {{ $internalUser->is_active ? 'Desativar' : 'Ativar' }}
                                </x-admin.button>
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
</div>
