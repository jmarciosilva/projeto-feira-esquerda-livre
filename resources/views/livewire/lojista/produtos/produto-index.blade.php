<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-base font-medium">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-base font-medium">{{ session('error') }}</div>
    @endif

    <div class="flex flex-col sm:flex-row gap-3 mb-4">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por nome..."
               class="flex-1 min-w-0 px-4 py-3 border border-gray-300 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000]">
        <a href="{{ route('lojista.produtos.create') }}">
            <button class="w-full sm:w-auto px-6 py-3 rounded-xl font-bold text-base text-white"
                    style="background-color: #E8A000; min-height: 52px;">
                + Novo Cadastro
            </button>
        </a>
    </div>

    {{-- Filtro por eixo --}}
    <div class="flex items-center gap-2 flex-wrap mb-6">
        <button wire:click="$set('filterType', '')"
                class="px-4 py-2 rounded-full text-sm font-semibold border-2 transition-colors"
                style="{{ $filterType === '' ? 'background:#E8A000; border-color:#E8A000; color:#fff;' : 'background:#fff; border-color:#e5e7eb; color:#6b7280;' }}">
            Todos
        </button>
        <button wire:click="$set('filterType', 'produto')"
                class="px-4 py-2 rounded-full text-sm font-semibold border-2 transition-colors"
                style="{{ $filterType === 'produto' ? 'background:#E8A000; border-color:#E8A000; color:#fff;' : 'background:#fff; border-color:#e5e7eb; color:#6b7280;' }}">
            🛍️ Produtos
        </button>
        <button wire:click="$set('filterType', 'servico')"
                class="px-4 py-2 rounded-full text-sm font-semibold border-2 transition-colors"
                style="{{ $filterType === 'servico' ? 'background:#E8A000; border-color:#E8A000; color:#fff;' : 'background:#fff; border-color:#e5e7eb; color:#6b7280;' }}">
            🎯 Serviços
        </button>
        <button wire:click="$set('filterType', 'cuidado')"
                class="px-4 py-2 rounded-full text-sm font-semibold border-2 transition-colors"
                style="{{ $filterType === 'cuidado' ? 'background:#E8A000; border-color:#E8A000; color:#fff;' : 'background:#fff; border-color:#e5e7eb; color:#6b7280;' }}">
            🌿 Cuidados
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-base">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider">Produto</th>
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Preço</th>
                        <th class="text-center py-4 px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="text-right py-4 px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($offers as $offer)
                    @php $product = $offer->product; @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-4">
                            <div class="flex items-center gap-3">
                                @if($product->main_image_url)
                                <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}"
                                     class="w-14 h-14 rounded-xl object-cover flex-shrink-0 border border-gray-100">
                                @else
                                @php
                                    $emoji = match($product->item_type?->value) {
                                        'servico' => '🎯',
                                        'cuidado' => '🌿',
                                        default   => '🛍',
                                    };
                                @endphp
                                <div class="w-14 h-14 rounded-xl flex-shrink-0 flex items-center justify-center text-2xl"
                                     style="background: linear-gradient(135deg, #F4E294, #E8A000);">{{ $emoji }}</div>
                                @endif
                                <div>
                                    <p class="font-semibold text-gray-900 leading-tight">{{ $product->name }}</p>
                                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                                        @php
                                            $typeColors = [
                                                'produto' => 'background:#dbeafe; color:#1e40af;',
                                                'servico' => 'background:#fce7f3; color:#9d174d;',
                                                'cuidado' => 'background:#dcfce7; color:#166534;',
                                            ];
                                            $typeColor = $typeColors[$product->item_type?->value ?? 'produto'] ?? $typeColors['produto'];
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                              style="{{ $typeColor }}">
                                            {{ $product->item_type?->emoji() }} {{ $product->item_type?->label() ?? 'Produto' }}
                                        </span>
                                        @if($product->category)
                                        <span class="text-xs text-gray-400">{{ $product->category->name }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-4 hidden sm:table-cell">
                            @if($offer->price_type?->value === 'sob_consulta')
                            <span class="text-gray-500 text-sm font-medium">A combinar</span>
                            @elseif($offer->price)
                            <div>
                                <span class="font-bold text-lg" style="color: #C47A00;">
                                    R$ {{ number_format((float)$offer->price, 2, ',', '.') }}
                                </span>
                                @if($offer->price_type && $product->item_type?->value !== 'produto')
                                <span class="block text-xs text-gray-400">{{ $offer->price_type->label() }}</span>
                                @endif
                            </div>
                            @else
                            <span class="text-gray-400 text-sm">Sem preço</span>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-center">
                            <button wire:click="toggleActive({{ $offer->id }})"
                                    class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold transition-colors"
                                    style="{{ $offer->is_active ? 'background:#dcfce7; color:#166534;' : 'background:#f3f4f6; color:#6b7280;' }}">
                                {{ $offer->is_active ? 'Ativo' : 'Inativo' }}
                            </button>
                        </td>
                        <td class="py-4 px-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('lojista.produtos.edit', $product) }}"
                                   class="px-3 py-2 rounded-lg text-sm font-semibold border-2 transition-colors"
                                   style="border-color: #E8A000; color: #C47A00; min-height: 40px; display:inline-flex; align-items:center;">
                                    Editar
                                </a>
                                <a href="{{ route('lojista.produtos.share-image', $product) }}"
                                   class="px-3 py-2 rounded-lg text-sm font-semibold border-2 transition-colors"
                                   style="border-color: #1a472a; color: #1a472a; min-height: 40px; display:inline-flex; align-items:center;">
                                    Gerar imagem
                                </a>
                                <button wire:click="delete({{ $offer->id }})"
                                        wire:confirm="Remover '{{ $product->name }}' da sua loja?"
                                        class="px-3 py-2 rounded-lg text-sm font-semibold border-2 border-red-200 text-red-500 transition-colors hover:bg-red-50"
                                        style="min-height: 40px;">
                                    Excluir
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-16 text-center">
                            <div class="text-5xl mb-4">🛍</div>
                            <p class="text-lg font-semibold text-gray-500">Nenhum cadastro encontrado.</p>
                            <a href="{{ route('lojista.produtos.create') }}"
                               class="mt-4 inline-block px-6 py-3 rounded-xl text-white font-bold"
                               style="background-color: #E8A000;">
                                Fazer primeiro cadastro
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($offers->hasPages())
        <div class="px-4 py-4 border-t border-gray-100">
            {{ $offers->links() }}
        </div>
        @endif
    </div>
</div>
