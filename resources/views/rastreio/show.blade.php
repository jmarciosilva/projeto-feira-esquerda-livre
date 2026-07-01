@extends('layouts.public')

@section('title', 'Rastreio ' . $shipping->tracking_code . ' — Feira Esquerda Livre')

@section('content')
@php
    use App\Enums\ShippingStatus;
    $order     = $shipping->order;
    $expositor = $shipping->expositor;
    $events    = $shipping->trackingEvents;
    $timeline  = ShippingStatus::timeline();
    $currentIndex = collect($timeline)->search($shipping->status) ?? 0;
    $estimatedDate = $shipping->estimatedDeliveryDate();
    $carrierUrl = $shipping->carrierTrackingUrl();
@endphp

<main class="max-w-2xl mx-auto px-4 sm:px-6 py-10">

    {{-- Header --}}
    <div class="text-center mb-8">
        <p class="text-3xl mb-3">{{ $shipping->status->icon() }}</p>
        <h1 class="text-2xl sm:text-3xl font-extrabold mb-1" style="color:#1a472a;">
            {{ $shipping->status->label() }}
        </h1>
        @if($shipping->tracking_code)
        <p class="text-sm text-gray-500 font-mono">
            Código: <strong>{{ $shipping->tracking_code }}</strong>
        </p>
        @endif
        @if($expositor)
        <p class="text-sm text-gray-500 mt-1">
            Enviado por <strong>{{ $expositor->name }}</strong>
            @if($order) &mdash; pedido <strong>#{{ $order->reference }}</strong> @endif
        </p>
        @endif
    </div>

    {{-- Previsão de entrega --}}
    @if($estimatedDate && ! $shipping->isDelivered())
    <div class="rounded-xl border border-yellow-100 bg-yellow-50 px-5 py-4 text-center mb-8">
        <p class="text-sm font-semibold text-yellow-800">
            Previsão de entrega: <strong>{{ $estimatedDate->isoFormat('dddd, D [de] MMMM') }}</strong>
        </p>
        @if($shipping->shipped_at)
        <p class="text-xs text-yellow-600 mt-1">
            Enviado em {{ $shipping->shipped_at->format('d/m/Y') }}
            @if($shipping->carrier) via {{ $shipping->carrier }} @endif
        </p>
        @endif
    </div>
    @endif

    @if($shipping->isDelivered())
    <div class="rounded-xl border border-green-100 bg-green-50 px-5 py-4 text-center mb-8">
        <p class="text-sm font-semibold text-green-800">
            Entregue em {{ $shipping->delivered_at?->format('d/m/Y') ?? '—' }}
        </p>
    </div>
    @endif

    {{-- Barra de progresso visual --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-8">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-5">Progresso da entrega</h2>

        <div class="relative">
            {{-- Linha de fundo --}}
            <div class="absolute left-4 top-4 bottom-4 w-0.5 bg-gray-200" aria-hidden="true"></div>

            <div class="space-y-5">
                @foreach($timeline as $i => $step)
                @php
                    $isActive    = $i <= $currentIndex && ! in_array($shipping->status, [\App\Enums\ShippingStatus::Returned, \App\Enums\ShippingStatus::Failed]);
                    $isCurrent   = $shipping->status === $step;
                @endphp
                <div class="relative flex items-start gap-4">
                    <div class="z-10 flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm
                        {{ $isCurrent ? 'ring-2 ring-offset-2' : '' }}"
                         style="{{ $isActive ? 'background:#1a472a; color:#F4E294; ring-color:#1a472a;' : 'background:#e5e7eb; color:#9ca3af;' }}">
                        @if($isActive)
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        @else
                        <span class="text-xs font-bold">{{ $i + 1 }}</span>
                        @endif
                    </div>
                    <div class="pt-1">
                        <p class="text-sm font-semibold {{ $isActive ? 'text-gray-900' : 'text-gray-400' }}">
                            {{ $step->label() }}
                        </p>
                        @if($isCurrent && $events->first())
                        <p class="text-xs text-gray-500 mt-0.5">{{ $events->first()->happened_at->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>
                </div>
                @endforeach

                {{-- Erro / Devolução (fora do fluxo normal) --}}
                @if(in_array($shipping->status, [\App\Enums\ShippingStatus::Returned, \App\Enums\ShippingStatus::Failed]))
                <div class="relative flex items-start gap-4">
                    <div class="z-10 flex-shrink-0 w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center text-sm">
                        ⚠
                    </div>
                    <div class="pt-1">
                        <p class="text-sm font-semibold text-red-700">{{ $shipping->status->label() }}</p>
                        @if($events->first())
                        <p class="text-xs text-gray-500 mt-0.5">{{ $events->first()->happened_at->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Linha do tempo detalhada --}}
    @if($events->count())
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-8">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-5">Histórico detalhado</h2>

        <div class="space-y-4">
            @foreach($events as $event)
            <div class="flex gap-4">
                <div class="flex-shrink-0 text-center w-12">
                    <p class="text-xs font-bold text-gray-800">{{ $event->happened_at->format('d/m') }}</p>
                    <p class="text-xs text-gray-400">{{ $event->happened_at->format('H:i') }}</p>
                </div>
                <div class="flex-1 pb-4 border-b border-gray-50 last:border-b-0 last:pb-0">
                    <p class="text-sm font-semibold text-gray-800">{{ $event->description }}</p>
                    @if($event->location)
                    <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        {{ $event->location }}
                    </p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Ações --}}
    <div class="flex flex-col sm:flex-row gap-3">
        @if($carrierUrl && $shipping->tracking_code)
        <a href="{{ $carrierUrl }}" target="_blank" rel="noopener"
           class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl font-semibold text-sm border-2"
           style="border-color:#1a472a; color:#1a472a;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            Rastrear no site da {{ $shipping->carrier ?? 'transportadora' }}
        </a>
        @endif

        @if($order)
        <a href="{{ route('pedido.show', $order->reference) }}"
           class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl font-bold text-sm text-white"
           style="background:#1a472a;">
            Ver detalhes do pedido
        </a>
        @endif
    </div>

    <p class="text-center text-xs text-gray-400 mt-8">
        <a href="{{ url('/') }}" style="color:#1a472a; font-weight:600;">← Voltar à Feira Esquerda Livre</a>
    </p>

</main>
@endsection
