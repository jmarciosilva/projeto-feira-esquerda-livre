<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Clientes</h2>
            <p class="text-sm text-gray-500">Compradores cadastrados no marketplace.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <x-admin.card>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
            <x-admin.input
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por nome, e-mail ou WhatsApp"
                label="Busca"
            />

            <x-admin.select wire:model.live="status" label="Status no marketplace">
                <option value="">Todos</option>
                <option value="active">Ativos</option>
                <option value="inactive">Inativos</option>
            </x-admin.select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                        <th class="py-3 pr-4">Cliente</th>
                        <th class="py-3 pr-4">WhatsApp</th>
                        <th class="py-3 pr-4">Pedidos</th>
                        <th class="py-3 pr-4">Endereços</th>
                        <th class="py-3 pr-4">Status Marketplace</th>
                        <th class="py-3 pr-4">Cadastro</th>
                        @can('clientes.gerenciar')
                        <th class="py-3 pr-4">Ações</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($clients as $client)
                    <tr>
                        <td class="py-3 pr-4">
                            <p class="font-semibold text-gray-900">{{ $client->name }}</p>
                            <p class="text-sm text-gray-500">{{ $client->email }}</p>
                            @if ($client->role && $client->role->isInternal())
                                <x-admin.badge color="yellow" class="mt-1">
                                    {{ $client->role->label() }}
                                </x-admin.badge>
                            @elseif ($client->role?->value === 'lojista')
                                <x-admin.badge color="brand" class="mt-1">
                                    Lojista
                                </x-admin.badge>
                            @endif
                        </td>
                        <td class="py-3 pr-4 text-sm text-gray-600">
                            {{ $client->whatsapp ?: '-' }}
                        </td>
                        <td class="py-3 pr-4">
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">
                                {{ $client->orders_count }}
                            </span>
                        </td>
                        <td class="py-3 pr-4">
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700">
                                {{ $client->addresses_count }}
                            </span>
                        </td>
                        <td class="py-3 pr-4">
                            @if ($client->customerProfile?->marketplace_status === \App\Enums\MarketplaceStatus::Active)
                                <x-admin.badge color="green">Ativo</x-admin.badge>
                            @else
                                <x-admin.badge color="gray">Inativo</x-admin.badge>
                            @endif
                        </td>
                        <td class="py-3 pr-4 text-sm text-gray-500">
                            {{ $client->created_at?->format('d/m/Y H:i') }}
                        </td>
                        @can('clientes.gerenciar')
                        <td class="py-3 pr-4">
                            @if ($client->customerProfile?->marketplace_status === \App\Enums\MarketplaceStatus::Active)
                                <button
                                    @click="$dispatch('open-confirm', {
                                        title: 'Inativar cliente no marketplace',
                                        message: 'O cliente ficará impedido de finalizar compras enquanto estiver inativo. O acesso ao painel não será afetado.',
                                        confirmText: 'Inativar',
                                        variant: 'danger',
                                        action: () => $wire.inactivateCustomer({{ $client->id }})
                                    })"
                                    class="text-sm font-medium text-red-600 hover:text-red-800 transition-colors"
                                >
                                    Inativar
                                </button>
                            @else
                                <button
                                    @click="$dispatch('open-confirm', {
                                        title: 'Reativar cliente no marketplace',
                                        message: 'O cliente voltará a poder realizar compras normalmente no marketplace.',
                                        confirmText: 'Reativar',
                                        variant: 'success',
                                        action: () => $wire.activateCustomer({{ $client->id }})
                                    })"
                                    class="text-sm font-medium text-green-700 hover:text-green-900 transition-colors"
                                >
                                    Reativar
                                </button>
                            @endif
                        </td>
                        @endcan
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-10 text-center text-sm text-gray-400">
                            Nenhum cliente encontrado.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $clients->links() }}
        </div>
    </x-admin.card>

    <x-admin.confirm-modal />
</div>
