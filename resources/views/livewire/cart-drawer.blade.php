<div>
    {{-- Cart icon trigger (floating or inline) --}}
    <button wire:click="toggle"
            class="relative flex items-center gap-2 px-4 py-2 rounded-full font-semibold text-base transition-colors"
            style="background: #3D3000; color: #F4E294; min-height: 44px;"
            aria-label="Carrinho">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <span class="hidden sm:inline">Carrinho</span>
        @if($count > 0)
        <span class="absolute -top-1 -right-1 w-5 h-5 rounded-full text-xs font-bold flex items-center justify-center"
              style="background: #E8A000; color: white;">{{ $count }}</span>
        @endif
    </button>

    {{-- Drawer backdrop --}}
    @if($open)
    <div class="fixed inset-0 z-40 bg-black/50" wire:click="toggle"></div>
    @endif

    {{-- Drawer panel --}}
    <div class="fixed top-0 right-0 z-50 h-full w-full max-w-md bg-white shadow-2xl flex flex-col transition-transform duration-300 {{ $open ? 'translate-x-0' : 'translate-x-full' }}">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h2 class="text-lg font-bold text-gray-900">
                Carrinho
                @if($count > 0)
                <span class="ml-2 text-sm font-medium text-gray-500">({{ $count }} {{ $count === 1 ? 'item' : 'itens' }})</span>
                @endif
            </h2>
            <button wire:click="toggle" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Items --}}
        <div class="flex-1 overflow-y-auto px-5 py-4 space-y-6">
            @forelse($grouped as $expositorId => $storeItems)
            @php $firstItem = $storeItems->first(); @endphp
            <div>
                {{-- Store name --}}
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">
                    {{ $firstItem->expositor?->name ?? 'Loja' }}
                </p>

                <div class="space-y-3">
                    @foreach($storeItems as $item)
                    <div class="flex gap-3 items-start">
                        {{-- Thumb --}}
                        @php
                            $imgs = $item->product?->images ?? [];
                            $thumb = !empty($imgs[0]['thumb']) ? \Storage::url($imgs[0]['thumb']) : ($item->product?->image_path ? \Storage::url($item->product->image_path) : null);
                        @endphp
                        @if($thumb)
                        <img src="{{ $thumb }}" alt="{{ $item->product?->name }}"
                             class="w-16 h-16 rounded-xl object-cover flex-shrink-0 border border-gray-100">
                        @else
                        <div class="w-16 h-16 rounded-xl flex-shrink-0 flex items-center justify-center text-2xl border border-gray-100"
                             style="background: linear-gradient(135deg, #F4E294, #E8A000);">🛍</div>
                        @endif

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 text-sm leading-tight truncate">{{ $item->product?->name }}</p>
                            <p class="text-sm font-bold mt-0.5" style="color: #C47A00;">
                                R$ {{ number_format((float) $item->price_snapshot, 2, ',', '.') }}
                            </p>

                            {{-- Qty controls --}}
                            <div class="flex items-center gap-2 mt-2">
                                <button wire:click="updateQty({{ $item->id }}, {{ max(1, $item->quantity - 1) }})"
                                        class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50 font-bold text-lg transition-colors"
                                        @if($item->quantity <= 1) disabled @endif>−</button>
                                <span class="text-base font-semibold text-gray-800 w-5 text-center">{{ $item->quantity }}</span>
                                <button wire:click="updateQty({{ $item->id }}, {{ $item->quantity + 1 }})"
                                        class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50 font-bold text-lg transition-colors">+</button>
                                <button wire:click="remove({{ $item->id }})"
                                        class="ml-auto text-red-400 hover:text-red-600 transition-colors" title="Remover">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @empty
            <div class="h-full flex flex-col items-center justify-center py-16 text-center">
                <div class="text-6xl mb-4">🛒</div>
                <p class="text-lg font-semibold text-gray-600">Seu carrinho está vazio</p>
                <p class="text-sm text-gray-400 mt-1">Explore as lojas e adicione produtos!</p>
                <button wire:click="toggle" class="mt-6 px-6 py-3 rounded-xl font-bold text-white text-base"
                        style="background: #E8A000;">Ver lojas</button>
            </div>
            @endforelse
        </div>

        {{-- Footer --}}
        @if($count > 0)
        <div class="border-t border-gray-100 px-5 py-5 space-y-3">
            <div class="flex items-center justify-between text-base">
                <span class="text-gray-600 font-medium">Total</span>
                <span class="text-xl font-bold" style="color: #3D3000;">
                    R$ {{ number_format($total, 2, ',', '.') }}
                </span>
            </div>
            <button class="w-full py-4 rounded-xl text-white text-lg font-bold transition-colors"
                    style="background: #E8A000; min-height: 60px;">
                Finalizar Compra
            </button>
            <button wire:click="toggle" class="w-full py-3 rounded-xl border-2 font-semibold text-base transition-colors"
                    style="border-color: #E8A000; color: #C47A00;">
                Continuar Comprando
            </button>
        </div>
        @endif
    </div>
</div>
