<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitação recebida — Feira Esquerda Livre</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f9fafb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1a1a1a; }
        .wrapper { max-width: 560px; margin: 32px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #3D3000 0%, #7A5C00 50%, #C47A00 100%); padding: 36px 40px; text-align: center; }
        .header h1 { margin: 0; color: #F4E294; font-size: 22px; font-weight: 800; letter-spacing: -0.3px; }
        .header p { margin: 6px 0 0; color: #e8c96a; font-size: 13px; }
        .body { padding: 36px 40px; }
        .greeting { font-size: 18px; font-weight: 700; color: #1a1a1a; margin: 0 0 12px; }
        .text { font-size: 15px; color: #4b5563; line-height: 1.6; margin: 0 0 20px; }
        .box { background: #fffbeb; border: 2px solid #F4E294; border-radius: 10px; padding: 20px 24px; margin: 24px 0; }
        .box h3 { margin: 0 0 12px; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #7A5C00; }
        .box p { margin: 0; font-size: 14px; line-height: 1.6; color: #5C4500; }
        .footer { background: #F4E294; padding: 20px 40px; text-align: center; font-size: 12px; color: #7A5C00; }
        .footer a { color: #3D3000; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Feira Esquerda Livre</h1>
            <p>Economia Solidária &amp; Cultura Popular</p>
        </div>

        <div class="body">
            <p class="greeting">Olá, {{ $solicitacao->responsavel }}!</p>

            <p class="text">
                Obrigado por querer fazer parte da <strong>Feira Esquerda Livre</strong>. Recebemos a solicitação
                da loja <strong>{{ $solicitacao->nome_loja }}</strong> e ela já está na fila de avaliação da nossa equipe.
            </p>

            <div class="box">
                <h3>Próximo passo</h3>
                <p>
                    Assim que sua solicitação for aprovada, enviaremos um novo e-mail para
                    <strong>{{ $solicitacao->email }}</strong> com as credenciais de acesso ao painel da sua loja.
                </p>
            </div>

            <p class="text">
                Enquanto isso, não é necessário criar conta manualmente. A conta será criada automaticamente no momento
                da aprovação administrativa.
            </p>

            <p class="text" style="font-size: 13px; color: #6b7280; margin: 0;">
                Se você não fez esta solicitação, ignore esta mensagem.
            </p>
        </div>

        <div class="footer">
            <p style="margin: 0;">
                <a href="{{ url('/') }}">Feira Esquerda Livre</a> —
                Este é um e-mail automático, por favor não responda.
            </p>
        </div>
    </div>
</body>
</html>
