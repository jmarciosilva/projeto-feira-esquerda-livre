<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-base font-medium">{{ session('success') }}</div>
    @endif

    <div class="flex items-center gap-2 flex-wrap mb-6">
        <button wire:click="$set('filterStatus', '')"
                class="px-4 py-2 rounded-full text-sm font-semibold border-2 transition-colors"
                style="{{ $filterStatus === '' ? 'background:#E8A000; border-color:#E8A000; color:#fff;' : 'background:#fff; border-color:#e5e7eb; color:#6b7280;' }}">
            Todos
        </button>
        <button wire:click="$set('filterStatus', 'pendente')"
                class="px-4 py-2 rounded-full text-sm font-semibold border-2 transition-colors"
                style="{{ $filterStatus === 'pendente' ? 'background:#E8A000; border-color:#E8A000; color:#fff;' : 'background:#fff; border-color:#e5e7eb; color:#6b7280;' }}">
            Aguardando confirmação
        </button>
        <button wire:click="$set('filterStatus', 'confirmado')"
                class="px-4 py-2 rounded-full text-sm font-semibold border-2 transition-colors"
                style="{{ $filterStatus === 'confirmado' ? 'background:#E8A000; border-color:#E8A000; color:#fff;' : 'background:#fff; border-color:#e5e7eb; color:#6b7280;' }}">
            Confirmados
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-base">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider">Pedido</th>
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider">Itens</th>
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Valor</th>
                        <th class="text-center py-4 px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="text-right py-4 px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($splits as $split)
                    @php $order = $split->order; @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-4">
                            <p class="font-semibold text-gray-900">#{{ $order->reference }}</p>
                            <p class="text-xs text-gray-400">{{ $order->customer_name }} · {{ $order->created_at->format('d/m/Y H:i') }}</p>
                        </td>
                        <td class="py-4 px-4">
                            <p class="text-sm text-gray-600">{{ $order->items->count() }} {{ $order->items->count() === 1 ? 'item' : 'itens' }}</p>
                        </td>
                        <td class="py-4 px-4 hidden sm:table-cell">
                            <span class="font-bold text-lg" style="color: #C47A00;">R$ {{ number_format((float) $split->gross_amount, 2, ',', '.') }}</span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold"
                                  style="{{ $split->status->value === 'confirmado' ? 'background:#dcfce7; color:#166534;' : 'background:#fef9c3; color:#854d0e;' }}">
                                {{ $split->status->label() }}
                            </span>
                        </td>
                        <td class="py-4 px-4 text-right">
                            @if($split->status->value === 'pendente')
                            <button wire:click="confirmar({{ $split->id }})"
                                    wire:confirm="Confirmar que o pagamento do pedido #{{ $order->reference }} foi recebido?"
                                    class="px-3 py-2 rounded-lg text-sm font-semibold border-2 transition-colors"
                                    style="border-color: #16a34a; color: #16a34a; min-height: 40px;">
                                Confirmar Pagamento
                            </button>
                            @else
                            <span class="text-xs text-gray-400">Confirmado em {{ $split->confirmed_at?->format('d/m/Y H:i') }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-16 text-center">
                            <div class="text-5xl mb-4">📦</div>
                            <p class="text-lg font-semibold text-gray-500">Nenhum pedido encontrado.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($splits->hasPages())
        <div class="px-4 py-4 border-t border-gray-100">
            {{ $splits->links() }}
        </div>
        @endif
    </div>
</div>
