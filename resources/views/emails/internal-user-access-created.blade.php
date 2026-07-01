<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso administrativo — Feira Esquerda Livre</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f9fafb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1a1a1a; }
        .wrapper { max-width: 560px; margin: 32px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .header { background: #1a472a; padding: 32px 40px; text-align: center; }
        .header h1 { margin: 0; color: #F4E294; font-size: 22px; font-weight: 800; }
        .body { padding: 34px 40px; }
        .text { font-size: 15px; color: #4b5563; line-height: 1.6; margin: 0 0 20px; }
        .credentials { background: #fffbeb; border: 2px solid #F4E294; border-radius: 10px; padding: 20px 24px; margin: 24px 0; }
        .cred-row { margin-bottom: 10px; }
        .cred-row:last-child { margin-bottom: 0; }
        .cred-label { display: block; font-size: 12px; color: #6b7280; margin-bottom: 4px; }
        .cred-value { display: block; font-size: 15px; font-weight: 700; color: #1a1a1a; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px 12px; font-family: 'Courier New', monospace; word-break: break-all; }
        .btn { display: inline-block; background-color: #C47A00; color: #ffffff !important; text-decoration: none; font-weight: 700; font-size: 15px; padding: 14px 32px; border-radius: 8px; }
        .footer { background: #F4E294; padding: 18px 40px; text-align: center; font-size: 12px; color: #7A5C00; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Feira Esquerda Livre</h1>
        </div>

        <div class="body">
            <p class="text">
                Olá, {{ $user->name }}. Um acesso administrativo foi criado para você na plataforma Feira Esquerda Livre.
            </p>

            <div class="credentials">
                <div class="cred-row">
                    <span class="cred-label">E-mail</span>
                    <span class="cred-value">{{ $user->email }}</span>
                </div>
                <div class="cred-row">
                    <span class="cred-label">Senha temporária</span>
                    <span class="cred-value">{{ $temporaryPassword }}</span>
                </div>
            </div>

            <p class="text">
                Por segurança, altere sua senha no primeiro acesso e não compartilhe estas credenciais.
            </p>

            <p style="text-align:center; margin: 28px 0;">
                <a href="{{ url('/login') }}" class="btn">Acessar o painel</a>
            </p>
        </div>

        <div class="footer">
            Este é um e-mail automático, por favor não responda.
        </div>
    </div>
</body>
</html>
