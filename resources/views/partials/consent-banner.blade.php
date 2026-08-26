{{--
    Banner de consentimento.

    So aparece enquanto a pergunta nao foi respondida — quem ja decidiu, em
    qualquer direcao, nao e perguntado de novo antes dos 12 meses.

    "Aceitar" e "Recusar" tem o mesmo tamanho, o mesmo peso de fonte e a mesma
    area de clique. A diferenca e apenas de cor, para dar contraste a duas
    opcoes igualmente disponiveis: esconder a recusa atras de um submenu seria
    obter consentimento por cansaco.

    Formulario HTML comum, sem JavaScript.
--}}
@if(app(\App\CustomerIntelligence\Support\TrackingPolicy::class)->needsDecision())
    <div role="region"
         aria-label="Preferências de privacidade"
         class="fixed inset-x-0 bottom-0 z-50 px-4 pb-4 sm:px-6 sm:pb-6">
        <div class="mx-auto max-w-4xl rounded-2xl border-2 shadow-2xl p-5 sm:p-6"
             style="background:#FDF8DC; border-color:#E8A000;">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                <div class="lg:pr-6">
                    <p class="text-base font-black mb-1" style="color:#3D3000;">
                        Podemos medir sua navegação?
                    </p>
                    <p class="text-sm leading-relaxed" style="color:#5C4000;">
                        Usamos medições próprias para entender quais páginas e produtos são mais
                        procurados. Nada é enviado para outras empresas. Se preferir não participar,
                        o site funciona exatamente igual.
                        <a href="{{ route('privacidade.preferencias') }}"
                           class="font-bold underline underline-offset-2"
                           style="color:#C47A00;">Entenda o que é coletado</a>.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row lg:shrink-0">
                    <form method="POST" action="{{ route('privacidade.consentimento') }}" class="sm:w-auto">
                        @csrf
                        <input type="hidden" name="decision" value="rejected">
                        <button type="submit"
                                class="w-full sm:w-40 rounded-full border-2 px-6 py-3 text-base font-black transition-colors"
                                style="background:#FFFFFF; border-color:#3D3000; color:#3D3000;">
                            Recusar
                        </button>
                    </form>

                    <form method="POST" action="{{ route('privacidade.consentimento') }}" class="sm:w-auto">
                        @csrf
                        <input type="hidden" name="decision" value="accepted">
                        <button type="submit"
                                class="w-full sm:w-40 rounded-full border-2 px-6 py-3 text-base font-black transition-colors"
                                style="background:#E8A000; border-color:#3D3000; color:#3D3000;">
                            Aceitar
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
@endif
