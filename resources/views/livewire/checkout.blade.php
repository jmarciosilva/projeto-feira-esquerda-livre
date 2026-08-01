@php
    $inputClass = fn (string $field) => 'w-full px-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors '
        . ($errors->has($field) ? 'border-red-400' : 'border-gray-200 focus:border-yellow-400');
    $itemCount = $grouped->sum(fn ($g) => $g->sum('quantity'));
@endphp

<div>

{{-- Modal de autenticação para convidados --}}
@if($showAuthModal)
<div class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,0.7);">
    <div class="rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border"
         style="background:#FFFDF2; border-color:#F0D060;"
         x-data="{ tab: '{{ old('_form', 'login') }}' }">

        <div class="px-8 pt-7 pb-5 text-center" style="background:linear-gradient(180deg, #FFF4B8 0%, #FFFDF2 100%);">
            @if($settings->logo_path)
                <img src="{{ Storage::url($settings->logo_path) }}"
                     alt=""
                     class="h-14 w-auto object-contain mx-auto mb-3"
                     loading="lazy">
            @else
                <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl font-black mb-3"
                     style="background:#F4E294; color:#3D3000;">F</div>
            @endif
            <h2 class="text-xl font-black" style="color:#3D3000;">Para finalizar seu pedido</h2>
            <p class="text-sm mt-1" style="color:#7A5C00;">Entre na sua conta ou crie uma nova em segundos</p>
        </div>

        <div class="mx-6 flex rounded-xl p-1" style="background:#F8EDB8;">
            <button type="button" @click="tab = 'login'"
                    class="flex-1 rounded-lg py-2.5 text-sm font-bold transition-colors"
                    :style="tab === 'login' ? 'color:#3D3000; background:#fff; box-shadow:0 1px 6px rgba(61,48,0,0.10);' : 'color:#7A5C00;'">
                Já tenho conta
            </button>
            <button type="button" @click="tab = 'register'"
                    class="flex-1 rounded-lg py-2.5 text-sm font-bold transition-colors"
                    :style="tab === 'register' ? 'color:#3D3000; background:#fff; box-shadow:0 1px 6px rgba(61,48,0,0.10);' : 'color:#7A5C00;'">
                Criar conta
            </button>
        </div>

        @if($errors->any())
        <div class="mx-6 mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div x-show="tab === 'login'" class="px-6 py-5">
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="_form" value="login">
                <input type="hidden" name="redirect_to" value="{{ route('checkout') }}">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2"
                           style="border-color:#D9C16A; --tw-ring-color:#E8A000;">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <input type="password" name="password" required
                           class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2"
                           style="border-color:#D9C16A; --tw-ring-color:#E8A000;">
                </div>
                <button type="submit"
                        class="w-full py-3 rounded-xl text-sm font-bold shadow-sm transition-opacity hover:opacity-90"
                        style="background:#E8A000; color:#fff;">
                    Entrar e continuar
                </button>
            </form>
        </div>

        <div x-show="tab === 'register'" class="px-6 py-5">
            <form method="POST" action="{{ route('register') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="_form" value="register">
                <input type="hidden" name="redirect_to" value="{{ route('checkout') }}">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome completo</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2"
                           style="border-color:#D9C16A; --tw-ring-color:#E8A000;">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2"
                           style="border-color:#D9C16A; --tw-ring-color:#E8A000;">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp</label>
                    <input type="text" name="whatsapp" value="{{ old('whatsapp') }}" required
                           class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2"
                           style="border-color:#D9C16A; --tw-ring-color:#E8A000;"
                           placeholder="(11) 91234-5678">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                        <input type="password" name="password" required placeholder="Mín. 8 caracteres"
                               class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2"
                               style="border-color:#D9C16A; --tw-ring-color:#E8A000;">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2"
                               style="border-color:#D9C16A; --tw-ring-color:#E8A000;">
                    </div>
                </div>
                <button type="submit"
                        class="w-full py-3 rounded-xl text-sm font-bold shadow-sm transition-opacity hover:opacity-90"
                        style="background:#E8A000; color:#fff;">
                    Criar conta e continuar
                </button>
            </form>
        </div>

    </div>
</div>
@endif

    @if(session('error'))
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    @if($total <= 0)
    <div class="rounded-2xl border border-gray-200 p-10 text-center bg-white">
        <div class="text-5xl mb-4">🛒</div>
        <p class="text-lg font-semibold text-gray-700 mb-2">Seu carrinho está vazio</p>
        <p class="text-sm text-gray-400 mb-6">Adicione produtos antes de continuar para o checkout.</p>
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-white" style="background:#3D3000;">
            ← Voltar às lojas
        </a>
    </div>
    @else
    <form wire:submit="confirmar" class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Coluna principal --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Produtos no carrinho --}}
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-bold text-base" style="color:#3D3000;">
                        Produtos no Carrinho
                    </h2>
                    <span class="text-sm text-gray-400 font-medium">
                        {{ $itemCount }} {{ $itemCount === 1 ? 'item' : 'itens' }}
                    </span>
                </div>

                @foreach($grouped as $expositorId => $storeItems)
                @php $firstItem = $storeItems->first(); @endphp
                <div class="{{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="px-6 pt-4 pb-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">
                            {{ $firstItem->expositor?->name ?? 'Loja' }}
                        </p>
                    </div>
                    <div class="px-6 pb-4 divide-y divide-gray-50">
                        @foreach($storeItems as $item)
                        @php
                            $imgs  = $item->product?->images ?? [];
                            $thumb = !empty($imgs[0]['thumb'])
                                ? \Storage::url($imgs[0]['thumb'])
                                : ($item->product?->image_path ? \Storage::url($item->product->image_path) : null);
                        @endphp
                        <div class="flex gap-3 sm:gap-4 items-center py-3" wire:key="item-{{ $item->id }}">

                            {{-- Thumbnail --}}
                            @if($thumb)
                            <img src="{{ $thumb }}" alt="{{ $item->product?->name }}"
                                 class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl object-cover flex-shrink-0 border border-gray-100">
                            @else
                            <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl flex-shrink-0 flex items-center justify-center text-xl border border-gray-100"
                                 style="background: linear-gradient(135deg, #F4E294, #E8A000);">🛍</div>
                            @endif

                            {{-- Nome + preço unitário --}}
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900 text-sm leading-tight">{{ $item->product?->name }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    R$ {{ number_format((float) $item->price_snapshot, 2, ',', '.') }} cada
                                </p>
                            </div>

                            {{-- Controles de quantidade --}}
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <button type="button"
                                        wire:click="updateQty({{ $item->id }}, {{ max(1, $item->quantity - 1) }})"
                                        wire:loading.attr="disabled"
                                        @disabled($item->quantity <= 1)
                                        class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50 font-bold transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                                    −
                                </button>
                                <span class="w-6 text-center text-sm font-bold text-gray-800">{{ $item->quantity }}</span>
                                <button type="button"
                                        wire:click="updateQty({{ $item->id }}, {{ $item->quantity + 1 }})"
                                        wire:loading.attr="disabled"
                                        class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50 font-bold transition-colors">
                                    +
                                </button>
                            </div>

                            {{-- Subtotal --}}
                            <div class="flex-shrink-0 text-right w-20 hidden sm:block">
                                <p class="font-bold text-sm" style="color:#3D3000;">
                                    R$ {{ number_format($item->subtotal(), 2, ',', '.') }}
                                </p>
                            </div>

                            {{-- Remover --}}
                            <button type="button"
                                    wire:click="removeItem({{ $item->id }})"
                                    wire:loading.attr="disabled"
                                    class="flex-shrink-0 text-red-400 hover:text-red-600 transition-colors" title="Remover item">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach

                {{-- Subtotal mobile (apenas em telas pequenas) --}}
                <div class="px-6 py-4 border-t border-gray-100 flex justify-between items-center sm:hidden">
                    <span class="text-sm font-medium text-gray-600">Subtotal</span>
                    <span class="font-bold text-base" style="color:#3D3000;">R$ {{ number_format($total, 2, ',', '.') }}</span>
                </div>
            </div>

            {{-- Seus dados --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="font-bold text-base mb-4" style="color:#3D3000;">Seus dados</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome completo</label>
                        <input type="text" wire:model="customer_name" class="{{ $inputClass('customer_name') }}">
                        @error('customer_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp</label>
                            <input type="text" wire:model="customer_whatsapp" placeholder="(11) 91234-5678" class="{{ $inputClass('customer_whatsapp') }}">
                            @error('customer_whatsapp') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail (opcional)</label>
                            <input type="email" wire:model="customer_email" class="{{ $inputClass('customer_email') }}">
                            @error('customer_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tipo de entrega --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="font-bold text-base mb-4" style="color:#3D3000;">Como você quer receber?</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <button type="button" wire:click="$set('delivery_type', 'retirada')"
                            class="p-4 rounded-xl border-2 text-left transition-colors"
                            style="{{ $delivery_type === 'retirada' ? 'border-color:#E8A000; background:#FFFBEB;' : 'border-color:#e5e7eb;' }}">
                        <span class="text-2xl">📦</span>
                        <p class="font-bold mt-2" style="color:#3D3000;">Retirar no local</p>
                        <p class="text-xs text-gray-500 mt-1">Sem necessidade de endereço — combine com o lojista pelo WhatsApp.</p>
                    </button>
                    <button type="button" wire:click="$set('delivery_type', 'entrega')"
                            class="p-4 rounded-xl border-2 text-left transition-colors"
                            style="{{ $delivery_type === 'entrega' ? 'border-color:#E8A000; background:#FFFBEB;' : 'border-color:#e5e7eb;' }}">
                        <span class="text-2xl">🚚</span>
                        <p class="font-bold mt-2" style="color:#3D3000;">Receber em casa</p>
                        <p class="text-xs text-gray-500 mt-1">Escolha um dos seus endereços salvos ou cadastre um novo.</p>
                    </button>
                </div>
            </div>

            {{-- Endereço ou info de retirada --}}
            @if($delivery_type === 'entrega')
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="font-bold text-base mb-4" style="color:#3D3000;">Endereço de entrega</h2>

                @error('customer_address_id') <p class="mb-3 text-sm text-red-600">{{ $message }}</p> @enderror

                @if($addresses->isNotEmpty())
                <div class="space-y-2 mb-4">
                    @foreach($addresses as $addr)
                    <label class="flex items-start gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors"
                           style="{{ $customer_address_id === $addr->id ? 'border-color:#E8A000; background:#FFFBEB;' : 'border-color:#e5e7eb;' }}">
                        <input type="radio" wire:model="customer_address_id" value="{{ $addr->id }}" class="mt-1 w-4 h-4">
                        <span>
                            <span class="font-semibold text-gray-900 block">{{ $addr->label }}{{ $addr->is_default ? ' (padrão)' : '' }}</span>
                            <span class="text-sm text-gray-500">{{ $addr->rua }}, {{ $addr->numero }} — {{ $addr->bairro }}, {{ $addr->cidade }}/{{ $addr->estado }}</span>
                        </span>
                    </label>
                    @endforeach
                </div>
                @endif

                @if(! $addingAddress)
                <button type="button" wire:click="startAddingAddress" class="text-sm font-semibold" style="color:#C47A00;">
                    + Adicionar novo endereço
                </button>
                @else
                <div class="space-y-4 mt-2 p-4 rounded-xl border border-gray-100" style="background:#FAFAFA;">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Identificação (ex: Casa, Praia, Trabalho)</label>
                        <input type="text" wire:model="new_label" placeholder="Casa" class="{{ $inputClass('new_label') }}">
                        @error('new_label') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                            <input type="text" wire:model="new_cep" placeholder="00000-000" class="{{ $inputClass('new_cep') }}">
                            @error('new_cep') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rua</label>
                            <input type="text" wire:model="new_rua" class="{{ $inputClass('new_rua') }}">
                            @error('new_rua') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                            <input type="text" wire:model="new_numero" class="{{ $inputClass('new_numero') }}">
                            @error('new_numero') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Complemento (opcional)</label>
                            <input type="text" wire:model="new_complemento" class="{{ $inputClass('new_complemento') }}">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                            <input type="text" wire:model="new_bairro" class="{{ $inputClass('new_bairro') }}">
                            @error('new_bairro') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                            <input type="text" wire:model="new_cidade" class="{{ $inputClass('new_cidade') }}">
                            @error('new_cidade') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">UF</label>
                            <input type="text" wire:model="new_estado" maxlength="2" placeholder="SP" class="{{ $inputClass('new_estado') }} uppercase">
                            @error('new_estado') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" wire:click="saveNewAddress"
                                class="px-5 py-2.5 rounded-xl text-white text-sm font-bold" style="background:#E8A000;">
                            Salvar endereço
                        </button>
                        <button type="button" wire:click="$set('addingAddress', false)"
                                class="px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-500">
                            Cancelar
                        </button>
                    </div>
                </div>
                @endif
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                    <div class="flex-1">
                        <h2 class="font-bold text-base mb-2" style="color:#3D3000;">Frete</h2>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CEP para cotação</label>
                        <input type="text" wire:model="shipping_destination_zipcode" placeholder="00000-000" class="{{ $inputClass('shipping_destination_zipcode') }}">
                        @error('shipping_destination_zipcode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="button"
                            wire:click="calculateShipping"
                            wire:loading.attr="disabled"
                            wire:target="calculateShipping"
                            class="px-5 py-3 rounded-xl text-white text-sm font-bold disabled:opacity-60"
                            style="background:#1a472a; min-height:52px;">
                        <span wire:loading.remove wire:target="calculateShipping">Consultar frete</span>
                        <span wire:loading wire:target="calculateShipping">Consultando...</span>
                    </button>
                </div>

                @if(! empty($shipping_quotes))
                <div class="mt-5 space-y-4">
                    @foreach($grouped as $expositorId => $storeItems)
                    @php
                        $firstItem = $storeItems->first();
                        $quotes = $shipping_quotes[$expositorId] ?? [];
                    @endphp
                    <div class="rounded-xl border border-gray-100 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">
                            {{ $firstItem->expositor?->name ?? 'Loja' }}
                        </p>

                        @if(empty($quotes))
                        <p class="text-sm text-gray-500">Nenhuma opção retornada para esta loja.</p>
                        @else
                        <div class="space-y-2">
                            @foreach($quotes as $quote)
                            @if(! empty($quote['error_message']))
                            <div class="p-3 rounded-lg text-sm border border-amber-200" style="background:#FFFBEB; color:#8A5A00;">
                                {{ $quote['error_message'] }}
                            </div>
                            @else
                            @php
                                $selected = ($selected_shipping_options[$expositorId]['service_id'] ?? null) === ($quote['service_id'] ?? null);
                            @endphp
                            <button type="button"
                                    wire:click='selectShippingOption({{ (int) $expositorId }}, {{ \Illuminate\Support\Js::from($quote) }})'
                                    class="w-full p-3 rounded-xl border-2 text-left transition-colors"
                                    style="{{ $selected ? 'border-color:#E8A000; background:#FFFBEB;' : 'border-color:#e5e7eb; background:#fff;' }}">
                                <span class="flex justify-between gap-3">
                                    <span>
                                        <span class="block font-semibold text-gray-900">
                                            {{ $quote['company'] }} {{ $quote['service_name'] }}
                                        </span>
                                        @if($quote['delivery_time'])
                                        <span class="block text-xs text-gray-500">Entrega em até {{ $quote['delivery_time'] }} dias úteis</span>
                                        @endif
                                    </span>
                                    <span class="font-bold whitespace-nowrap" style="color:#3D3000;">
                                        R$ {{ number_format((float) $quote['price'], 2, ',', '.') }}
                                    </span>
                                </span>
                            </button>
                            @endif
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @else
            <div class="p-4 rounded-xl text-sm" style="background:#FDF8DC; color:#5C4500;">
                📦 <strong>Retirada:</strong> combine o local e horário diretamente com o(s) lojista(s) pelo WhatsApp após a confirmação do pedido.
            </div>
            @endif
        </div>

        {{-- Resumo lateral --}}
        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-gray-200 p-6 sticky top-24">
                <h2 class="font-bold text-base mb-4" style="color:#3D3000;">Resumo do Pedido</h2>

                <div class="space-y-4 mb-5">
                    @foreach($grouped as $expositorId => $storeItems)
                    @php $firstItem = $storeItems->first(); @endphp
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1.5">
                            {{ $firstItem->expositor?->name ?? 'Loja' }}
                        </p>
                        <div class="space-y-1.5">
                            @foreach($storeItems as $item)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 truncate pr-2">{{ $item->quantity }}× {{ $item->product?->name }}</span>
                                <span class="font-semibold text-gray-800 flex-shrink-0">
                                    R$ {{ number_format($item->subtotal(), 2, ',', '.') }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="border-t border-gray-100 pt-4 space-y-2">
                    <div class="flex justify-between items-center text-sm">
                        <span class="font-medium text-gray-600">Subtotal</span>
                        <span class="font-semibold text-gray-800">R$ {{ number_format($total, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="font-medium text-gray-600">Frete</span>
                        <span class="font-semibold text-gray-800">
                            {{ $shippingTotal > 0 ? 'R$ '.number_format($shippingTotal, 2, ',', '.') : 'A combinar' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-gray-100">
                        <span class="font-semibold text-gray-700">Total</span>
                        <span class="text-xl font-bold" style="color:#3D3000;">R$ {{ number_format($totalWithShipping, 2, ',', '.') }}</span>
                    </div>
                </div>

                <button type="submit"
                        class="w-full mt-5 py-4 rounded-xl text-white text-base font-bold transition-opacity hover:opacity-90"
                        style="background:#E8A000; min-height:56px;">
                    <span wire:loading.remove wire:target="confirmar">
                        {{ $settings->mercado_pago_ativo ? 'Pagar com Mercado Pago' : 'Confirmar Pedido' }}
                    </span>
                    <span wire:loading wire:target="confirmar">
                        {{ $settings->mercado_pago_ativo ? 'Abrindo pagamento...' : 'Enviando...' }}
                    </span>
                </button>

                <p class="text-xs text-center text-gray-400 mt-3">
                    Ao confirmar, você concorda com os termos do pedido.
                </p>
            </div>
        </div>
    </form>
    @endif
</div>
