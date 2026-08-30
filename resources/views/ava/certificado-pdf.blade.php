<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Certificado — {{ $enrollment->course->product->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Serif', Georgia, serif;
            background: #fff;
            color: #1a1a1a;
            width: 297mm;
            height: 210mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .page {
            width: 287mm;
            height: 200mm;
            border: 12px solid #E8A000;
            padding: 0;
            position: relative;
            overflow: hidden;
        }
        .inner {
            border: 3px solid #F4E294;
            margin: 8mm;
            height: calc(200mm - 22mm);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 10mm 20mm;
            text-align: center;
        }
        .logo-area {
            margin-bottom: 6mm;
        }
        .logo-text {
            font-size: 13pt;
            font-weight: bold;
            color: #E8A000;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .certifica {
            font-size: 10pt;
            color: #888;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 5mm;
        }
        .nome {
            font-size: 26pt;
            font-weight: bold;
            color: #1a1a1a;
            border-bottom: 2px solid #E8A000;
            padding-bottom: 3mm;
            margin-bottom: 5mm;
            font-style: italic;
        }
        .concluded {
            font-size: 10pt;
            color: #555;
            margin-bottom: 3mm;
        }
        .course-name {
            font-size: 16pt;
            font-weight: bold;
            color: #3D3000;
            margin-bottom: 4mm;
        }
        .expositor {
            font-size: 10pt;
            color: #777;
            margin-bottom: 6mm;
        }
        .details {
            font-size: 9pt;
            color: #999;
            display: flex;
            gap: 20mm;
            justify-content: center;
        }
        .watermark {
            position: absolute;
            bottom: 8mm;
            right: 14mm;
            font-size: 7pt;
            color: #ccc;
            font-family: monospace;
        }
    </style>
</head>
<body>
<div class="page">
    <div class="inner">
        <div class="logo-area">
            <div class="logo-text">Feira Esquerda Livre</div>
        </div>

        <p class="certifica">Certificado de Conclusão</p>

        <div class="nome">{{ $enrollment->user->name }}</div>

        <p class="concluded">concluiu com êxito o curso</p>

        <div class="course-name">{{ $enrollment->course->product->name }}</div>

        @if($enrollment->course->product->expositor)
        <div class="expositor">por {{ $enrollment->course->product->expositor->name }}</div>
        @endif

        <div class="details">
            <span>Concluído em: <strong>{{ $enrollment->completed_at->format('d \d\e F \d\e Y') }}</strong></span>
            @if($enrollment->course->estimated_hours)
            <span>Carga horária: <strong>{{ $enrollment->course->estimated_hours }}h</strong></span>
            @endif
        </div>
    </div>
    <div class="watermark">ID: {{ $enrollment->id }} · feira.com.br</div>
</div>
</body>
</html>
