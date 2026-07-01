@extends('layouts.public')

@section('title', 'Pedido #' . $order->reference . ' - Feira Esquerda Livre')

@section('content')
@php
    $isMercadoPago = $order->payment_method === 'mercado_pago';
    $isPaid = $order->status === \App\Enums\OrderStatus::PagamentoConfirmado;
@endphp

<main class="max-w-3xl mx-auto px-4 sm:px-6 py-10">

    <div class="text-center mb-8">
        <div class="text-5xl mb-3">{{ $isPaid ? '✓' : '🎉' }}</div>
        <h1 class="text-2xl sm:text-3xl font-extrabold mb-1" style="color:#3D3000;">
            {{ $isPaid ? 'Pagamento confirmado!' : 'Pedido recebido!' }}
        </h1>
        <p class="text-sm text-gray-500">Referencia <strong>#{{ $order->reference }}</strong> - {{ $order->status->label() }}</p>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    @if($isMercadoPago)
        <div class="p-5 rounded-xl text-sm mb-8" style="background:#FDF8DC; color:#5C4500;">
            <p class="font-semibold mb-2">Pagamento via Mercado Pago</p>
            @if($isPaid)
                <p>Recebemos a confirmacao do Mercado Pago da Feira Esquerda Livre. As lojas ja podem preparar seu pedido.</p>
            @else
                <p>Finalize o pagamento no ambiente seguro do Mercado Pago da Feira Esquerda Livre. O pedido sera atualizado automaticamente quando o pagamento for confirmado.</p>
                <a href="{{ route('mercado-pago.pay', $order->reference) }}"
                   class="mt-4 inline-flex items-center justify-center px-6 py-3 rounded-xl text-white font-bold"
                   style="background:#E8A000; min-height:48px;">
                    Pagar agora com Mercado Pago
                </a>
            @endif
            <p class="mt-3">{{ $order->delivery_type->emoji() }} <strong>{{ $order->delivery_type->label() }}:</strong> {{ $order->shipping_note }}</p>
        </div>
    @else
        <div class="p-4 rounded-xl text-sm mb-8" style="background:#FDF8DC; color:#5C4500;">
            <strong>Pagamento manual:</strong> finalize o pagamento diretamente com cada loja usando os dados de PIX/banco abaixo
            e envie o comprovante pelo WhatsApp informado. A loja confirmara o recebimento no painel dela.
            <br>{{ $order->delivery_type->emoji() }} <strong>{{ $order->delivery_type->label() }}:</strong> {{ $order->shipping_note }}
        </div>
    @endif

    <div class="space-y-6 mb-8">
        @foreach($order->splits as $split)
        @php $expositor = $split->expositor; @endphp
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <div class="flex items-center justify-between gap-4 mb-4">
                <h2 class="font-bold text-base" style="color:#3D3000;">{{ $expositor?->name ?? 'Loja' }}</h2>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $split->status->value === 'confirmado' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                    {{ $split->status->label() }}
                </span>
            </div>

            <div class="space-y-1.5 mb-4">
                @foreach($order->items->where('expositor_id', $expositor?->id) as $item)
                <div class="flex justify-between gap-4 text-sm">
                    <span class="text-gray-600">{{ $item->quantity }}x {{ $item->product_name }}</span>
                    <span class="font-semibold text-gray-800">R$ {{ number_format($item->total_price, 2, ',', '.') }}</span>
                </div>
                @endforeach
            </div>

            <div class="flex justify-between items-center border-t border-gray-100 pt-3 mb-4">
                <span class="font-semibold text-gray-700 text-sm">Subtotal da loja</span>
                <span class="font-bold" style="color:#3D3000;">R$ {{ number_format($split->gross_amount, 2, ',', '.') }}</span>
            </div>

            @if(! $isMercadoPago && $expositor?->pix_chave)
            <div class="p-3 rounded-lg bg-gray-50 border border-gray-100 text-sm">
                <p class="font-semibold text-gray-800 mb-1">Pagar via PIX</p>
                <p class="text-gray-600">Tipo: <strong>{{ $expositor->pix_tipo }}</strong></p>
                <p class="text-gray-600 break-all">Chave: <strong>{{ $expositor->pix_chave }}</strong></p>
            </div>
            @endif

            @if($expositor?->whatsapp)
            <a href="https://wa.me/55{{ preg_replace('/\D/', '', $expositor->whatsapp) }}?text={{ rawurlencode('Ola! Acabei de fazer o pedido #' . $order->reference . ' na Feira Esquerda Livre.') }}"
               target="_blank"
               class="mt-3 inline-flex items-center gap-2 text-sm font-semibold" style="color:#16a34a;">
                Falar com a loja no WhatsApp
            </a>
            @endif
        </div>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-8">
        @if($order->delivery_type->value === 'retirada')
        <h2 class="font-bold text-base mb-3" style="color:#3D3000;">Retirada no local</h2>
        <p class="text-sm text-gray-600">Combine o local e horario diretamente com o(s) lojista(s) pelo WhatsApp.</p>
        @else
        <h2 class="font-bold text-base mb-3" style="color:#3D3000;">Endereco de entrega</h2>
        <p class="text-sm text-gray-600">
            {{ $order->address_rua }}, {{ $order->address_numero }}
            @if($order->address_complemento) - {{ $order->address_complemento }} @endif
            <br>
            {{ $order->address_bairro }} - {{ $order->address_cidade }}/{{ $order->address_estado }} - CEP {{ $order->address_cep }}
        </p>
        @endif
    </div>

    <div class="flex justify-between items-center mb-10 px-2">
        <span class="font-semibold text-gray-700">Total do pedido</span>
        <span class="text-2xl font-bold" style="color:#3D3000;">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</span>
    </div>

    <div class="text-center flex flex-col sm:flex-row items-center justify-center gap-4">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-white" style="background:#3D3000;">
            Voltar ao inicio
        </a>
        @auth
        <a href="{{ route('cliente.pedidos.index') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold border-2" style="color:#3D3000; border-color:#3D3000;">
            Ver meus pedidos
        </a>
        @endauth
    </div>
</main>
@endsection
