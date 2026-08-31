<div>
    <button wire:click="toggle"
            class="relative inline-flex items-center justify-center rounded-full transition-colors hover:opacity-90"
            style="background: #3D3000; color: #F4E294; width: 44px; height: 44px;"
            aria-label="Abrir carrinho"
            title="Carrinho">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        @if($count > 0)
        <span class="absolute -top-1 -right-1 w-5 h-5 rounded-full text-xs font-bold flex items-center justify-center"
              style="background: #E8A000; color: white;">{{ $count }}</span>
        @endif
    </button>

    @if($open)
    <div class="fixed inset-0 z-40 bg-black/70" wire:click="toggle"></div>
    @endif

    <aside class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-white shadow-2xl ring-1 ring-black/10 transition-transform duration-300 {{ $open ? 'translate-x-0' : 'translate-x-full' }}"
           style="height: 100dvh;"
           aria-label="Carrinho de compras">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 bg-white">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Carrinho</h2>
                @if($count > 0)
                <p class="text-sm text-gray-500">{{ $count }} {{ $count === 1 ? 'item' : 'itens' }} no carrinho</p>
                @endif
            </div>
            <button wire:click="toggle"
                    class="w-11 h-11 rounded-full flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-gray-800 transition-colors"
                    aria-label="Fechar carrinho">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="flex-1 min-h-0 overflow-y-auto bg-white px-5 py-4">
            @forelse($grouped as $expositorId => $storeItems)
            @php $firstItem = $storeItems->first(); @endphp
            <section class="mb-6 last:mb-0">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-500">
                        {{ $firstItem->expositor?->name ?? 'Loja' }}
                    </p>
                    <p class="text-xs font-semibold text-gray-400">
                        {{ $storeItems->sum('quantity') }} {{ $storeItems->sum('quantity') === 1 ? 'item' : 'itens' }}
                    </p>
                </div>

                <div class="space-y-3">
                    @foreach($storeItems as $item)
                    @php
                        // A imagem do item no carrinho e a da oferta comprada. O
                        // ultimo ramo cobre a oferta removida depois da compra
                        // (`product_offer_id` e SET NULL): ai so resta o canonico.
                        $thumb = $item->offer?->urlDaImagemPrincipal('thumb')
                            ?? ($item->product?->image_path ? \Storage::url($item->product->image_path) : null);
                    @endphp
                    <article class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm" wire:key="cart-item-{{ $item->id }}">
                        <div class="flex gap-3">
                            @if($thumb)
                            <img src="{{ $thumb }}"
                                 alt="{{ $item->product?->name ?? 'Produto' }}"
                                 class="w-20 h-20 rounded-lg object-cover flex-shrink-0 border border-gray-100">
                            @else
                            <div class="w-20 h-20 rounded-lg flex-shrink-0 flex items-center justify-center border border-gray-100"
                                 style="background: linear-gradient(135deg, #F4E294, #E8A000);">
                                <svg class="w-8 h-8" fill="none" stroke="#3D3000" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l-1 11H6L5 9z"/>
                                </svg>
                            </div>
                            @endif

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-bold leading-snug text-gray-900">
                                            {{ $item->product?->name ?? 'Produto removido' }}
                                        </h3>
                                        <p class="mt-1 text-xs font-semibold text-gray-500">
                                            Unitário: R$ {{ number_format((float) $item->price_snapshot, 2, ',', '.') }}
                                        </p>
                                    </div>
                                    <button wire:click="remove({{ $item->id }})"
                                            wire:loading.attr="disabled"
                                            class="w-9 h-9 rounded-full flex items-center justify-center text-red-500 hover:bg-red-50 transition-colors"
                                            title="Remover item"
                                            aria-label="Remover {{ $item->product?->name ?? 'produto' }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4h6v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>

                                <div class="mt-3 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                                wire:click="updateQty({{ $item->id }}, {{ max(1, $item->quantity - 1) }})"
                                                wire:loading.attr="disabled"
                                                @disabled($item->quantity <= 1)
                                                class="w-9 h-9 rounded-lg border border-gray-300 flex items-center justify-center text-lg font-bold text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                                                aria-label="Diminuir quantidade">
                                            -
                                        </button>
                                        <span class="w-8 text-center text-base font-bold text-gray-900">{{ $item->quantity }}</span>
                                        <button type="button"
                                                wire:click="updateQty({{ $item->id }}, {{ $item->quantity + 1 }})"
                                                wire:loading.attr="disabled"
                                                class="w-9 h-9 rounded-lg border border-gray-300 flex items-center justify-center text-lg font-bold text-gray-700 hover:bg-gray-50"
                                                aria-label="Aumentar quantidade">
                                            +
                                        </button>
                                    </div>

                                    <div class="text-right">
                                        <p class="text-xs text-gray-500">Subtotal</p>
                                        <p class="text-sm font-black" style="color: #3D3000;">
                                            R$ {{ number_format($item->subtotal(), 2, ',', '.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                    @endforeach
                </div>
            </section>
            @empty
            <div class="h-full min-h-96 flex flex-col items-center justify-center py-16 text-center">
                <div class="w-20 h-20 rounded-full flex items-center justify-center mb-4" style="background: #F4E294;">
                    <svg class="w-10 h-10" fill="none" stroke="#3D3000" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17"/>
                    </svg>
                </div>
                <p class="text-lg font-bold text-gray-700">Seu carrinho está vazio</p>
                <p class="text-sm text-gray-500 mt-1">Explore as lojas e adicione produtos.</p>
                <button wire:click="toggle" class="mt-6 px-6 py-3 rounded-xl font-bold text-white text-base"
                        style="background: #E8A000;">Ver lojas</button>
            </div>
            @endforelse
        </div>

        @if($count > 0)
        <div class="border-t border-gray-200 bg-white px-5 py-5 shadow-[0_-12px_30px_rgba(15,23,42,0.08)]">
            <div class="flex items-center justify-between text-base">
                <span class="text-gray-700 font-bold">Total</span>
                <span class="text-2xl font-black" style="color: #3D3000;">
                    R$ {{ number_format($total, 2, ',', '.') }}
                </span>
            </div>
            <a href="{{ route('checkout') }}"
               class="mt-4 block w-full text-center py-4 rounded-xl text-white text-lg font-bold transition-opacity hover:opacity-90"
               style="background: #E8A000; min-height: 60px;">
                Finalizar Compra
            </a>
            <button wire:click="toggle" class="mt-3 w-full py-3 rounded-xl border-2 font-semibold text-base transition-colors hover:bg-yellow-50"
                    style="border-color: #E8A000; color: #C47A00;">
                Continuar Comprando
            </button>
        </div>
        @endif
    </aside>
</div>
