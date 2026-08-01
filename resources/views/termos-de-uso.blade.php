@extends('layouts.public')

@section('title', 'Termos de Uso - Feira Esquerda Livre')
@section('description', 'Conheça as regras de uso da plataforma Feira Esquerda Livre para usuários, clientes e expositores.')

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
                    <span>Termos de Uso</span>
                </h1>
                <p class="section-subtitle">Regras básicas para uso da plataforma por visitantes, clientes e expositores.</p>
            </div>

            <article class="bg-white rounded-2xl border shadow-sm p-6 sm:p-8 lg:p-10 space-y-7" style="border-color:#F0D060;">
                <p class="text-sm" style="color:#7A5C00;">Última atualização: {{ date('d/m/Y') }}</p>

                @foreach([
                    ['Aceitação dos termos', 'Ao acessar ou utilizar a Feira Esquerda Livre, o usuário declara que leu, compreendeu e concorda com estes Termos de Uso e com a Política de Privacidade da plataforma.'],
                    ['Finalidade da plataforma', 'A plataforma tem como objetivo divulgar feiras, expositores, produtos, serviços, práticas de cuidado e conteúdos relacionados à economia solidária, cultura popular e iniciativas comunitárias.'],
                    ['Cadastro e responsabilidades do usuário', 'O usuário se compromete a fornecer informações verdadeiras, manter seus dados atualizados e utilizar a plataforma de forma ética, respeitosa e compatível com a legislação vigente.'],
                    ['Expositores e lojistas', 'Expositores são responsáveis pelas informações, imagens, preços, disponibilidade, qualidade, entrega e atendimento relacionados aos seus produtos ou serviços, sem prejuízo das regras administrativas da plataforma.'],
                    ['Compras, pagamentos e entregas', 'As condições de compra, pagamento, frete, retirada, troca ou cancelamento podem variar conforme o expositor, o produto, o serviço ou o evento. O usuário deve conferir as informações antes de concluir qualquer pedido.'],
                    ['Condutas proibidas', 'É proibido usar a plataforma para fins ilícitos, envio de spam, fraude, violação de direitos de terceiros, publicação de conteúdo discriminatório, ofensivo ou incompatível com os princípios da Feira Esquerda Livre.'],
                    ['Conteúdos e propriedade intelectual', 'Marcas, textos, imagens, layouts e demais conteúdos da plataforma pertencem aos seus respectivos titulares. O uso não autorizado, cópia ou reprodução indevida pode gerar responsabilização.'],
                    ['Alterações dos termos', 'Estes Termos de Uso podem ser atualizados a qualquer momento para refletir melhorias da plataforma, mudanças operacionais ou exigências legais. A versão vigente estará sempre disponível nesta página.'],
                ] as $index => [$title, $text])
                    <section class="space-y-3">
                        <h2 class="text-xl font-black" style="color:#3D3000;">{{ $index + 1 }}. {{ $title }}</h2>
                        <p class="leading-relaxed" style="color:#4B5563;">{{ $text }}</p>
                    </section>
                @endforeach

                <section class="space-y-3">
                    <h2 class="text-xl font-black" style="color:#3D3000;">9. Contato</h2>
                    <p class="leading-relaxed" style="color:#4B5563;">
                        Para dúvidas sobre estes Termos de Uso, uso da plataforma, cadastro, pedidos, expositores ou qualquer atendimento relacionado à Feira Esquerda Livre, fale conosco pelos canais abaixo.
                    </p>
                </section>

                <section class="rounded-2xl border p-5 sm:p-6" style="background:#FDF8DC; border-color:#F0D060;">
                    <h2 class="text-xl font-black mb-3" style="color:#3D3000;">Canais oficiais de contato</h2>
                    <p class="text-sm leading-relaxed mb-5" style="color:#5C4000;">
                        Se preferir, envie uma mensagem completa pelo formulário da página de contato. Nossa equipe receberá sua solicitação por e-mail.
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
