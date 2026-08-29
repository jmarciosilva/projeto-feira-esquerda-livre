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
                        <th class="text-center py-4 px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider">Pagamento</th>
                        <th class="text-center py-4 px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Envio</th>
                        <th class="text-right py-4 px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($splits as $split)
                    @php $order = $split->order; $shipping = $split->shipping; @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-4">
                            <p class="font-semibold text-gray-900">#{{ $order->reference }}</p>
                            <p class="text-xs text-gray-400">{{ $order->customer_name }} · {{ $order->created_at->format('d/m/Y H:i') }}</p>
                            <div class="mt-1.5 space-y-0.5">
                                @if($order->customer_whatsapp)
                                <a href="https://wa.me/55{{ preg_replace('/\D/', '', $order->customer_whatsapp) }}"
                                   target="_blank"
                                   class="flex items-center gap-1 text-xs font-semibold" style="color:#16a34a;">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347M12 21.785h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26C2.117 6.443 6.552 2.009 12.004 2.009c2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884"/>
                                    </svg>
                                    {{ $order->customer_whatsapp }}
                                </a>
                                @endif
                                @if($order->customer_email)
                                <p class="text-xs text-gray-400 truncate max-w-[180px]">✉ {{ $order->customer_email }}</p>
                                @endif
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            @php $itemsOverflow = $order->items->count() - 5; @endphp
                            <div class="space-y-0.5 mb-2" @if($itemsOverflow > 0) x-data="{ expanded: false }" @endif>
                                @foreach($order->items->take(5) as $item)
                                <p class="text-sm text-gray-700 leading-snug">
                                    <span class="font-semibold">{{ $item->quantity }}×</span> {{ $item->product_name }}
                                </p>
                                @endforeach

                                @if($itemsOverflow > 0)
                                <div x-show="expanded" x-cloak class="space-y-0.5">
                                    @foreach($order->items->slice(5) as $item)
                                    <p class="text-sm text-gray-700 leading-snug">
                                        <span class="font-semibold">{{ $item->quantity }}×</span> {{ $item->product_name }}
                                    </p>
                                    @endforeach
                                </div>
                                <button type="button" @click="expanded = !expanded"
                                        class="text-xs font-semibold" style="color:#C47A00;"
                                        x-text="expanded ? 'Ver menos' : '+ {{ $itemsOverflow }} ' + '{{ $itemsOverflow === 1 ? 'item' : 'itens' }}'">
                                </button>
                                @endif
                            </div>
                            <div class="text-xs rounded-lg px-2 py-1 inline-block" style="background:#FDF8DC; color:#5C4500;">
                                @if($order->delivery_type->value === 'retirada')
                                    {{ $order->delivery_type->emoji() }} Retirada no local
                                @else
                                    {{ $order->delivery_type->emoji() }}
                                    {{ $order->address_rua }}, {{ $order->address_numero }}
                                    @if($order->address_complemento) - {{ $order->address_complemento }} @endif
                                    — {{ $order->address_bairro }}, {{ $order->address_cidade }}/{{ $order->address_estado }}
                                @endif
                            </div>
                        </td>
                        <td class="py-4 px-4 hidden sm:table-cell">
                            <span class="font-bold text-lg" style="color: #C47A00;">R$ {{ number_format((float) $split->gross_amount, 2, ',', '.') }}</span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold"
                                  style="{{ $split->status->badge() }}">
                                {{ $split->status->label() }}
                            </span>
                        </td>
                        <td class="py-4 px-4 text-center hidden md:table-cell">
                            @if($shipping)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold
                                    @if($shipping->status === \App\Enums\ShippingStatus::Delivered) bg-green-100 text-green-800
                                    @elseif($shipping->status === \App\Enums\ShippingStatus::OutForDelivery) bg-yellow-100 text-yellow-800
                                    @elseif($shipping->status === \App\Enums\ShippingStatus::InTransit) bg-indigo-100 text-indigo-800
                                    @elseif($shipping->status === \App\Enums\ShippingStatus::Failed) bg-red-100 text-red-800
                                    @else bg-blue-100 text-blue-800
                                    @endif">
                                    {{ $shipping->status->icon() }} {{ $shipping->status->label() }}
                                </span>
                                @if($shipping->tracking_code)
                                <p class="text-xs text-gray-400 mt-1 font-mono">{{ $shipping->tracking_code }}</p>
                                @endif
                                @if($shipping->tracking_code)
                                <a href="{{ route('rastreio.show', $shipping->tracking_code) }}" target="_blank"
                                   class="text-xs font-semibold mt-1 inline-block" style="color:#1a472a;">
                                    Ver rastreio ↗
                                </a>
                                @endif
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-right">
                            <div class="flex flex-col items-end gap-2">
                                @if($split->status->value === 'pendente')
                                <button wire:click="confirmar({{ $split->id }})"
                                        @click="$dispatch('open-confirm', {
                                            title: 'Confirmar pagamento',
                                            message: 'Confirmar que o pagamento do pedido #{{ $order->reference }} foi recebido?',
                                            confirmText: 'Confirmar',
                                            variant: 'success',
                                            action: () => $wire.confirmar({{ $split->id }})
                                        })"
                                        class="px-3 py-2 rounded-lg text-sm font-semibold border-2 transition-colors"
                                        style="border-color: #16a34a; color: #16a34a; min-height: 40px;">
                                    Confirmar Pagamento
                                </button>
                                @else
                                <span class="text-xs text-gray-400">Pago {{ $split->confirmed_at?->format('d/m') }}</span>
                                @endif

                                @if($split->status->value === 'confirmado' && (! $shipping || ! $shipping->status->isTerminal()))
                                <button wire:click="openShipModal({{ $split->id }})"
                                        class="px-3 py-2 rounded-lg text-sm font-semibold border-2 transition-colors"
                                        style="border-color: #1a472a; color: #1a472a; min-height: 40px;">
                                    {{ $shipping ? 'Atualizar Envio' : 'Marcar Enviado' }}
                                </button>
                                @endif

                                <a href="{{ route('lojista.pedidos.chat', $split->id) }}"
                                   class="px-3 py-2 rounded-lg text-sm font-semibold border-2 transition-colors inline-flex items-center gap-1.5"
                                   style="border-color: #E8A000; color: #C47A00; min-height: 40px;">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    Chat
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-16 text-center">
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

    {{-- Modal: Marcar como Enviado --}}
    @if($showShipModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8l1.343 9.372A2 2 0 008.33 19h7.34a2 2 0 001.987-1.628L19 8M10 12h4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Marcar pedido como enviado</h3>
                    <p class="text-xs text-gray-500">O cliente receberá uma notificação por e-mail com o código de rastreio.</p>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Transportadora <span class="text-red-500">*</span></label>
                    <select wire:model="carrier"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a472a]">
                        <option value="">Selecione...</option>
                        <option value="Correios">Correios</option>
                        <option value="Jadlog">Jadlog</option>
                        <option value="Azul Cargo">Azul Cargo</option>
                        <option value="Sequoia">Sequoia</option>
                        <option value="Total Express">Total Express</option>
                        <option value="Loggi">Loggi</option>
                        <option value="Outro">Outro</option>
                    </select>
                    @error('carrier') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Código de rastreio <span class="text-red-500">*</span></label>
                    <input wire:model="trackingCode" type="text" placeholder="Ex: BR000000000BR"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm font-mono uppercase focus:outline-none focus:ring-2 focus:ring-[#1a472a]">
                    @error('trackingCode') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Data de envio <span class="text-red-500">*</span></label>
                    <input wire:model="shippedAtDate" type="date" max="{{ now()->format('Y-m-d') }}"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a472a]">
                    @error('shippedAtDate') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex gap-3 justify-end mt-6">
                <button wire:click="closeShipModal"
                        class="px-4 py-2.5 rounded-xl text-sm font-semibold border-2 border-gray-200 text-gray-600 hover:bg-gray-50">
                    Cancelar
                </button>
                <button wire:click="markAsShipped"
                        class="px-5 py-2.5 rounded-xl text-sm font-bold text-white"
                        style="background:#1a472a;">
                    Confirmar Envio
                </button>
            </div>
        </div>
    </div>
    @endif

    <x-admin.confirm-modal />
</div>
