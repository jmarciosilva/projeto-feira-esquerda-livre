<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitação Aprovada — Feira Esquerda Livre</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f9fafb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1a1a1a; }
        .wrapper { max-width: 560px; margin: 32px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #3D3000 0%, #7A5C00 50%, #C47A00 100%); padding: 36px 40px; text-align: center; }
        .header h1 { margin: 0; color: #F4E294; font-size: 22px; font-weight: 800; letter-spacing: -0.3px; }
        .header p { margin: 6px 0 0; color: #e8c96a; font-size: 13px; }
        .body { padding: 36px 40px; }
        .greeting { font-size: 18px; font-weight: 700; color: #1a1a1a; margin: 0 0 12px; }
        .text { font-size: 15px; color: #4b5563; line-height: 1.6; margin: 0 0 20px; }
        .credentials { background: #fffbeb; border: 2px solid #F4E294; border-radius: 10px; padding: 20px 24px; margin: 24px 0; }
        .credentials h3 { margin: 0 0 14px; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #7A5C00; }
        .cred-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .cred-row:last-child { margin-bottom: 0; }
        .cred-label { font-size: 13px; color: #6b7280; min-width: 70px; }
        .cred-value { font-size: 15px; font-weight: 700; color: #1a1a1a; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; padding: 6px 12px; font-family: 'Courier New', monospace; flex: 1; word-break: break-all; }
        .btn-wrapper { text-align: center; margin: 28px 0; }
        .btn { display: inline-block; background-color: #C47A00; color: #ffffff !important; text-decoration: none; font-weight: 700; font-size: 15px; padding: 14px 36px; border-radius: 8px; }
        .warning { background: #fef3c7; border-left: 4px solid #F59E0B; border-radius: 6px; padding: 14px 16px; margin: 20px 0; font-size: 13px; color: #92400e; line-height: 1.5; }
        .footer { background: #F4E294; padding: 20px 40px; text-align: center; font-size: 12px; color: #7A5C00; }
        .footer a { color: #3D3000; text-decoration: none; font-weight: 600; }
        .divider { height: 1px; background: #f3f4f6; margin: 24px 0; }
    </style>
</head>
<body>
    <div class="wrapper">

        <div class="header">
            <h1>Feira Esquerda Livre</h1>
            <p>Economia Solidária &amp; Cultura Popular</p>
        </div>

        <div class="body">
            <p class="greeting">Olá, {{ $user->name }}! 🎉</p>

            <p class="text">
                Ótima notícia! Sua solicitação para cadastrar a loja <strong>{{ $nomeLoja }}</strong> na
                <strong>Feira Esquerda Livre</strong> foi <strong>aprovada</strong>.
            </p>

            <p class="text">
                Sua conta foi validada pela administração e já está liberada para autenticação.
                Abaixo estão seus dados de acesso ao painel de lojista, onde você poderá configurar
                sua loja, adicionar produtos e gerenciar sua participação nas feiras.
            </p>

            <div class="credentials">
                <h3>Seus Dados de Acesso</h3>
                <div class="cred-row">
                    <span class="cred-label">E-mail</span>
                    <span class="cred-value">{{ $user->email }}</span>
                </div>
                <div class="cred-row">
                    <span class="cred-label">Senha</span>
                    <span class="cred-value">{{ $senha }}</span>
                </div>
            </div>

            <div class="warning">
                ⚠️ <strong>Por segurança, altere sua senha assim que fizer o primeiro acesso.</strong>
                Guarde estas credenciais em local seguro — esta é a única vez que a senha será exibida.
            </div>

            <div class="btn-wrapper">
                <a href="{{ url('/login') }}" class="btn">Acessar Minha Loja</a>
            </div>

            <div class="divider"></div>

            <p class="text" style="font-size: 13px; color: #6b7280; margin: 0;">
                Se você não fez esta solicitação ou acredita que recebeu este e-mail por engano,
                por favor ignore esta mensagem ou entre em contato conosco.
            </p>
        </div>

        <div class="footer">
            <p style="margin: 0;">
                <a href="{{ url('/') }}">Feira Esquerda Livre</a> &mdash;
                Este é um e-mail automático, por favor não responda.
            </p>
        </div>

    </div>
</body>
</html>
