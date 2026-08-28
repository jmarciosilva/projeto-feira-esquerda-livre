@extends('layouts.public')

@section('title', 'Pedido #' . $order->reference . ' - Feira Esquerda Livre')

@section('content')
@php
    $isMercadoPago = $order->payment_method === 'mercado_pago';
    $isPaid = $order->status === \App\Enums\OrderStatus::PagamentoConfirmado;
    $shippings = $order->shippings ?? collect();
    $mpSettings = \App\Models\SiteSetting::instance();
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
                <p class="mb-4">Finalize o pagamento abaixo, sem sair desta página.</p>

                <div id="mp-payment-error" class="hidden mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"></div>
                <div id="mp-pix-result" class="hidden mb-4 p-4 rounded-lg bg-white border border-gray-200 text-center"></div>

                <div id="paymentBrick_container"></div>

                @push('scripts')
                <script src="https://sdk.mercadopago.com/js/v2"></script>
                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const mp = new MercadoPago(@json($mpSettings->mercado_pago_public_key), { locale: 'pt-BR' });

                    const errorBox = document.getElementById('mp-payment-error');
                    const pixBox = document.getElementById('mp-pix-result');
                    const payUrl = @json(route('mercado-pago.pay.process', $order->reference));
                    const statusUrl = @json(route('pedido.status', $order->reference));

                    function showError(message) {
                        errorBox.textContent = message;
                        errorBox.classList.remove('hidden');
                    }

                    function pollPaymentStatus() {
                        let attempts = 0;
                        const interval = setInterval(function () {
                            attempts++;
                            fetch(statusUrl)
                                .then((response) => response.json())
                                .then((data) => {
                                    if (data.status === 'approved') {
                                        clearInterval(interval);
                                        window.location.reload();
                                    }
                                })
                                .catch(() => {});
                            if (attempts >= 60) clearInterval(interval);
                        }, 5000);
                    }

                    mp.bricks().create('payment', 'paymentBrick_container', {
                        initialization: {
                            amount: {{ (float) $order->total_amount }},
                            payer: { email: @json($order->customer_email) },
                        },
                        customization: {
                            paymentMethods: {
                                creditCard: 'all',
                                debitCard: 'all',
                                bankTransfer: 'all',
                                ticket: 'all',
                            },
                        },
                        callbacks: {
                            onReady: () => {},
                            onError: (error) => {
                                console.error(error);
                                showError('Não foi possível carregar o pagamento agora. Recarregue a página e tente novamente.');
                            },
                            onSubmit: ({ formData }) => {
                                errorBox.classList.add('hidden');

                                return new Promise((resolve, reject) => {
                                    fetch(payUrl, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                        },
                                        body: JSON.stringify(formData),
                                    })
                                        .then((response) => response.json())
                                        .then((result) => {
                                            if (result.status === 'approved') {
                                                window.location.reload();
                                            } else if (result.status === 'pending' && result.pix && result.pix.qr_code_base64) {
                                                pixBox.innerHTML =
                                                    '<p class="font-semibold text-gray-800 mb-3">Escaneie o QR Code do Pix para pagar</p>' +
                                                    '<img src="data:image/png;base64,' + result.pix.qr_code_base64 + '" alt="QR Code Pix" class="mx-auto mb-3" style="max-width:220px;">' +
                                                    '<p class="text-xs text-gray-500 mb-2">Ou copie o código:</p>' +
                                                    '<textarea readonly class="w-full text-xs p-2 border rounded-lg" rows="3" onclick="this.select()">' + result.pix.qr_code + '</textarea>' +
                                                    '<p class="text-xs text-gray-500 mt-3">Esta página será atualizada automaticamente assim que o pagamento for confirmado.</p>';
                                                pixBox.classList.remove('hidden');
                                                pollPaymentStatus();
                                            } else if (result.status === 'pending' && result.ticket_url) {
                                                window.open(result.ticket_url, '_blank');
                                                showError('Boleto gerado em uma nova aba. Após o pagamento, pode levar até 3 dias úteis para compensar.');
                                                pollPaymentStatus();
                                            } else if (result.status === 'rejected') {
                                                showError('Pagamento recusado: ' + (result.status_detail || 'tente outro cartão ou forma de pagamento.'));
                                            } else if (result.status === 'error') {
                                                showError(result.message || 'Não foi possível processar o pagamento agora.');
                                            } else {
                                                showError('Pagamento em análise. Assim que confirmado, atualizaremos seu pedido.');
                                                pollPaymentStatus();
                                            }
                                            resolve();
                                        })
                                        .catch((error) => {
                                            console.error(error);
                                            showError('Não foi possível processar o pagamento agora. Tente novamente.');
                                            reject();
                                        });
                                });
                            },
                        },
                    });
                });
                </script>
                @endpush
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
        @php
            $expositor = $split->expositor;
            $nomeDaLoja = $expositor?->name ?? $split->expositor_name ?? 'Loja';
            // Agrupa pelo vinculo vivo quando ele existe e, sem ele, pelo nome
            // gravado na compra: o pedido continua legivel sem o cadastro.
            $itensDaLoja = $expositor
                ? $order->items->where('expositor_id', $expositor->id)
                : $order->items->whereNull('expositor_id')->where('expositor_name', $split->expositor_name);
        @endphp
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <div class="flex items-center justify-between gap-4 mb-4">
                <h2 class="font-bold text-base" style="color:#3D3000;">{{ $nomeDaLoja }}</h2>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $split->status->value === 'confirmado' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                    {{ $split->status->label() }}
                </span>
            </div>

            <div class="space-y-1.5 mb-4">
                @foreach($itensDaLoja as $item)
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

            @auth
            @if(auth()->id() === $order->user_id)
            <div class="mt-5 border-t border-gray-100 pt-5">
                <p class="text-sm font-semibold text-gray-700 mb-3">Mensagens com a loja</p>
                <livewire:order-chat :split="$split" :key="'chat-'.$split->id" />
            </div>
            @endif
            @endauth
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

    {{-- Rastreio de envio --}}
    @if($shippings->isNotEmpty())
    <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-8">
        <h2 class="font-bold text-base mb-4" style="color:#3D3000;">Rastreio de entrega</h2>
        <div class="space-y-3">
            @foreach($shippings as $shipping)
            <div class="flex items-center justify-between gap-4 p-3 bg-gray-50 rounded-xl">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-800">{{ $shipping->expositor?->name }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $shipping->status->icon() }} {{ $shipping->status->label() }}
                        @if($shipping->carrier) &mdash; {{ $shipping->carrier }} @endif
                        @if($shipping->tracking_code)
                        &mdash; <span class="font-mono">{{ $shipping->tracking_code }}</span>
                        @endif
                    </p>
                </div>
                @if($shipping->tracking_code)
                <a href="{{ route('rastreio.show', $shipping->tracking_code) }}"
                   class="flex-shrink-0 inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-semibold"
                   style="background:#1a472a; color:#F4E294;">
                    Rastrear
                </a>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

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
