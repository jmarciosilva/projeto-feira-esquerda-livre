@extends('layouts.public')

@section('title', 'Agenda de Feiras — Feira Esquerda Livre')
@section('description', 'Encontre feiras progressistas em todo o Brasil. Filtre por estado e data.')

@section('content')
<main>

    {{-- Hero --}}
    <div class="py-10 px-4" style="background:linear-gradient(135deg,#F4E294,#E8A000);">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl sm:text-4xl font-bold mb-2" style="color:#3D3000;">Agenda de Feiras</h1>
            <p class="text-lg" style="color:#5C3000;">Encontre feiras progressistas perto de você em todo o Brasil.</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="py-6 px-4" style="background:#FDF8DC;">
        <form method="GET" action="{{ route('agenda.index') }}" class="max-w-4xl mx-auto">
            <div class="flex flex-col sm:flex-row gap-4 items-end">
                <div class="flex-1">
                    <label for="estado" class="block text-sm font-semibold mb-2" style="color:#3D3000;">Estado</label>
                    <select id="estado" name="estado"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl text-base focus:outline-none focus:border-[#E8A000] transition-colors">
                        <option value="">Todos os estados</option>
                        @foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                        <option value="{{ $uf }}" {{ $estadoFiltro === $uf ? 'selected' : '' }}>{{ $uf }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1">
                    <label for="mes" class="block text-sm font-semibold mb-2" style="color:#3D3000;">Mês</label>
                    <select id="mes" name="mes"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl text-base focus:outline-none focus:border-[#E8A000] transition-colors">
                        <option value="">Todos os meses</option>
                        @foreach([
                            1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',
                            7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'
                        ] as $num => $nome)
                        <option value="{{ $num }}" {{ $mesFiltro == $num ? 'selected' : '' }}>{{ $nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1">
                    <label for="ano" class="block text-sm font-semibold mb-2" style="color:#3D3000;">Ano</label>
                    <select id="ano" name="ano"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl text-base focus:outline-none focus:border-[#E8A000] transition-colors">
                        @foreach(range(date('Y'), date('Y') + 1) as $y)
                        <option value="{{ $y }}" {{ $anoFiltro == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="submit"
                            class="px-6 py-3 rounded-xl font-bold text-base transition-opacity hover:opacity-90 whitespace-nowrap"
                            style="background:#E8A000; color:#fff; min-height:52px;">
                        Buscar
                    </button>
                    @if($estadoFiltro || $mesFiltro)
                    <a href="{{ route('agenda.index') }}"
                       class="px-4 py-3 rounded-xl font-medium text-sm border-2 border-gray-300 text-gray-600 hover:border-gray-400 transition-colors whitespace-nowrap flex items-center">
                        Limpar
                    </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Lista de Eventos --}}
    <div class="max-w-4xl mx-auto px-4 py-8">

        @if($events->isEmpty())
        <div class="text-center py-16">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <h3 class="text-xl font-semibold text-gray-500 mb-2">Nenhuma feira encontrada</h3>
            <p class="text-gray-400 mb-6">Tente outros filtros ou volte em breve.</p>
            <a href="{{ route('agenda.index') }}" class="inline-block px-6 py-3 rounded-xl font-semibold" style="background:#F4E294; color:#3D3000;">
                Ver todas as feiras
            </a>
        </div>
        @else

        <p class="text-sm text-gray-500 mb-6">
            {{ $events->total() }} {{ $events->total() === 1 ? 'feira encontrada' : 'feiras encontradas' }}
            @if($estadoFiltro) em <strong>{{ $estadoFiltro }}</strong>@endif
        </p>

        <div class="space-y-4">
            @foreach($events as $event)
            <a href="{{ route('agenda.show', $event->slug) }}" class="block group">
                <div class="bg-white rounded-2xl border-2 border-transparent hover:border-[#F4E294] transition-all shadow-sm hover:shadow-md p-0 overflow-hidden">
                    <div class="flex items-stretch">
                        {{-- Data badge --}}
                        <div class="flex flex-col items-center justify-center px-5 py-4 flex-shrink-0 text-center"
                             style="background:#F4E294; min-width:80px;">
                            <p class="text-2xl font-bold leading-none" style="color:#3D3000;">{{ $event->start_date->format('d') }}</p>
                            <p class="text-sm font-semibold mt-0.5" style="color:#E8A000;">{{ $event->start_date->isoFormat('MMM') }}</p>
                            <p class="text-xs" style="color:#5C3000;">{{ $event->start_date->format('Y') }}</p>
                        </div>

                        {{-- Imagem (se houver) --}}
                        @if($event->image_path)
                        <div class="w-24 sm:w-32 flex-shrink-0 overflow-hidden">
                            <img src="{{ Storage::url($event->image_path) }}" alt="{{ $event->title }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        </div>
                        @endif

                        {{-- Info --}}
                        <div class="flex-1 p-4 sm:p-5">
                            <div class="flex flex-wrap items-start gap-2 mb-1">
                                <h2 class="text-base sm:text-lg font-bold text-gray-900 group-hover:text-[#E8A000] transition-colors">
                                    {{ $event->title }}
                                </h2>
                                @if($event->is_featured)
                                <span class="text-xs px-2 py-0.5 rounded-full font-semibold flex-shrink-0" style="background:#F4E294; color:#3D3000;">Destaque</span>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-3 text-sm text-gray-600">
                                @if($event->city || $event->state)
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ $event->city }}@if($event->city && $event->state), @endif{{ $event->state }}
                                </span>
                                @endif

                                @if($event->end_date)
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    até {{ $event->end_date->format('d/m/Y') }}
                                </span>
                                @endif

                                @if($event->vagas_restantes !== null)
                                <span class="flex items-center gap-1 {{ $event->vagas_restantes === 0 ? 'text-red-600' : 'text-green-600' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ $event->vagas_restantes === 0 ? 'Vagas esgotadas' : $event->vagas_restantes . ' vagas restantes' }}
                                </span>
                                @endif
                            </div>

                            @if($event->description)
                            <p class="text-sm text-gray-500 mt-2 line-clamp-2">{{ $event->description }}</p>
                            @endif
                        </div>

                        <div class="flex items-center pr-4 flex-shrink-0">
                            <svg class="w-5 h-5 text-gray-300 group-hover:text-[#E8A000] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </a>
            @endforeach
        </div>

        {{-- Paginação --}}
        <div class="mt-8">{{ $events->withQueryString()->links() }}</div>
        @endif
    </div>

    {{-- CTA --}}
    <div class="py-12 px-4 text-center" style="background:#FDF8DC;">
        <h3 class="text-xl font-bold mb-2" style="color:#3D3000;">Quer expor na próxima feira?</h3>
        <p class="text-gray-600 mb-5">Cadastre sua loja e receba convites para as feiras da sua região.</p>
        <a href="{{ route('seja-um-expositor') }}"
           class="inline-block px-8 py-4 rounded-xl font-bold text-lg transition-opacity hover:opacity-90"
           style="background:#F4E294; color:#3D3000;">
            Quero ser Expositor
        </a>
    </div>
</main>
@endsection
