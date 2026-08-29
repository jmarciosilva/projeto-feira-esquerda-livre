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
                            @php $sh = $order->shippings->where('order_split_id', $split->id)->first(); @endphp
                            <div class="flex flex-wrap items-center gap-1 mb-1">
                                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full"
                                      style="{{ $split->status->badge() }}">
                                    {{ $split->expositor?->name ?? $split->expositor_name ?? 'Loja removida' }}
                                </span>
                                @if($sh)
                                <span class="inline-flex items-center gap-0.5 text-xs font-medium px-1.5 py-0.5 rounded-full
                                    @if($sh->status === \App\Enums\ShippingStatus::Delivered) bg-green-100 text-green-700
                                    @elseif($sh->status === \App\Enums\ShippingStatus::InTransit || $sh->status === \App\Enums\ShippingStatus::OutForDelivery) bg-indigo-100 text-indigo-700
                                    @elseif($sh->status === \App\Enums\ShippingStatus::Failed) bg-red-100 text-red-700
                                    @else bg-blue-100 text-blue-700 @endif">
                                    {{ $sh->status->icon() }}
                                    {{ $sh->status->label() }}
                                    @if($sh->tracking_code)
                                    — <a href="{{ route('rastreio.show', $sh->tracking_code) }}" target="_blank" class="underline">{{ $sh->tracking_code }}</a>
                                    @endif
                                </span>
                                @endif
                            </div>
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
                            @if($order->status === \App\Enums\OrderStatus::AguardandoPagamento)
                            <button wire:click="cancelar({{ $order->id }})"
                                    wire:confirm="Cancelar o pedido #{{ $order->reference }}? O estoque reservado volta para a loja."
                                    class="px-3 py-1.5 border border-red-200 text-red-700 rounded-lg text-xs font-semibold hover:bg-red-50">
                                Cancelar
                            </button>
                            @endif
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
