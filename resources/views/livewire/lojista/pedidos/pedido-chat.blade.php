<div>
    {{-- Back link + order summary --}}
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('lojista.pedidos.index') }}"
           class="flex items-center gap-1 text-sm font-medium hover:underline"
           style="color:#5C4500;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Voltar aos Pedidos
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Order summary card --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
                <h2 class="font-bold text-base mb-3" style="color:#3D3000;">
                    Pedido #{{ $split->order->reference }}
                </h2>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between gap-2">
                        <span class="text-gray-500">Cliente</span>
                        <span class="font-medium text-gray-800 text-right">{{ $split->order->customer_name }}</span>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span class="text-gray-500">Data</span>
                        <span class="font-medium text-gray-800">{{ $split->order->created_at->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span class="text-gray-500">Situação</span>
                        <span class="font-semibold"
                              style="{{ $split->status->value === 'confirmado' ? 'color:#166534;' : 'color:#854d0e;' }}">
                            {{ $split->status->label() }}
                        </span>
                    </div>
                    <div class="flex justify-between gap-2 pt-2 border-t border-gray-100">
                        <span class="text-gray-500 font-semibold">Valor</span>
                        <span class="font-bold text-base" style="color:#C47A00;">
                            R$ {{ number_format($split->gross_amount, 2, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-5">
                <h3 class="font-semibold text-sm text-gray-700 mb-3">Itens do pedido</h3>
                <div class="space-y-1.5">
                    @foreach($split->order->items->where('expositor_id', $split->expositor_id) as $item)
                    <div class="flex justify-between gap-2 text-sm">
                        <span class="text-gray-600">{{ $item->quantity }}x {{ $item->product_name }}</span>
                        <span class="font-medium text-gray-800 flex-shrink-0">R$ {{ number_format($item->total_price, 2, ',', '.') }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Chat --}}
        <div class="lg:col-span-2">
            <livewire:order-chat :split="$split" />
        </div>
    </div>
</div>
