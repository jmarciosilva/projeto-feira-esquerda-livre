<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body { margin: 0; padding: 0; background: #f9fafb; font-family: Inter, Arial, sans-serif; color: #1f2937; }
  .wrapper { max-width: 600px; margin: 0 auto; }
  .header { background: #1a472a; padding: 28px 32px; text-align: center; }
  .header h1 { margin: 0; color: #F4E294; font-size: 20px; font-weight: 800; letter-spacing: -0.3px; }
  .header p { margin: 4px 0 0; color: #b7e4c7; font-size: 13px; }
  .body { background: #ffffff; padding: 32px; }
  .footer { background: #f3f4f6; padding: 20px 32px; text-align: center; }
  .footer p { margin: 0; font-size: 12px; color: #6b7280; line-height: 1.6; }
  .footer a { color: #1a472a; text-decoration: underline; }
  img.pixel { display: none; width: 1px; height: 1px; }
  @media (max-width: 600px) {
    .header, .body, .footer { padding-left: 20px; padding-right: 20px; }
  }
</style>
</head>
<body>
<div class="wrapper">

  <div class="header">
    <h1>Feira Esquerda Livre</h1>
    <p>arte · cultura · economia solidária</p>
  </div>

  <div class="body">
    {!! $bodyHtml !!}
  </div>

  <div class="footer">
    <p>
      Você recebeu este e-mail porque se inscreveu ou realizou uma compra na Feira Esquerda Livre.<br>
      <a href="{{ $unsubscribeUrl }}">Cancelar inscrição</a> &middot;
      <a href="{{ url('/') }}">Visitar a feira</a>
    </p>
  </div>

</div>

{{-- Pixel de rastreio de abertura --}}
<img class="pixel" src="{{ url('/mk/o/' . $pixelToken) }}" width="1" height="1" alt="">
</body>
</html>
