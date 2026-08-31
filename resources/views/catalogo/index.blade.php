@extends('layouts.public')

@section('title', $eixo->label() . ' — Feira Esquerda Livre')
@section('description', 'Explore ' . strtolower($eixo->label()) . ' de expositores progressistas em todo o Brasil.')

@section('content')
@php $settings = App\Models\SiteSetting::instance(); @endphp

<main>
    {{-- Hero do eixo --}}
    <div class="py-12 px-4" style="background:linear-gradient(135deg,#3D3000 0%,#5C4500 60%,#E8A000 100%);">
        <div class="max-w-5xl mx-auto text-center">
            <div class="text-5xl mb-3">{{ $eixo->emoji() }}</div>
            <h1 class="text-3xl sm:text-4xl font-extrabold mb-3 leading-tight" style="color:#F4E294;">
                {{ $eixo->label() }}
            </h1>
            <p class="text-base sm:text-lg max-w-xl mx-auto" style="color:rgba(255,255,255,0.85);">
                @if($eixo->value === 'produto')
                    Artesanato, livros, alimentação, moda, decoração e muito mais.
                @elseif($eixo->value === 'servico')
                    Design, comunicação, aulas, consultorias e serviços criativos.
                @else
                    Massagens, terapias, práticas integrativas e bem-estar.
                @endif
            </p>
        </div>
    </div>

    {{-- Abas dos três eixos --}}
    <div style="background:#FDF8DC; border-bottom:2px solid #F4E294;">
        <div class="max-w-5xl mx-auto px-4">
            <div class="flex items-center gap-1 overflow-x-auto py-3">
                @foreach(\App\Enums\ItemType::cases() as $tipo)
                <a href="{{ url('/' . $tipo->routeSlug()) }}"
                   class="flex-shrink-0 flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-colors"
                   style="{{ $tipo->value === $eixo->value
                       ? 'background:#3D3000; color:#F4E294;'
                       : 'background:transparent; color:#5C4500;' }}">
                    {{ $tipo->emoji() }} {{ $tipo->label() }}
                </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 py-10">

        {{-- Filtros --}}
        <div x-data="catalogoFiltros()" class="mb-8">
            <form method="GET" action="{{ url('/' . $eixo->routeSlug()) }}" class="flex flex-col sm:flex-row gap-3">
                <input type="search" name="busca" value="{{ request('busca') }}"
                       placeholder="Buscar por nome..."
                       class="flex-1 px-4 py-3 border-2 border-gray-200 rounded-xl text-base focus:outline-none focus:border-yellow-400"
                       style="min-height: 52px;">

                @if($categorias->isNotEmpty())
                <select name="categoria"
                        class="px-4 py-3 border-2 border-gray-200 rounded-xl text-base focus:outline-none focus:border-yellow-400"
                        style="min-height: 52px; min-width: 200px;">
                    <option value="">Todas as categorias</option>
                    @foreach($categorias as $cat)
                    <option value="{{ $cat->id }}" {{ request('categoria') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                    @endforeach
                </select>
                @endif

                <button type="submit"
                        class="px-6 py-3 rounded-xl font-bold text-white transition-colors"
                        style="background:#3D3000; min-height: 52px;">
                    Buscar
                </button>

                @if(request('busca') || request('categoria'))
                <a href="{{ url('/' . $eixo->routeSlug()) }}"
                   class="px-5 py-3 rounded-xl font-semibold text-gray-500 border-2 border-gray-200 text-center"
                   style="min-height: 52px; display:inline-flex; align-items:center;">
                    Limpar
                </a>
                @endif
            </form>
        </div>

        {{-- Grid de itens --}}
        @if($items->isEmpty())
        <div class="bg-white rounded-2xl p-16 text-center border border-gray-100">
            <div class="text-6xl mb-4">{{ $eixo->emoji() }}</div>
            <p class="text-xl font-semibold text-gray-500">Nenhum resultado encontrado.</p>
            <a href="{{ url('/' . $eixo->routeSlug()) }}"
               class="mt-5 inline-block px-6 py-3 rounded-xl font-bold text-white"
               style="background:#E8A000;">
                Ver todos
            </a>
        </div>
        @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($items as $item)
            {{-- O card mostra o item; quem vende, por quanto e como vem da oferta. --}}
            @php $oferta = $item->ofertaVigente; @endphp
            <a href="{{ route('loja.produto', [$oferta->expositor->slug, $item->slug, 'return_to' => url()->full()]) }}"
               class="group bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                <div class="aspect-square overflow-hidden bg-gray-50">
                    @php
                        // CAT-DOM-02E: a vitrine e contexto comercial, entao a
                        // imagem vem da oferta, com fallback para a canonica.
                        $thumb = $oferta?->urlDaImagemPrincipal('thumb');
                    @endphp
                    @if($thumb)
                    <img src="{{ $thumb }}" alt="{{ $item->name }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                         loading="lazy">
                    @else
                    <div class="w-full h-full flex items-center justify-center text-4xl"
                         style="background: linear-gradient(135deg, #F4E294, #E8A000);">{{ $eixo->emoji() }}</div>
                    @endif
                </div>
                <div class="p-3">
                    <p class="font-semibold text-gray-900 text-sm leading-tight line-clamp-2">{{ $item->name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $oferta->expositor->name }}</p>
                    @if($oferta->price_type?->value === 'sob_consulta')
                    <p class="font-semibold text-sm mt-1.5" style="color:#C47A00;">A combinar</p>
                    @elseif($oferta->price)
                    <p class="font-bold text-base mt-1.5" style="color:#C47A00;">
                        R$ {{ number_format((float) $oferta->price, 2, ',', '.') }}
                        @if($oferta->price_type && $item->item_type?->value !== 'produto')
                        <span class="text-xs font-normal text-gray-400">/ {{ $oferta->price_type->label() }}</span>
                        @endif
                    </p>
                    @endif
                    @if($oferta->modality)
                    <span class="inline-flex items-center mt-1.5 px-2 py-0.5 rounded-full text-xs font-medium"
                          style="background:#f0fdf4; color:#166534;">
                        {{ $oferta->modality->label() }}
                    </span>
                    @endif
                </div>
            </a>
            @endforeach
        </div>

        @if($items->hasPages())
        <div class="mt-10 flex justify-center">
            {{ $items->appends(request()->query())->links() }}
        </div>
        @endif
        @endif

    </div>
</main>

<script>
function catalogoFiltros() {
    return {};
}
</script>
@endsection
