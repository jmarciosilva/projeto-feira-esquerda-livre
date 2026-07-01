<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Clientes</h2>
            <p class="text-sm text-gray-500">Compradores cadastrados na plataforma.</p>
        </div>
    </div>

    <x-admin.card>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
            <x-admin.input
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por nome, e-mail ou WhatsApp"
                label="Busca"
            />

            <x-admin.select wire:model.live="status" label="Status">
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
                        <th class="py-3 pr-4">Status</th>
                        <th class="py-3 pr-4">Cadastro</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($clients as $client)
                    <tr>
                        <td class="py-3 pr-4">
                            <p class="font-semibold text-gray-900">{{ $client->name }}</p>
                            <p class="text-sm text-gray-500">{{ $client->email }}</p>
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
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $client->is_active ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $client->is_active ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="py-3 pr-4 text-sm text-gray-500">
                            {{ $client->created_at?->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-sm text-gray-400">
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
</div>
