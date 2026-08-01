<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $productUrl = route('loja.produto', [$expositor->slug, $product->slug]);
        $productDescription = Str::limit($product->description ?? $product->name, 160);
        $productImages = $product->images ?? [];
        $productOgImage = ! empty($productImages[0]['medium'])
            ? Storage::url($productImages[0]['medium'])
            : ($product->image_path ? Storage::url($product->image_path) : null);
        $productOgImage = $productOgImage
            ? url($productOgImage)
            : route('loja.produto.share-preview', [$expositor->slug, $product->slug]);
        $sharePrice = $product->price ? 'R$ ' . number_format((float) $product->price, 2, ',', '.') : 'valor sob consulta';
        $shareText = "Conheça {$product->name} por {$sharePrice} na loja {$expositor->name}: {$productUrl}";
        $returnTo = request()->query('return_to');
        $returnTo = $returnTo && parse_url($returnTo, PHP_URL_HOST) === request()->getHost()
            ? $returnTo
            : null;
        $storeUrl = route('loja.show', $returnTo
            ? [$expositor->slug, 'return_to' => $returnTo]
            : [$expositor->slug]);
    @endphp
    <title>{{ $product->name }} — {{ $expositor->name }} — Feira Esquerda Livre</title>
    <meta name="description" content="{{ $productDescription }}">
    <meta property="og:title" content="{{ $product->name }} — {{ $expositor->name }}">
    <meta property="og:description" content="{{ $productDescription }}">
    <meta property="og:type" content="product">
    <meta property="og:url" content="{{ $productUrl }}">
    @if($productOgImage)
    <meta property="og:image" content="{{ $productOgImage }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $product->name }} — {{ $expositor->name }}">
    <meta name="twitter:description" content="{{ $productDescription }}">
    @if($productOgImage)
    <meta name="twitter:image" content="{{ $productOgImage }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @include('partials.site-css')
</head>
<body class="bg-gray-50 font-sans antialiased">

{{-- Navbar --}}
<nav class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-gray-100 shadow-sm">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 flex items-center justify-between h-16">
        <a href="{{ $storeUrl }}"
           class="flex items-center gap-2 font-bold text-base" style="color: var(--texto-escuro, #3D3000);">
            @include('partials.site-logo')
            ← {{ $expositor->name }}
        </a>
        <div class="flex items-center gap-3">
            <livewire:cart-drawer />
        </div>
    </div>
</nav>

{{-- Breadcrumb --}}
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-3">
    <p class="text-sm text-gray-400">
        <a href="{{ url('/') }}" class="hover:underline">Início</a>
        <span class="mx-1.5">›</span>
        <a href="{{ $storeUrl }}" class="hover:underline">{{ $expositor->name }}</a>
        <span class="mx-1.5">›</span>
        <span class="text-gray-600">{{ $product->name }}</span>
    </p>
</div>

{{-- Produto --}}
<div class="max-w-5xl mx-auto px-4 sm:px-6 pb-12">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-0">

            {{-- Galeria --}}
            <div class="p-4 sm:p-6">
                @php
                    $imgs = $product->images ?? [];
                @endphp
                @if(count($imgs))
                <div class="aspect-square rounded-xl overflow-hidden bg-gray-50 mb-3">
                    <img id="main-product-img"
                         src="{{ Storage::url($imgs[0]['medium'] ?? $imgs[0]['thumb']) }}"
                         alt="{{ $product->name }}"
                         class="w-full h-full object-cover">
                </div>
                @if(count($imgs) > 1)
                <div class="flex gap-2 overflow-x-auto pb-1">
                    @foreach($imgs as $i => $img)
                    <button onclick="document.getElementById('main-product-img').src='{{ Storage::url($img['medium'] ?? $img['thumb']) }}'"
                            class="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0 border-2 border-transparent hover:border-yellow-400 transition-colors">
                        <img src="{{ Storage::url($img['thumb']) }}" alt="Foto {{ $i+1 }}" class="w-full h-full object-cover">
                    </button>
                    @endforeach
                </div>
                @endif
                @elseif($product->image_path)
                <div class="aspect-square rounded-xl overflow-hidden bg-gray-50">
                    <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                </div>
                @else
                <div class="aspect-square rounded-xl flex items-center justify-center text-7xl"
                     style="background: linear-gradient(135deg, #F4E294, #E8A000);">🛍</div>
                @endif
            </div>

            {{-- Detalhes --}}
            <div class="p-6 sm:p-8 flex flex-col justify-between border-t md:border-t-0 md:border-l border-gray-100">
                <div>
                    {{-- Categoria --}}
                    @if($product->category)
                    <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full mb-3"
                          style="background: #F4E294; color: #3D3000;">{{ $product->category->name }}</span>
                    @endif

                    <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight">{{ $product->name }}</h1>

                    @if($product->price)
                    <p class="text-3xl font-black mt-4" style="color: #C47A00;">
                        R$ {{ number_format((float) $product->price, 2, ',', '.') }}
                    </p>
                    @endif

                    @if($product->description)
                    <div class="mt-5 text-base text-gray-600 leading-relaxed space-y-3">
                        @foreach(explode("\n", $product->description) as $paragraph)
                        @if(trim($paragraph))
                        <p>{{ trim($paragraph) }}</p>
                        @endif
                        @endforeach
                    </div>
                    @endif

                    {{-- Estoque --}}
                    @if($product->has_stock)
                    <div class="mt-4 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-green-400 flex-shrink-0"></span>
                        <span class="text-sm text-green-700 font-medium">
                            @if($product->stock_quantity !== null)
                                {{ $product->stock_quantity }} {{ $product->stock_quantity === 1 ? 'unidade disponível' : 'unidades disponíveis' }}
                            @else
                                Disponível em estoque
                            @endif
                        </span>
                    </div>
                    @endif
                </div>

                {{-- CTA --}}
                <div class="mt-8 space-y-3">
                    <button onclick="Livewire.dispatch('add-to-cart', { productId: {{ $product->id }} })"
                            class="w-full py-4 rounded-xl text-white text-lg font-bold transition-colors"
                            style="background: #E8A000; min-height: 60px;">
                        Adicionar ao Carrinho
                    </button>

                    @if($expositor->whatsapp)
                    <a href="https://wa.me/55{{ preg_replace('/\D/', '', $expositor->whatsapp) }}?text={{ urlencode('Olá! Vi o produto "' . $product->name . '" na Feira Esquerda Livre e tenho interesse. Ainda está disponível?') }}"
                       target="_blank"
                       class="flex items-center justify-center gap-2 w-full py-3 rounded-xl border-2 font-semibold text-base transition-colors"
                       style="border-color: #25D366; color: #25D366;">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        Perguntar pelo WhatsApp
                    </a>
                    @endif

                    <div class="pt-3 border-t border-gray-100"
                         x-data="{ copied: false, copy() { navigator.clipboard.writeText('{{ $productUrl }}'); this.copied = true; setTimeout(() => this.copied = false, 2000); } }">
                        <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-3">Compartilhar</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <a href="https://wa.me/?text={{ urlencode($shareText) }}"
                               target="_blank"
                               rel="noopener"
                               class="flex items-center justify-center px-4 py-3 rounded-xl border-2 font-semibold text-sm"
                               style="border-color:#25D366; color:#15803d; min-height:48px;">
                                WhatsApp
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($productUrl) }}"
                               target="_blank"
                               rel="noopener"
                               class="flex items-center justify-center px-4 py-3 rounded-xl border-2 font-semibold text-sm"
                               style="border-color:#2563eb; color:#1d4ed8; min-height:48px;">
                                Facebook
                            </a>
                            <button type="button"
                                    @click="copy()"
                                    class="px-4 py-3 rounded-xl border-2 font-semibold text-sm"
                                    style="border-color:#E8A000; color:#C47A00; min-height:48px;">
                                <span x-show="!copied">Copiar link</span>
                                <span x-show="copied">Link copiado</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Vendedor --}}
                <div class="mt-6 pt-5 border-t border-gray-100">
                    <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-3">Vendido por</p>
                    <a href="{{ $storeUrl }}" class="flex items-center gap-3 group">
                        <div class="w-10 h-10 rounded-xl overflow-hidden flex-shrink-0" style="background: #F4E294;">
                            @if($expositor->logo_path)
                            <img src="{{ Storage::url($expositor->logo_path) }}" alt="{{ $expositor->name }}" class="w-full h-full object-cover">
                            @else
                            <div class="w-full h-full flex items-center justify-center font-bold text-sm" style="color: #3D3000;">
                                {{ strtoupper(substr($expositor->name, 0, 1)) }}
                            </div>
                            @endif
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 group-hover:underline">{{ $expositor->name }}</p>
                            @if($expositor->city)
                            <p class="text-xs text-gray-400">{{ $expositor->city }}@if($expositor->state), {{ $expositor->state }}@endif</p>
                            @endif
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Q&A --}}
    <livewire:product-q-and-a :product="$product" />

    {{-- FAQ --}}
    @php $faqs = $product->faqs; @endphp
    @if($faqs->isNotEmpty())
    <div class="mt-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Perguntas Frequentes</h2>
        <div class="space-y-2" x-data="{ open: null }">
            @foreach($faqs as $i => $faq)
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <button type="button"
                        @click="open = open === {{ $i }} ? null : {{ $i }}"
                        class="w-full flex items-center justify-between px-5 py-4 text-left gap-4">
                    <span class="font-semibold text-gray-900 text-base leading-snug">{{ $faq->question }}</span>
                    <svg class="w-5 h-5 flex-shrink-0 text-gray-400 transition-transform duration-200"
                         :class="open === {{ $i }} ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === {{ $i }}"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="px-5 pb-5 text-gray-600 text-base leading-relaxed border-t border-gray-50">
                    <div class="pt-3">
                        @foreach(explode("\n", $faq->answer) as $line)
                        @if(trim($line))<p>{{ trim($line) }}</p>@endif
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Outros produtos da loja --}}
    @if($otherProducts->isNotEmpty())
    <div class="mt-10">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Outros produtos de {{ $expositor->name }}</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @foreach($otherProducts as $other)
            <a href="{{ route('loja.produto', [$expositor->slug, $other->slug, 'return_to' => $returnTo]) }}"
               class="group bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                <div class="aspect-square overflow-hidden bg-gray-50">
                    @php
                        $otherImgs = $other->images ?? [];
                        $otherThumb = !empty($otherImgs[0]['thumb']) ? Storage::url($otherImgs[0]['thumb']) : ($other->image_path ? Storage::url($other->image_path) : null);
                    @endphp
                    @if($otherThumb)
                    <img src="{{ $otherThumb }}" alt="{{ $other->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    @else
                    <div class="w-full h-full flex items-center justify-center text-3xl" style="background: linear-gradient(135deg, #F4E294, #E8A000);">🛍</div>
                    @endif
                </div>
                <div class="p-3">
                    <p class="font-semibold text-gray-900 text-sm leading-tight line-clamp-2">{{ $other->name }}</p>
                    @if($other->price)
                    <p class="font-bold text-base mt-1" style="color: #C47A00;">R$ {{ number_format((float) $other->price, 2, ',', '.') }}</p>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>

<footer class="text-center text-sm text-gray-400 py-8 border-t border-gray-100 mt-4">
    <a href="{{ url('/') }}" class="font-semibold" style="color: #C47A00;">Feira Esquerda Livre</a>
    &copy; {{ date('Y') }}
</footer>

@livewireScripts
</body>
</html>
