<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matrícula confirmada</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; color: #333; margin: 0; padding: 0; }
        .container { max-width: 580px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb; }
        .header { background: #3D3000; padding: 32px 24px; text-align: center; }
        .header h1 { color: #F4E294; font-size: 20px; margin: 0; }
        .header p { color: #ccc; font-size: 13px; margin: 6px 0 0; }
        .body { padding: 28px 24px; }
        .body p { font-size: 15px; line-height: 1.6; }
        .card { background: #FFFBEB; border: 1px solid #F4E294; border-radius: 8px; padding: 16px 20px; margin: 20px 0; }
        .card strong { display: block; font-size: 16px; color: #3D3000; margin-bottom: 4px; }
        .card span { font-size: 13px; color: #666; }
        .btn { display: inline-block; margin-top: 20px; padding: 12px 28px; background: #E8A000; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px; }
        .footer { text-align: center; font-size: 12px; color: #aaa; padding: 16px; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Feira Esquerda Livre</h1>
        <p>Matrícula Confirmada</p>
    </div>
    <div class="body">
        <p>Olá, <strong>{{ $enrollment->user->name }}</strong>!</p>

        <p>Sua matrícula no curso abaixo foi confirmada com sucesso. Acesse a plataforma para começar a aprender.</p>

        <div class="card">
            <strong>{{ $enrollment->course->product->name }}</strong>
            @if($enrollment->expires_at)
                <span>Acesso válido até {{ $enrollment->expires_at->format('d/m/Y') }}</span>
            @else
                <span>Acesso vitalício</span>
            @endif
        </div>

        <a href="{{ url('/minha-conta/aprendizado') }}" class="btn">Acessar Meu Aprendizado</a>

        <p style="margin-top: 28px; font-size: 13px; color: #666;">
            Se você não realizou esta compra, entre em contato com nosso suporte.
        </p>
    </div>
    <div class="footer">
        Feira Esquerda Livre &copy; {{ date('Y') }}
    </div>
</div>
</body>
</html>
