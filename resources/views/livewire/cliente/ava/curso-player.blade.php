<div class="flex flex-col lg:flex-row gap-0 -mx-4 lg:-mx-6 -mt-4 lg:-mt-6" style="min-height: calc(100vh - 80px);">

    {{-- ═══════════════════════════════════════════════════
         SIDEBAR — ÍNDICE DO CURSO
    ══════════════════════════════════════════════════════ --}}
    <aside class="w-full lg:w-72 xl:w-80 flex-shrink-0 bg-white border-b lg:border-b-0 lg:border-r border-gray-200 overflow-y-auto">

        {{-- Cabeçalho da sidebar --}}
        <div class="px-4 py-4 border-b border-gray-100">
            <h2 class="text-sm font-bold text-gray-900 leading-tight">{{ $course->product->name }}</h2>
            <div class="mt-2">
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>Progresso</span>
                    <span>{{ (int) $enrollment->completion_percent }}%</span>
                </div>
                <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full" style="width:{{ (int) $enrollment->completion_percent }}%; background:#E8A000;"></div>
                </div>
            </div>
            @if($enrollment->isCompleted())
            <p class="mt-2 text-xs font-semibold text-green-700">✓ Curso concluído!</p>
            @endif
        </div>

        {{-- Módulos e aulas --}}
        <div class="py-2">
            @foreach($course->modules as $module)
            <div>
                <p class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $module->title }}</p>
                @foreach($module->lessons as $lesson)
                @php $isCompleted = in_array($lesson->id, $completedLessonIds); @endphp
                <button wire:click="selectLesson({{ $lesson->id }})"
                        class="w-full flex items-center gap-3 px-4 py-2.5 text-left transition-colors {{ $activeLessonId === $lesson->id ? 'text-[#3D3000] font-semibold' : 'text-gray-600 hover:bg-gray-50' }}"
                        style="{{ $activeLessonId === $lesson->id ? 'background:#FFFBEB;' : '' }}">
                    {{-- Status --}}
                    <span class="w-5 h-5 flex-shrink-0 rounded-full flex items-center justify-center
                        {{ $isCompleted ? 'bg-green-500 text-white' : ($activeLessonId === $lesson->id ? 'border-2 border-[#E8A000]' : 'border-2 border-gray-200') }}">
                        @if($isCompleted)
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @endif
                    </span>
                    <span class="text-sm leading-snug flex-1">{{ $lesson->title }}</span>
                    {{-- Tipo --}}
                    <span class="text-xs text-gray-400 flex-shrink-0">
                        @if($lesson->content_type === 'video') ▶
                        @elseif($lesson->content_type === 'texto') 📄
                        @elseif($lesson->content_type === 'pdf') 📎
                        @else 🎵
                        @endif
                    </span>
                </button>
                @endforeach
            </div>
            @endforeach
        </div>
    </aside>

    {{-- ═══════════════════════════════════════════════════
         ÁREA PRINCIPAL — PLAYER / CONTEÚDO
    ══════════════════════════════════════════════════════ --}}
    <main class="flex-1 overflow-y-auto">
        @if($activeLesson)

        <div class="max-w-4xl mx-auto px-4 lg:px-8 py-6">
            <h1 class="text-xl font-bold text-gray-900 mb-1">{{ $activeLesson->title }}</h1>
            @if($activeLesson->description)
            <p class="text-sm text-gray-500 mb-4">{{ $activeLesson->description }}</p>
            @endif

            {{-- ── VÍDEO ─────────────────────────────────────────── --}}
            @if($activeLesson->isVideo())
                @if($activeLesson->embedUrl())
                <div class="relative w-full rounded-2xl overflow-hidden mb-6" style="padding-bottom:56.25%;">
                    <iframe src="{{ $activeLesson->embedUrl() }}"
                            class="absolute inset-0 w-full h-full"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                </div>
                @else
                <div class="flex items-center justify-center rounded-2xl bg-gray-100 h-48 mb-6 text-gray-400 text-sm">
                    URL do vídeo não configurada.
                </div>
                @endif

            {{-- ── TEXTO ─────────────────────────────────────────── --}}
            @elseif($activeLesson->isTexto())
                @if($activeLesson->text_content)
                <div class="prose max-w-none mb-6 text-gray-700 leading-relaxed">
                    {!! nl2br(e($activeLesson->text_content)) !!}
                </div>
                @else
                <div class="flex items-center justify-center rounded-2xl bg-gray-50 h-32 mb-6 text-gray-400 text-sm border border-dashed border-gray-200">
                    Conteúdo textual não cadastrado.
                </div>
                @endif

            {{-- ── PDF ───────────────────────────────────────────── --}}
            @elseif($activeLesson->isPdf())
                <div class="flex flex-col items-center justify-center rounded-2xl bg-gray-50 h-48 mb-6 gap-3 border border-dashed border-gray-200">
                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm text-gray-500">Material em PDF</p>
                    <p class="text-xs text-gray-400">Upload de materiais disponível na Fase 8.3</p>
                </div>

            {{-- ── ÁUDIO ─────────────────────────────────────────── --}}
            @elseif($activeLesson->isAudio())
                @if($activeLesson->video_url)
                <audio controls class="w-full mb-6 rounded-xl">
                    <source src="{{ $activeLesson->video_url }}">
                    Seu navegador não suporta o elemento de áudio.
                </audio>
                @else
                <div class="flex items-center justify-center rounded-2xl bg-gray-50 h-32 mb-6 text-gray-400 text-sm border border-dashed border-gray-200">
                    URL do áudio não configurada.
                </div>
                @endif
            @endif

            {{-- ── Materiais complementares ──────────────────────────── --}}
            @if($activeLesson->materials->count() > 0)
            <div class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Materiais desta aula</p>
                <div class="space-y-2">
                    @foreach($activeLesson->materials as $mat)
                    <a href="{{ $mat->temporaryUrl() }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg bg-white border border-gray-100 hover:border-[#E8A000] transition-colors group">
                        <svg class="w-5 h-5 text-gray-400 group-hover:text-[#E8A000]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                        <span class="flex-1 text-sm text-gray-700 group-hover:text-gray-900">{{ $mat->title }}</span>
                        @if($mat->fileSizeLabel())
                        <span class="text-xs text-gray-400">{{ $mat->fileSizeLabel() }}</span>
                        @endif
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-[#E8A000]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- ── Botão marcar concluída / avançar ─────────────────── --}}
            @php $isLessonCompleted = in_array($activeLesson->id, $completedLessonIds); @endphp

            <div class="flex items-center gap-4">
                @if($isLessonCompleted)
                <span class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-green-50 text-green-700 font-semibold text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Aula concluída
                </span>
                @else
                <button wire:click="markComplete"
                        class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-white font-semibold text-sm"
                        style="background:#E8A000;">
                    <span wire:loading.remove>Marcar como concluída e avançar</span>
                    <span wire:loading>Salvando...</span>
                </button>
                @endif
            </div>

            {{-- ── Banner de conclusão / certificado ───────────────── --}}
            @if($enrollment->isCompleted())
            <div class="mt-6 p-5 rounded-2xl text-center" style="background:#FFFBEB; border: 1.5px solid #F4E294;">
                <div class="text-3xl mb-2">🎓</div>
                <p class="text-base font-bold text-[#3D3000] mb-1">Parabéns! Você concluiu este curso.</p>
                <p class="text-sm text-gray-500 mb-4">Seu certificado foi gerado e enviado por email.</p>
                <a href="{{ route('cliente.ava.certificado.download', $enrollment) }}"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-white font-semibold text-sm"
                   style="background:#E8A000;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Baixar Certificado
                </a>
            </div>
            @endif
        </div>

        @else
        <div class="flex flex-col items-center justify-center h-full py-20 text-center text-gray-400">
            <svg class="w-12 h-12 mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm">Selecione uma aula no índice ao lado.</p>
        </div>
        @endif
    </main>
</div>
