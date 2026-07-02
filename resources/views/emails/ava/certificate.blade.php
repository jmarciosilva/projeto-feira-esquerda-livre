<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Conclusão</title>
    <style>
        body { font-family: sans-serif; color: #1a1a1a; margin: 0; padding: 0; background: #fff; }
        .container { max-width: 600px; margin: 0 auto; padding: 40px 24px; }
        .header { text-align: center; padding-bottom: 24px; border-bottom: 2px solid #F4E294; margin-bottom: 32px; }
        .badge { display: inline-block; background: #F4E294; color: #3D3000; padding: 6px 16px; border-radius: 999px; font-size: 13px; font-weight: 700; margin-bottom: 16px; }
        h1 { font-size: 22px; color: #1a1a1a; margin: 0 0 8px; }
        p { font-size: 15px; color: #444; line-height: 1.6; margin: 0 0 16px; }
        .course-name { font-size: 20px; font-weight: 700; color: #3D3000; margin: 24px 0; text-align: center; }
        .cta { display: block; margin: 32px auto 0; text-align: center; }
        .cta a { display: inline-block; background: #E8A000; color: #fff; text-decoration: none; padding: 14px 32px; border-radius: 12px; font-size: 15px; font-weight: 700; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; font-size: 12px; color: #999; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <span class="badge">🎓 Certificado de Conclusão</span>
        <h1>Parabéns, {{ $enrollment->user->name }}!</h1>
        <p>Você concluiu com sucesso o curso:</p>
        <div class="course-name">{{ $enrollment->course->product->name }}</div>
        <p>por <strong>{{ $enrollment->course->product->expositor?->nome_fantasia }}</strong></p>
        <p style="font-size:13px; color:#999;">Concluído em: {{ $enrollment->completed_at->format('d/m/Y') }}</p>
    </div>

    <p>Seu certificado está em anexo neste email. Você também pode baixá-lo a qualquer momento pelo painel de aprendizado.</p>

    <div class="cta">
        <a href="{{ route('cliente.ava.index') }}">Acessar Meu Aprendizado</a>
    </div>

    <div class="footer">
        Feira Esquerda Livre &nbsp;·&nbsp; Este email foi enviado automaticamente.
    </div>
</div>
</body>
</html>
