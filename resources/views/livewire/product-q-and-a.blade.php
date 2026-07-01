<div class="mt-8">
    <h2 class="text-xl font-bold text-gray-900 mb-5">Perguntas & Respostas</h2>

    {{-- Perguntas respondidas (públicas) --}}
    @if($answered->isNotEmpty())
    <div class="space-y-3 mb-6" x-data="{ open: null }">
        @foreach($answered as $i => $q)
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <button type="button"
                    @click="open = open === {{ $i }} ? null : {{ $i }}"
                    class="w-full flex items-start justify-between px-5 py-4 text-left gap-4">
                <div class="flex-1">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block mb-1">
                        {{ $q->askerFirstName() }} perguntou · {{ $q->created_at->diffForHumans() }}
                    </span>
                    <span class="font-semibold text-gray-900 text-base leading-snug">{{ $q->question }}</span>
                </div>
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-gray-400 transition-transform duration-200"
                     :class="open === {{ $i }} ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open === {{ $i }}"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="px-5 pb-5 border-t border-gray-50">
                <div class="pt-3 flex gap-3">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"
                         style="background:#1a472a;">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold mb-1" style="color:#1a472a;">Resposta da loja</p>
                        <div class="text-gray-700 text-sm leading-relaxed space-y-1">
                            @foreach(explode("\n", $q->answer) as $line)
                            @if(trim($line))<p>{{ trim($line) }}</p>@endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Perguntas pendentes do usuário logado --}}
    @if($myPending->isNotEmpty())
    <div class="mb-6 space-y-2">
        @foreach($myPending as $q)
        <div class="flex items-start gap-3 p-4 rounded-xl border border-yellow-200" style="background:#FFFBEB;">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-yellow-800">Sua pergunta aguarda resposta</p>
                <p class="text-sm text-yellow-700 mt-0.5">{{ $q->question }}</p>
                <p class="text-xs text-yellow-600 mt-1">Enviada {{ $q->created_at->diffForHumans() }}</p>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Confirmação de envio --}}
    @if($submitted)
    <div class="mb-4 p-4 rounded-xl border border-green-200 bg-green-50 text-green-800 text-sm font-medium flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        Sua pergunta foi enviada! A loja responderá em breve.
    </div>
    @endif

    {{-- Formulário --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <h3 class="text-base font-bold text-gray-900 mb-1">Tem alguma dúvida?</h3>
        <p class="text-sm text-gray-500 mb-4">
            Sua pergunta será respondida pelo lojista e ficará visível para outros clientes.
        </p>

        @auth
        <form wire:submit="submit">
            <textarea wire:model="question"
                      rows="3"
                      placeholder="Ex.: Qual o prazo de entrega para São Paulo? Tem disponível em outras cores?"
                      class="w-full px-4 py-3 border border-gray-300 rounded-xl text-base focus:outline-none focus:ring-2 resize-none @error('question') border-red-400 @enderror"
                      style="focus-ring-color:#E8A000;"></textarea>
            @error('question')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="flex items-center justify-between mt-3">
                <span class="text-xs text-gray-400">Mínimo de 5 caracteres</span>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl font-bold text-sm text-white transition-opacity"
                        style="background:#E8A000;"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-60">
                    <span wire:loading.remove>Enviar pergunta</span>
                    <span wire:loading>Enviando...</span>
                </button>
            </div>
        </form>
        @else
        <a href="{{ route('login') }}"
           class="flex items-center justify-center gap-2 w-full py-3 rounded-xl font-semibold text-base border-2 transition-colors"
           style="border-color:#E8A000; color:#C47A00;">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            Faça login para perguntar
        </a>
        @endauth
    </div>
</div>
