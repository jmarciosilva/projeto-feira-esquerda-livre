<div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-base">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider">Pedido</th>
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Entrega</th>
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Total</th>
                        <th class="text-center py-4 px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="text-right py-4 px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($orders as $order)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-4">
                            <p class="font-semibold text-gray-900">#{{ $order->reference }}</p>
                            <p class="text-xs text-gray-400">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                        </td>
                        <td class="py-4 px-4 hidden sm:table-cell">
                            <span class="text-sm text-gray-600">{{ $order->delivery_type->emoji() }} {{ $order->delivery_type->label() }}</span>
                        </td>
                        <td class="py-4 px-4 hidden sm:table-cell">
                            <span class="font-bold" style="color:#C47A00;">R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}</span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <x-admin.badge :color="$order->status->color()">{{ $order->status->label() }}</x-admin.badge>
                        </td>
                        <td class="py-4 px-4 text-right">
                            <a href="{{ route('pedido.show', $order->reference) }}" target="_blank"
                               class="px-3 py-2 rounded-lg text-sm font-semibold border-2 transition-colors"
                               style="border-color:#E8A000; color:#C47A00; min-height:40px; display:inline-flex; align-items:center;">
                                Ver detalhes
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-16 text-center">
                            <div class="text-5xl mb-4">📦</div>
                            <p class="text-lg font-semibold text-gray-500">Você ainda não fez nenhum pedido.</p>
                            <a href="{{ url('/') }}" class="mt-4 inline-block px-6 py-3 rounded-xl text-white font-bold" style="background-color:#E8A000;">
                                Explorar lojas
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
        <div class="px-4 py-4 border-t border-gray-100">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</div>
