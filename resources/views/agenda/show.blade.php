@extends('layouts.public')

@section('title', $event->title . ' — Feira Esquerda Livre')
@section('description', $event->description ? Str::limit($event->description, 160) : 'Feira ' . $event->title . ' em ' . $event->city . ', ' . $event->state)

@section('content')
@php use Illuminate\Support\Str; @endphp
<main>

    {{-- Banner --}}
    @if($event->banner_image_path)
    <div class="w-full overflow-hidden" style="max-height:400px;">
        <img src="{{ Storage::url($event->banner_image_path) }}" alt="{{ $event->title }}"
             class="w-full object-cover" style="max-height:400px;">
    </div>
    @elseif($event->image_path)
    <div class="w-full overflow-hidden" style="max-height:320px;">
        <img src="{{ Storage::url($event->image_path) }}" alt="{{ $event->title }}"
             class="w-full object-cover" style="max-height:320px;">
    </div>
    @else
    <div class="w-full py-16 flex items-center justify-center" style="background:linear-gradient(135deg,#F4E294,#E8A000);">
        <div class="text-center">
            <p class="text-6xl mb-3">🏪</p>
            <h1 class="text-3xl font-bold" style="color:#3D3000;">{{ $event->title }}</h1>
        </div>
    </div>
    @endif

    <div class="max-w-4xl mx-auto px-4 py-8">

        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-500 mb-6">
            <a href="{{ route('agenda.index') }}" class="hover:underline">Agenda</a>
            <span class="mx-2">›</span>
            <span class="text-gray-900">{{ $event->title }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- Conteúdo principal --}}
            <div class="lg:col-span-2">
                @if($event->banner_image_path || $event->image_path)
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">{{ $event->title }}</h1>
                @endif

                @if($event->description)
                <div class="prose prose-base max-w-none text-gray-700 mb-8">
                    {!! nl2br(e($event->description)) !!}
                </div>
                @endif

                {{-- Expositores confirmados --}}
                @if($event->expositores->isNotEmpty())
                <div class="mt-8">
                    <h2 class="text-xl font-bold mb-4" style="color:#3D3000;">
                        Expositores Confirmados ({{ $event->expositores->count() }})
                    </h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @foreach($event->expositores as $expositor)
                        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center hover:border-[#F4E294] transition-colors">
                            @if($expositor->logo_path)
                            <img src="{{ Storage::url($expositor->logo_path) }}" alt="{{ $expositor->name }}"
                                 class="w-16 h-16 rounded-full object-cover mx-auto mb-2 border-2 border-[#F4E294]">
                            @else
                            <div class="w-16 h-16 rounded-full mx-auto mb-2 flex items-center justify-center text-xl font-bold border-2 border-[#F4E294]"
                                 style="background:#FDF8DC; color:#3D3000;">
                                {{ strtoupper(substr($expositor->name, 0, 1)) }}
                            </div>
                            @endif
                            <p class="text-sm font-semibold text-gray-900">{{ $expositor->name }}</p>
                            @if($expositor->city)
                            <p class="text-xs text-gray-500">{{ $expositor->city }}@if($expositor->state)/{{ $expositor->state }}@endif</p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Sidebar de informações --}}
            <div class="space-y-4">

                {{-- Data e horário --}}
                <div class="bg-white rounded-2xl border-2 p-5" style="border-color:#F4E294;">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-xl flex flex-col items-center justify-center flex-shrink-0" style="background:#F4E294;">
                            <p class="text-lg font-bold leading-none" style="color:#3D3000;">{{ $event->start_date->format('d') }}</p>
                            <p class="text-xs font-semibold" style="color:#E8A000;">{{ $event->start_date->isoFormat('MMM') }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ $event->start_date->isoFormat('dddd') }}</p>
                            <p class="text-sm text-gray-500">{{ $event->start_date->format('H:i') }}h</p>
                        </div>
                    </div>
                    @if($event->end_date)
                    <div class="text-sm text-gray-600 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7"/></svg>
                        Até {{ $event->end_date->isoFormat('dddd, DD [de] MMMM') }} às {{ $event->end_date->format('H:i') }}h
                    </div>
                    @endif
                </div>

                {{-- Local --}}
                @if($event->city || $event->address)
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" style="color:#E8A000;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Local
                    </h3>
                    @if($event->city)
                    <p class="text-gray-700 font-medium">{{ $event->city }}@if($event->state) — {{ $event->state }}@endif</p>
                    @endif
                    @if($event->address)
                    <p class="text-sm text-gray-500 mt-1">{{ $event->address }}</p>
                    @endif
                    @if($event->address)
                    <a href="https://maps.google.com?q={{ urlencode($event->address . ', ' . $event->city . ', ' . $event->state) }}"
                       target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 mt-3 text-sm font-medium px-4 py-2 rounded-lg transition-colors"
                       style="background:#FDF8DC; color:#3D3000;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        Ver no mapa
                    </a>
                    @endif
                </div>
                @endif

                {{-- Vagas --}}
                @if($event->capacidade_expositores !== null)
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <h3 class="font-semibold text-gray-900 mb-3">Vagas para Expositores</h3>
                    @php
                        $cap = $event->capacidade_expositores;
                        $vagas = $event->vagas_restantes ?? $cap;
                        $pct = $cap > 0 ? round((($cap - $vagas) / $cap) * 100) : 100;
                    @endphp
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-gray-600">{{ $vagas }} vagas restantes</span>
                        <span class="text-gray-500">de {{ $cap }}</span>
                    </div>
                    <div class="w-full rounded-full h-3" style="background:#e5e7eb;">
                        <div class="h-3 rounded-full transition-all" style="background:#E8A000; width:{{ $pct }}%;"></div>
                    </div>
                    @if($vagas === 0)
                    <p class="text-sm text-red-600 font-medium mt-2">Vagas esgotadas</p>
                    @endif
                </div>
                @endif

                {{-- Ações --}}
                <div class="space-y-3">
                    @if($event->registration_url)
                    <a href="{{ $event->registration_url }}" target="_blank" rel="noopener"
                       class="flex items-center justify-center gap-2 w-full py-4 rounded-xl font-bold text-lg transition-opacity hover:opacity-90"
                       style="background:#5C8A3C; color:#fff; min-height:56px;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        Inscrever-se
                    </a>
                    @endif

                    <a href="{{ route('seja-um-expositor') }}"
                       class="flex items-center justify-center gap-2 w-full py-4 rounded-xl font-bold text-lg transition-opacity hover:opacity-90"
                       style="background:#F4E294; color:#3D3000; min-height:56px;">
                        Quero Expor Nesta Feira
                    </a>

                    @php
                    $msgWhatsApp = urlencode("Olá! Vi a feira *{$event->title}* na Feira Esquerda Livre e gostaria de saber mais! " . route('agenda.show', $event->slug));
                    @endphp
                    <a href="https://wa.me/?text={{ $msgWhatsApp }}" target="_blank" rel="noopener"
                       class="flex items-center justify-center gap-2 w-full py-3 rounded-xl font-semibold text-base border-2 transition-colors hover:bg-green-50"
                       style="border-color:#25D366; color:#25D366;">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.135.56 4.14 1.54 5.877L0 24l6.337-1.521A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.368l-.36-.214-3.724.894.927-3.627-.235-.374A9.818 9.818 0 012.182 12C2.182 6.56 6.56 2.182 12 2.182S21.818 6.56 21.818 12 17.44 21.818 12 21.818z"/></svg>
                        Compartilhar no WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Outras feiras --}}
    @if($otherEvents->isNotEmpty())
    <div class="py-10 px-4" style="background:#FDF8DC;">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-xl font-bold mb-6" style="color:#3D3000;">Outras Feiras</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach($otherEvents as $other)
                <a href="{{ route('agenda.show', $other->slug) }}" class="group bg-white rounded-xl border-2 border-transparent hover:border-[#F4E294] transition-all p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-lg flex flex-col items-center justify-center flex-shrink-0" style="background:#F4E294;">
                            <p class="text-sm font-bold leading-none" style="color:#3D3000;">{{ $other->start_date->format('d') }}</p>
                            <p class="text-xs" style="color:#E8A000;">{{ $other->start_date->isoFormat('MMM') }}</p>
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 text-sm truncate group-hover:text-[#E8A000] transition-colors">{{ $other->title }}</p>
                            <p class="text-xs text-gray-500">{{ $other->city }}@if($other->state)/{{ $other->state }}@endif</p>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

</main>
@endsection
