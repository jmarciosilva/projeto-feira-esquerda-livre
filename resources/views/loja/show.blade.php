<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $expositor->name }} — Feira Esquerda Livre</title>
    <meta name="description" content="{{ Str::limit($expositor->description, 160) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @include('partials.site-css')
</head>
<body class="bg-gray-50 font-sans antialiased">
@php
    $requestedReturnTo = request()->query('return_to');
    $requestedReturnTo = $requestedReturnTo && parse_url($requestedReturnTo, PHP_URL_HOST) === request()->getHost()
        ? $requestedReturnTo
        : null;
    $previousUrl = url()->previous();
    $currentUrl = url()->current();
    $previousPath = parse_url($previousUrl, PHP_URL_PATH) ?: '';
    $isSameHostPrevious = parse_url($previousUrl, PHP_URL_HOST) === request()->getHost();
    $isLojaPrevious = str_starts_with($previousPath, '/loja/');
    $storeBackUrl = $requestedReturnTo
        ?: ($isSameHostPrevious && $previousUrl !== $currentUrl && ! $isLojaPrevious ? $previousUrl : url('/'));
@endphp

{{-- Navbar --}}
<nav class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-gray-100 shadow-sm">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 flex items-center justify-between h-16">
        <a href="{{ $storeBackUrl }}" class="flex items-center gap-2 font-bold text-lg" style="color: var(--texto-escuro, #3D3000);">
            @include('partials.site-logo')
            <span class="hidden sm:inline">← Feira Esquerda Livre</span>
            <span class="sm:hidden">←</span>
        </a>
        <div class="flex items-center gap-3">
            <livewire:cart-drawer />
        </div>
    </div>
</nav>

{{-- Header da loja --}}
<div class="relative overflow-hidden" style="background: linear-gradient(135deg, #3D3000 0%, #5C4500 100%); min-height: 200px;">
    @if($expositor->image_path)
    <img src="{{ Storage::url($expositor->image_path) }}" alt="{{ $expositor->name }}"
         class="absolute inset-0 w-full h-full object-cover opacity-30">
    @endif
    <div class="relative max-w-6xl mx-auto px-4 sm:px-6 py-10 flex flex-col sm:flex-row items-start sm:items-end gap-5">
        {{-- Logo --}}
        <div class="w-24 h-24 rounded-2xl border-4 border-white/20 overflow-hidden flex-shrink-0 shadow-xl"
             style="background: #F4E294;">
            @if($expositor->logo_path)
            <img src="{{ Storage::url($expositor->logo_path) }}" alt="{{ $expositor->name }}" class="w-full h-full object-cover">
            @else
            <div class="w-full h-full flex items-center justify-center">
                <span class="text-3xl font-black" style="color: #3D3000;">{{ strtoupper(substr($expositor->name, 0, 1)) }}</span>
            </div>
            @endif
        </div>
        {{-- Info --}}
        <div class="text-white pb-1">
            <h1 class="text-2xl sm:text-3xl font-extrabold leading-tight">{{ $expositor->name }}</h1>
            @if($expositor->city || $expositor->state)
            <p class="text-base mt-1 opacity-80">
                <svg class="inline w-4 h-4 mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ $expositor->city }}@if($expositor->city && $expositor->state), @endif{{ $expositor->state }}
            </p>
            @endif
        </div>
    </div>
</div>

{{-- Conteúdo principal --}}
<div class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">

        {{-- Sidebar: sobre a loja --}}
        <aside class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sticky top-24">
                <h2 class="font-bold text-gray-900 text-base mb-3">Sobre a loja</h2>
                @if($expositor->description)
                <p class="text-sm text-gray-600 leading-relaxed">{{ $expositor->description }}</p>
                @else
                <p class="text-sm text-gray-400 italic">Sem descrição ainda.</p>
                @endif

                @if($expositor->whatsapp)
                <a href="https://wa.me/55{{ preg_replace('/\D/', '', $expositor->whatsapp) }}" target="_blank"
                   class="mt-4 flex items-center gap-2 px-4 py-2.5 rounded-xl text-white text-sm font-semibold w-full justify-center"
                   style="background: #25D366;">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    WhatsApp
                </a>
                @endif
            </div>
        </aside>

        {{-- Catálogo agrupado por eixo --}}
        <main class="lg:col-span-3">
            @php
                use App\Enums\ItemType;
                $grupos = $products->groupBy(fn($p) => $p->item_type?->value ?? 'produto');
            @endphp

            @if($products->isEmpty())
            <div class="bg-white rounded-2xl p-12 text-center border border-gray-100">
                <div class="text-5xl mb-4">🛍</div>
                <p class="text-lg font-semibold text-gray-500">Esta loja ainda não tem itens cadastrados.</p>
            </div>
            @else

            @foreach(ItemType::cases() as $tipo)
            @if($grupos->has($tipo->value))
            @php $itensDoEixo = $grupos->get($tipo->value); @endphp
            <section class="mb-10">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-2xl">{{ $tipo->emoji() }}</span>
                    <h2 class="text-xl font-bold text-gray-900">{{ $tipo->label() }}</h2>
                    <span class="text-sm text-gray-400">({{ $itensDoEixo->count() }})</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    @foreach($itensDoEixo as $product)
                    <a href="{{ route('loja.produto', [$expositor->slug, $product->slug, 'return_to' => $storeBackUrl]) }}"
                       class="group bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                        <div class="aspect-square overflow-hidden bg-gray-50">
                            @php
                                $imgs  = $product->images ?? [];
                                $thumb = !empty($imgs[0]['thumb']) ? Storage::url($imgs[0]['thumb']) : ($product->image_path ? Storage::url($product->image_path) : null);
                            @endphp
                            @if($thumb)
                            <img src="{{ $thumb }}" alt="{{ $product->name }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy">
                            @else
                            <div class="w-full h-full flex items-center justify-center text-4xl"
                                 style="background: linear-gradient(135deg, #F4E294, #E8A000);">{{ $tipo->emoji() }}</div>
                            @endif
                        </div>
                        <div class="p-3">
                            <p class="font-semibold text-gray-900 text-sm leading-tight line-clamp-2">{{ $product->name }}</p>
                            @if($product->price_type?->value === 'sob_consulta')
                            <p class="font-semibold text-sm mt-1.5" style="color:#C47A00;">A combinar</p>
                            @elseif($product->price)
                            <p class="font-bold text-base mt-1.5" style="color:#C47A00;">
                                R$ {{ number_format((float) $product->price, 2, ',', '.') }}
                                @if($product->price_type && $product->item_type?->value !== 'produto')
                                <span class="text-xs font-normal text-gray-400">/ {{ $product->price_type->label() }}</span>
                                @endif
                            </p>
                            @endif
                            @if($product->modality)
                            <span class="inline-flex mt-1 px-2 py-0.5 rounded-full text-xs font-medium"
                                  style="background:#f0fdf4; color:#166534;">
                                {{ $product->modality->label() }}
                            </span>
                            @endif
                        </div>
                    </a>
                    @endforeach
                </div>
            </section>
            @endif
            @endforeach

            @endif
        </main>

    </div>
</div>

<footer class="text-center text-sm text-gray-400 py-8 border-t border-gray-100 mt-10">
    <a href="{{ url('/') }}" class="font-semibold" style="color: #C47A00;">Feira Esquerda Livre</a>
    &copy; {{ date('Y') }}
</footer>

@livewireScripts
</body>
</html>
