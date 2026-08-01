<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recebemos sua mensagem</title>
    <style>
        body { margin: 0; padding: 0; background: #FDF8DC; font-family: Arial, sans-serif; color: #1a1a1a; }
        .wrapper { max-width: 620px; margin: 32px auto; background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #F0D060; box-shadow: 0 10px 28px rgba(61,48,0,0.08); }
        .header { background: #3D3000; padding: 34px 36px; text-align: center; }
        .badge { display: inline-block; padding: 7px 14px; border-radius: 999px; background: #F4E294; color: #3D3000; font-size: 12px; font-weight: 700; margin-bottom: 14px; }
        .header h1 { margin: 0; color: #ffffff; font-size: 26px; line-height: 1.2; }
        .header p { margin: 10px 0 0; color: #F4E294; font-size: 14px; line-height: 1.5; }
        .body { padding: 34px 36px; }
        .hello { margin: 0 0 14px; color: #3D3000; font-size: 20px; font-weight: 800; }
        .text { margin: 0 0 18px; color: #4B5563; font-size: 15px; line-height: 1.7; }
        .summary { margin: 24px 0; padding: 20px; background: #FDF8DC; border: 1px solid #F0D060; border-radius: 12px; }
        .summary-title { margin: 0 0 12px; color: #7A5C00; font-size: 12px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        .line { margin: 8px 0; color: #3D3000; font-size: 14px; line-height: 1.5; }
        .line strong { color: #1A1A1A; }
        .notice { margin: 22px 0 0; padding: 16px 18px; background: #E8F4EA; border-radius: 12px; color: #2D6A30; font-size: 14px; line-height: 1.6; }
        .footer { padding: 22px 36px; background: #F4E294; color: #5C4000; font-size: 12px; line-height: 1.6; text-align: center; }
        .footer a { color: #3D3000; font-weight: 700; text-decoration: none; }
        @media (max-width: 640px) {
            .wrapper { margin: 0; border-radius: 0; }
            .header, .body, .footer { padding-left: 22px; padding-right: 22px; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="badge">Feira Esquerda Livre</div>
            <h1>Recebemos sua mensagem</h1>
            <p>Obrigado por entrar em contato com a nossa equipe.</p>
        </div>

        <div class="body">
            <p class="hello">Olá, {{ $dados['name'] }}!</p>

            <p class="text">
                Sua mensagem foi recebida com sucesso pela <strong>Feira Esquerda Livre</strong>.
                Nossa equipe vai analisar o conteúdo enviado e retornará em breve pelos contatos informados no formulário.
            </p>

            <div class="summary">
                <p class="summary-title">Resumo do envio</p>
                <p class="line"><strong>Assunto:</strong> {{ $dados['subject'] }}</p>
                <p class="line"><strong>E-mail informado:</strong> {{ $dados['email'] }}</p>
                @if(!empty($dados['phone']))
                    <p class="line"><strong>Telefone / WhatsApp:</strong> {{ $dados['phone'] }}</p>
                @endif
            </div>

            <p class="text">
                Você não precisa responder este e-mail. Esta é uma confirmação automática para avisar que sua solicitação chegou até nós.
            </p>

            <div class="notice">
                Se precisar complementar alguma informação, envie uma nova mensagem pela página de contato do site.
            </div>
        </div>

        <div class="footer">
            <p style="margin: 0;">
                <a href="{{ url('/') }}">Feira Esquerda Livre</a><br>
                Economia solidária, cultura popular e cuidado coletivo.
            </p>
        </div>
    </div>
</body>
</html>
