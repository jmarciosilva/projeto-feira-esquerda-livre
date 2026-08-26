@extends('layouts.public')

@section('title', 'Preferências de Privacidade - Feira Esquerda Livre')
@section('description', 'Escolha se a Feira Esquerda Livre pode medir sua navegação. A decisão pode ser mudada a qualquer momento.')

@section('content')
<main style="background:#FDF8DC;">
    <section class="py-12 md:py-16 xl:py-20">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-10">
                <div class="w-12 h-1.5 rounded-full mx-auto mb-4" style="background-color:#E8A000;"></div>
                <h1 class="section-title">Preferências de Privacidade</h1>
                <p class="section-subtitle">Você escolhe se podemos medir sua navegação — e pode mudar de ideia quando quiser.</p>
            </div>

            @if(session('privacidade_status'))
                <div class="mb-6 rounded-2xl border-2 px-5 py-4 text-sm font-bold"
                     style="background:#F4E294; border-color:#E8A000; color:#3D3000;"
                     role="status">
                    {{ session('privacidade_status') }}
                </div>
            @endif

            <article class="bg-white rounded-2xl border shadow-sm p-6 sm:p-8 lg:p-10 space-y-8"
                     style="border-color:#F0D060;">

                {{-- Cookies essenciais: informativos, sem controle, porque sem eles o site nao funciona. --}}
                <section>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-black" style="color:#3D3000;">Essenciais</h2>
                            <p class="mt-1 text-sm leading-relaxed" style="color:#5C4000;">
                                Mantêm você conectado, guardam o carrinho e protegem os formulários contra
                                fraude. Sem eles não há como comprar nem entrar na conta, então ficam
                                sempre ativos.
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full px-4 py-1.5 text-xs font-black uppercase tracking-wider"
                              style="background:#F4E294; color:#3D3000;">
                            Sempre ativo
                        </span>
                    </div>
                </section>

                <div class="border-t" style="border-color:#F0D060;"></div>

                <section>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-black" style="color:#3D3000;">Medição de navegação</h2>
                            <p class="mt-1 text-sm leading-relaxed" style="color:#5C4000;">
                                Registram quais páginas e produtos são visitados, para entendermos o que a
                                feira precisa mostrar melhor. A medição é nossa e fica no nosso próprio
                                servidor — <strong>nada é enviado para outras empresas</strong> e não há
                                rastreadores de terceiros no site.
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full px-4 py-1.5 text-xs font-black uppercase tracking-wider"
                              style="background:{{ $estado->allowsAnalytics() ? '#CFE8B0' : '#F0D060' }}; color:#3D3000;">
                            {{ $estado->label() }}
                        </span>
                    </div>

                    @if($decididoEm)
                        <p class="mt-3 text-xs font-medium" style="color:#7A5C00;">
                            Sua escolha foi registrada em {{ $decididoEm->format('d/m/Y \à\s H:i') }}
                            e vale por 12 meses. Depois disso perguntamos de novo.
                        </p>
                    @endif

                    <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                        <form method="POST" action="{{ route('privacidade.consentimento') }}" class="sm:w-auto">
                            @csrf
                            <input type="hidden" name="decision" value="accepted">
                            <button type="submit"
                                    class="w-full sm:w-48 rounded-full border-2 px-6 py-3 text-base font-black transition-colors"
                                    style="background:#E8A000; border-color:#3D3000; color:#3D3000;">
                                Aceitar medição
                            </button>
                        </form>

                        <form method="POST" action="{{ route('privacidade.consentimento') }}" class="sm:w-auto">
                            @csrf
                            <input type="hidden" name="decision" value="rejected">
                            <button type="submit"
                                    class="w-full sm:w-48 rounded-full border-2 px-6 py-3 text-base font-black transition-colors"
                                    style="background:#FFFFFF; border-color:#3D3000; color:#3D3000;">
                                Recusar medição
                            </button>
                        </form>
                    </div>
                </section>

                <div class="border-t" style="border-color:#F0D060;"></div>

                {{--
                    Recusar interrompe a coleta; nao apaga o que ja foi coletado. Dizer isso
                    em voz alta evita que alguem saia daqui achando que apagou um historico
                    que continua existindo.
                --}}
                <section class="rounded-xl p-5" style="background:#FDF8DC;">
                    <h2 class="text-base font-black mb-2" style="color:#3D3000;">E o que já foi registrado?</h2>
                    <p class="text-sm leading-relaxed" style="color:#5C4000;">
                        Recusar interrompe a medição daqui para frente e apaga os identificadores
                        guardados no seu navegador. O histórico já registrado não é apagado
                        automaticamente. Se você quiser que ele deixe de estar ligado à sua conta,
                        fale com a gente pela
                        <a href="{{ route('contato') }}" class="font-bold underline underline-offset-2"
                           style="color:#C47A00;">página de contato</a> — esse desligamento é feito
                        a pedido, como manda a
                        <a href="{{ route('politica-privacidade') }}" class="font-bold underline underline-offset-2"
                           style="color:#C47A00;">Política de Privacidade</a>.
                    </p>
                </section>

            </article>
        </div>
    </section>
</main>
@endsection
