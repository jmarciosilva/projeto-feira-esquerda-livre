<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seu pedido foi enviado — Feira Esquerda Livre</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f9fafb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1a1a1a; }
        .wrapper { max-width: 560px; margin: 32px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .header { background: #1a472a; padding: 32px 40px; text-align: center; }
        .header h1 { margin: 0; color: #F4E294; font-size: 22px; font-weight: 800; }
        .header p { margin: 8px 0 0; color: #a7c7a0; font-size: 14px; }
        .body { padding: 34px 40px; }
        .text { font-size: 15px; color: #4b5563; line-height: 1.6; margin: 0 0 20px; }
        .info-box { background: #fffbeb; border: 2px solid #F4E294; border-radius: 10px; padding: 20px 24px; margin: 24px 0; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; }
        .info-row:last-child { margin-bottom: 0; }
        .info-label { color: #6b7280; }
        .info-value { font-weight: 700; color: #1a1a1a; }
        .btn { display: inline-block; background-color: #1a472a; color: #F4E294 !important; text-decoration: none; font-weight: 700; font-size: 15px; padding: 14px 32px; border-radius: 8px; }
        .timeline { margin: 24px 0; }
        .tl-step { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px; }
        .tl-dot { width: 24px; height: 24px; border-radius: 50%; background: #1a472a; color: #F4E294; font-size: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px; }
        .tl-dot.inactive { background: #e5e7eb; color: #9ca3af; }
        .tl-text { font-size: 14px; }
        .tl-text strong { display: block; color: #1a1a1a; }
        .tl-text span { color: #6b7280; font-size: 12px; }
        .footer { background: #F4E294; padding: 18px 40px; text-align: center; font-size: 12px; color: #7A5C00; }
        .footer a { color: #5C4500; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>🚚 Seu pedido foi enviado!</h1>
        <p>Feira Esquerda Livre</p>
    </div>

    <div class="body">
        <p class="text">
            Olá, <strong>{{ $shipping->order?->customer_name ?? 'Cliente' }}</strong>!<br>
            A loja <strong>{{ $shipping->expositor?->name ?? 'Loja' }}</strong> acabou de despachar os seus itens do pedido
            <strong>#{{ $shipping->order?->reference }}</strong>.
        </p>

        <div class="info-box">
            @if($shipping->carrier)
            <div class="info-row">
                <span class="info-label">Transportadora</span>
                <span class="info-value">{{ $shipping->carrier }}{{ $shipping->service_name ? ' — ' . $shipping->service_name : '' }}</span>
            </div>
            @endif
            @if($shipping->tracking_code)
            <div class="info-row">
                <span class="info-label">Código de rastreio</span>
                <span class="info-value" style="font-family: 'Courier New', monospace;">{{ $shipping->tracking_code }}</span>
            </div>
            @endif
            @if($shipping->estimatedDeliveryDate())
            <div class="info-row">
                <span class="info-label">Previsão de entrega</span>
                <span class="info-value">{{ $shipping->estimatedDeliveryDate()->format('d/m/Y') }}</span>
            </div>
            @endif
        </div>

        <p style="text-align: center; margin: 28px 0;">
            <a href="{{ route('rastreio.show', $shipping->tracking_code ?? $shipping->id) }}" class="btn">
                Rastrear meu pedido
            </a>
        </p>

        <p class="text" style="font-size: 13px; color: #9ca3af; margin-top: 32px;">
            Você está recebendo este e-mail porque realizou uma compra na Feira Esquerda Livre.<br>
            Pedido: <strong>#{{ $shipping->order?->reference }}</strong>
        </p>
    </div>

    <div class="footer">
        <p style="margin: 0;">Feira Esquerda Livre · <a href="{{ url('/') }}">feiraesquerdalivre.com.br</a></p>
    </div>
</div>
</body>
</html>
