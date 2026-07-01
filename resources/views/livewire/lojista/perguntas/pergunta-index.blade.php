<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-sm font-medium flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Filtros --}}
    <div class="flex items-center gap-3 mb-6 flex-wrap">
        <button wire:click="$set('filter', 'pending')"
                class="px-4 py-2 rounded-xl text-sm font-bold transition-colors {{ $filter === 'pending' ? 'text-[#3D3000]' : 'bg-white border border-gray-200 text-gray-600 hover:border-yellow-400' }}"
                style="{{ $filter === 'pending' ? 'background:#F4E294;' : '' }}">
            Aguardando resposta
            @if($pendingCount > 0)
            <span class="ml-1.5 inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-extrabold"
                  style="background:#E8A000; color:#fff;">{{ $pendingCount }}</span>
            @endif
        </button>
        <button wire:click="$set('filter', 'answered')"
                class="px-4 py-2 rounded-xl text-sm font-bold transition-colors {{ $filter === 'answered' ? 'text-[#3D3000]' : 'bg-white border border-gray-200 text-gray-600 hover:border-yellow-400' }}"
                style="{{ $filter === 'answered' ? 'background:#F4E294;' : '' }}">
            Respondidas
            <span class="ml-1.5 text-gray-400 font-normal">({{ $answeredCount }})</span>
        </button>
        <button wire:click="$set('filter', 'all')"
                class="px-4 py-2 rounded-xl text-sm font-bold transition-colors {{ $filter === 'all' ? 'text-[#3D3000]' : 'bg-white border border-gray-200 text-gray-600 hover:border-yellow-400' }}"
                style="{{ $filter === 'all' ? 'background:#F4E294;' : '' }}">
            Todas
        </button>
    </div>

    @forelse($questions as $question)
    <div class="bg-white rounded-2xl border mb-4 overflow-hidden {{ $question->is_visible ? 'border-gray-100' : 'border-gray-200 opacity-60' }}">
        <div class="p-5">
            {{-- Cabeçalho --}}
            <div class="flex items-start justify-between gap-4 mb-3">
                <div class="flex-1">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full"
                              style="background:#F4E294; color:#3D3000;">{{ $question->product->name }}</span>
                        @if(! $question->is_visible)
                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-500">Oculta</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-400">
                        <strong class="text-gray-600">{{ $question->user->name }}</strong>
                        · {{ $question->created_at->diffForHumans() }}
                    </p>
                </div>
                <button wire:click="toggleVisibility({{ $question->id }})"
                        class="text-xs font-semibold {{ $question->is_visible ? 'text-gray-400 hover:text-gray-600' : 'text-indigo-600 hover:text-indigo-800' }} transition-colors flex-shrink-0">
                    {{ $question->is_visible ? 'Ocultar' : 'Tornar visível' }}
                </button>
            </div>

            {{-- Pergunta --}}
            <p class="text-base font-semibold text-gray-900 mb-4">{{ $question->question }}</p>

            {{-- Resposta existente --}}
            @if($question->isAnswered())
            <div class="flex gap-3 p-4 rounded-xl border border-green-100 bg-green-50">
                <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"
                     style="background:#1a472a;">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-xs font-semibold text-green-700 mb-1">
                        Respondida {{ $question->answered_at->diffForHumans() }}
                    </p>
                    <p class="text-sm text-gray-700 leading-relaxed">{{ $question->answer }}</p>
                    <button wire:click="$set('answers.{{ $question->id }}', '{{ addslashes($question->answer) }}')"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 mt-2 transition-colors">
                        Editar resposta
                    </button>
                </div>
            </div>
            @endif

            {{-- Formulário de resposta --}}
            @if(! $question->isAnswered() || isset($answers[$question->id]))
            <div class="{{ $question->isAnswered() ? 'mt-4' : '' }}">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    {{ $question->isAnswered() ? 'Editar resposta' : 'Sua resposta' }}
                </label>
                <textarea wire:model.live="answers.{{ $question->id }}"
                          rows="3"
                          placeholder="Escreva uma resposta clara e objetiva..."
                          class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#E8A000] resize-none"></textarea>
                @error("answers.{$question->id}")
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <div class="flex justify-end mt-2 gap-2">
                    @if($question->isAnswered())
                    <button type="button"
                            wire:click="$set('answers.{{ $question->id }}', null)"
                            class="px-4 py-2 text-sm font-semibold rounded-xl border border-gray-200 text-gray-600">
                        Cancelar
                    </button>
                    @endif
                    <button type="button"
                            wire:click="saveAnswer({{ $question->id }})"
                            class="px-5 py-2 text-sm font-bold rounded-xl text-white transition-opacity"
                            style="background:#E8A000;"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-60">
                        <span wire:loading.remove wire:target="saveAnswer({{ $question->id }})">Publicar</span>
                        <span wire:loading wire:target="saveAnswer({{ $question->id }})">Publicando...</span>
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="py-20 text-center">
        <div class="text-5xl mb-4">💬</div>
        <p class="text-base font-semibold text-gray-500">
            @if($filter === 'pending') Nenhuma pergunta aguardando resposta.
            @elseif($filter === 'answered') Nenhuma pergunta respondida ainda.
            @else Nenhuma pergunta cadastrada.
            @endif
        </p>
        <p class="text-sm text-gray-400 mt-1">As perguntas dos clientes aparecerão aqui.</p>
    </div>
    @endforelse

    @if($questions->hasPages())
    <div class="mt-4">{{ $questions->links() }}</div>
    @endif
</div>
