<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-blue-800 text-xs">
        🔧 MVP em modo manual: o pagamento é confirmado por loja no painel do lojista. Use o status abaixo apenas para acompanhamento geral do pedido.
    </div>

    {{-- Filtros --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por referência ou cliente..."
               class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
        <select wire:model.live="filterStatus" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
            <option value="">Todos os status</option>
            @foreach($statuses as $status)
            <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>

    <x-admin.card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Pedido</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Lojas</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Total</th>
                        <th class="text-center py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="text-right py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($orders as $order)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-2">
                            <p class="font-medium text-gray-900">#{{ $order->reference }}</p>
                            <p class="text-xs text-gray-500">{{ $order->customer_name }} · {{ $order->created_at->format('d/m/Y H:i') }}</p>
                            <p class="text-xs text-gray-400">{{ $order->items_count }} {{ $order->items_count === 1 ? 'item' : 'itens' }}</p>
                        </td>
                        <td class="py-3 px-2">
                            @foreach($order->splits as $split)
                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full mb-1"
                                  style="{{ $split->status->value === 'confirmado' ? 'background:#dcfce7; color:#166534;' : 'background:#fef9c3; color:#854d0e;' }}">
                                {{ $split->expositor?->name }}
                            </span><br>
                            @endforeach
                        </td>
                        <td class="py-3 px-2 hidden sm:table-cell">
                            <span class="font-bold" style="color: #C47A00;">R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}</span>
                        </td>
                        <td class="py-3 px-2 text-center">
                            <x-admin.badge :color="$order->status->color()">{{ $order->status->label() }}</x-admin.badge>
                        </td>
                        <td class="py-3 px-2 text-right">
                            @can('pedidos.atualizar_status')
                            <select wire:change="updateStatus({{ $order->id }}, $event.target.value)"
                                    class="px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-[#52b788]">
                                @foreach($statuses as $status)
                                <option value="{{ $status->value }}" @selected($order->status === $status)>{{ $status->label() }}</option>
                                @endforeach
                            </select>
                            @endcan
                            <a href="{{ route('pedido.show', $order->reference) }}" target="_blank"
                               class="ml-2 text-xs font-semibold text-gray-500 hover:text-gray-700">Ver</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-16 text-center">
                            <div class="text-5xl mb-4">📦</div>
                            <p class="text-base font-semibold text-gray-500">Nenhum pedido encontrado.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
        <div class="pt-4">{{ $orders->links() }}</div>
        @endif
    </x-admin.card>
</div>
