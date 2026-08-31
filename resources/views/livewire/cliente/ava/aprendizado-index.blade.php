<div>
    @if($enrollments->isEmpty())
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4" style="background:#F4E294;">
            <svg class="w-8 h-8" fill="none" stroke="#3D3000" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-700 mb-2">Nenhum curso matriculado</h3>
        <p class="text-sm text-gray-500 max-w-sm">Quando você adquirir um curso digital na Feira, ele aparecerá aqui para acesso imediato.</p>
        <a href="{{ url('/produtos') }}" class="mt-6 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white" style="background:#E8A000;">
            Explorar cursos disponíveis
        </a>
    </div>
    @else

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach($enrollments as $enrollment)
        @php
            $product    = $enrollment->course->product;
            $course     = $enrollment->course;
            $accessible = $enrollment->isAccessible();
            $percent    = (int) $enrollment->completion_percent;
            // CAT-DOM-02E: a matricula nao guarda qual oferta foi comprada — o
            // vinculo nao existe e cria-lo seria redesenhar o AVA, fora desta
            // fase. Para a capa, a oferta vigente basta: e ilustracao do item,
            // nao afirmacao sobre quem vendeu.
            $capa = $product->ofertaVigente?->urlDaImagemPrincipal('medium')
                ?? ($product->image_path ? Storage::url($product->image_path) : null);
        @endphp

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">

            {{-- Thumb --}}
            <div class="h-36 w-full flex items-center justify-center" style="background:#F4E294;">
                @if($capa)
                    <img src="{{ $capa }}" alt="{{ $product->name }}"
                         class="h-full w-full object-cover">
                @else
                    <svg class="w-12 h-12 opacity-40" fill="none" stroke="#3D3000" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 10l4.553-2.069A1 1 0 0121 8.847v6.306a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                @endif
            </div>

            <div class="p-5 flex flex-col gap-3 flex-1">
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">{{ $product->expositor?->name }}</p>
                    <h3 class="text-base font-bold text-gray-900 leading-snug">{{ $product->name }}</h3>
                </div>

                {{-- Status badge --}}
                <div class="flex items-center gap-2">
                    @if(! $accessible)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                            {{ $enrollment->status->label() }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700">
                            Ativo
                        </span>
                        @if($enrollment->expires_at)
                            <span class="text-xs text-gray-400">até {{ $enrollment->expires_at->format('d/m/Y') }}</span>
                        @endif
                    @endif
                </div>

                {{-- Progresso --}}
                <div>
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>Progresso</span>
                        <span>{{ $percent }}%</span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all"
                             style="width:{{ $percent }}%; background:#E8A000;"></div>
                    </div>
                </div>

                {{-- CTA --}}
                <div class="mt-auto pt-2 flex flex-col gap-2">
                    @if($enrollment->isCompleted())
                        <a href="{{ route('cliente.ava.player', $enrollment) }}"
                           class="inline-flex items-center gap-1.5 text-sm font-semibold text-green-700 justify-center py-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Concluído — Rever
                        </a>
                        <a href="{{ route('cliente.ava.certificado.download', $enrollment) }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-medium border border-[#E8A000] text-[#C47A00] w-full justify-center hover:bg-[#FFFBEB] transition-colors">
                            🎓 Baixar Certificado
                        </a>
                    @elseif($accessible)
                        <a href="{{ route('cliente.ava.player', $enrollment) }}"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white w-full justify-center" style="background:#E8A000;">
                            {{ $percent > 0 ? 'Continuar' : 'Começar agora' }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
