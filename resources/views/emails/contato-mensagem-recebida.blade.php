<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova mensagem de contato</title>
    <style>
        body { margin: 0; padding: 0; background: #f9fafb; font-family: Arial, sans-serif; color: #1a1a1a; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #f0d060; }
        .header { background: #3D3000; padding: 28px 32px; }
        .header h1 { margin: 0; color: #F4E294; font-size: 22px; }
        .header p { margin: 6px 0 0; color: #fff7cc; font-size: 13px; }
        .body { padding: 28px 32px; }
        .label { margin: 0 0 4px; color: #7A5C00; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .value { margin: 0 0 18px; color: #1a1a1a; font-size: 15px; line-height: 1.5; }
        .message { padding: 18px; background: #FDF8DC; border: 1px solid #F4E294; border-radius: 10px; white-space: pre-line; }
        .footer { padding: 18px 32px; background: #F4E294; color: #5C4000; font-size: 12px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Nova mensagem de contato</h1>
            <p>Feira Esquerda Livre</p>
        </div>
        <div class="body">
            <p class="label">Nome</p>
            <p class="value">{{ $dados['name'] }}</p>

            <p class="label">E-mail</p>
            <p class="value">{{ $dados['email'] }}</p>

            @if(!empty($dados['phone']))
                <p class="label">Telefone / WhatsApp</p>
                <p class="value">{{ $dados['phone'] }}</p>
            @endif

            <p class="label">Assunto</p>
            <p class="value">{{ $dados['subject'] }}</p>

            <p class="label">Mensagem</p>
            <div class="message">{{ $dados['message'] }}</div>
        </div>
        <div class="footer">
            Mensagem enviada pelo formulário público de contato do site.
        </div>
    </div>
</body>
</html>
