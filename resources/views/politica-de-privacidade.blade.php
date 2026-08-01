@extends('layouts.public')

@section('title', 'Política de Privacidade - Feira Esquerda Livre')
@section('description', 'Conheça como a Feira Esquerda Livre coleta, utiliza e protege os dados pessoais dos usuários da plataforma.')

@section('content')
@php
    $settings = $settings ?? App\Models\SiteSetting::instance();
@endphp

<main style="background:#FDF8DC;">
    <section class="py-12 md:py-16 xl:py-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <div class="w-12 h-1.5 rounded-full mx-auto mb-4" style="background-color:#E8A000;"></div>
                <h1 class="section-title inline-flex items-center justify-center gap-3">
                    @if($settings->logo_path)
                        <img src="{{ Storage::url($settings->logo_path) }}" alt="" class="h-9 w-auto object-contain" loading="lazy">
                    @else
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-black" style="background:#F4E294; color:#3D3000;">F</span>
                    @endif
                    <span>Política de Privacidade</span>
                </h1>
                <p class="section-subtitle">Entenda como cuidamos das informações pessoais usadas na plataforma.</p>
            </div>

            <article class="bg-white rounded-2xl border shadow-sm p-6 sm:p-8 lg:p-10 space-y-7" style="border-color:#F0D060;">
                <p class="text-sm" style="color:#7A5C00;">Última atualização: {{ date('d/m/Y') }}</p>

                @foreach([
                    ['Quem somos', 'A Feira Esquerda Livre é uma plataforma voltada à divulgação de feiras, expositores, produtos, serviços, conteúdos e iniciativas relacionadas à economia solidária, cultura popular e comércio consciente.'],
                    ['Dados que podemos coletar', 'Podemos coletar dados informados voluntariamente pelo usuário, como nome, e-mail, telefone, endereço, dados de cadastro, mensagens enviadas pelo formulário de contato, inscrições em newsletter, solicitações de expositor e informações necessárias para pedidos ou atendimento.'],
                    ['Como usamos os dados', 'Utilizamos os dados para operar a plataforma, responder mensagens, processar cadastros, viabilizar pedidos, enviar comunicações solicitadas, divulgar atualizações da feira, melhorar a experiência do usuário e cumprir obrigações legais ou regulatórias.'],
                    ['Compartilhamento de informações', 'Os dados podem ser compartilhados com prestadores de serviço necessários à operação da plataforma, como serviços de hospedagem, envio de e-mails, meios de pagamento, logística e ferramentas administrativas. Não vendemos dados pessoais dos usuários.'],
                    ['Cookies e tecnologias semelhantes', 'Podemos utilizar cookies e tecnologias similares para manter sessões de acesso, lembrar preferências, proteger formulários, analisar navegação e melhorar funcionalidades da plataforma.'],
                    ['Segurança', 'Adotamos medidas técnicas e organizacionais para proteger os dados pessoais contra acessos não autorizados, perda, alteração ou uso indevido. Ainda assim, nenhum sistema digital é totalmente imune a riscos.'],
                    ['Direitos do titular', 'O usuário pode solicitar acesso, correção, atualização, exclusão ou informações sobre o tratamento de seus dados pessoais, conforme previsto na legislação aplicável.'],
                ] as $index => [$title, $text])
                    <section class="space-y-3">
                        <h2 class="text-xl font-black" style="color:#3D3000;">{{ $index + 1 }}. {{ $title }}</h2>
                        <p class="leading-relaxed" style="color:#4B5563;">{{ $text }}</p>
                    </section>
                @endforeach

                <section class="space-y-3">
                    <h2 class="text-xl font-black" style="color:#3D3000;">8. Contato sobre privacidade</h2>
                    <p class="leading-relaxed" style="color:#4B5563;">
                        Para dúvidas, solicitações sobre dados pessoais ou qualquer assunto relacionado a esta Política de Privacidade, fale diretamente com a Feira Esquerda Livre pelos canais abaixo.
                    </p>
                </section>

                <section class="rounded-2xl border p-5 sm:p-6" style="background:#FDF8DC; border-color:#F0D060;">
                    <h2 class="text-xl font-black mb-3" style="color:#3D3000;">Canais oficiais de contato</h2>
                    <p class="text-sm leading-relaxed mb-5" style="color:#5C4000;">
                        Você também pode usar o formulário da página de contato para enviar uma mensagem detalhada para nossa equipe.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                        @if($settings->email)
                            <div>
                                <p class="text-xs font-bold uppercase tracking-widest mb-1" style="color:#7A5C00;">E-mail</p>
                                <a href="mailto:{{ $settings->email }}" class="font-semibold break-all" style="color:#1A1A1A;">{{ $settings->email }}</a>
                            </div>
                        @endif

                        @if($settings->whatsapp)
                            <div>
                                <p class="text-xs font-bold uppercase tracking-widest mb-1" style="color:#7A5C00;">Telefone / WhatsApp</p>
                                <a href="https://wa.me/55{{ preg_replace('/\D/', '', $settings->whatsapp) }}" target="_blank" class="font-semibold" style="color:#1A1A1A;">{{ $settings->whatsapp }}</a>
                            </div>
                        @endif

                        @if($settings->address)
                            <div class="sm:col-span-2">
                                <p class="text-xs font-bold uppercase tracking-widest mb-1" style="color:#7A5C00;">Endereço</p>
                                <p class="text-sm leading-relaxed" style="color:#1A1A1A;">{{ $settings->address }}</p>
                            </div>
                        @endif
                    </div>

                    <a href="{{ route('contato') }}" class="btn-primary px-6 py-3 text-base" style="min-height:48px;">
                        Enviar mensagem pelo formulário de contato
                    </a>
                </section>
            </article>
        </div>
    </section>
</main>
@endsection
