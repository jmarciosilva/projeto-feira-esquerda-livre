<div>
    @if($cursos->isEmpty())
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4" style="background:#F4E294;">
            <svg class="w-8 h-8" fill="none" stroke="#3D3000" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-700 mb-2">Nenhum produto digital cadastrado</h3>
        <p class="text-sm text-gray-500 max-w-sm mb-6">Para criar um curso, cadastre um produto e ative a opção "Produto digital / Curso online" no formulário.</p>
        <a href="{{ route('lojista.produtos.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white"
           style="background:#E8A000;">
            + Criar primeiro produto digital
        </a>
    </div>
    @else

    <div class="mb-6 flex items-center justify-between">
        <p class="text-sm text-gray-500">{{ $cursos->count() }} {{ Str::plural('curso', $cursos->count()) }} encontrado{{ $cursos->count() !== 1 ? 's' : '' }}</p>
        <a href="{{ route('lojista.produtos.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white"
           style="background:#E8A000;">
            + Novo produto digital
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach($cursos as $item)
        @php
            $product = $item['product'];
            $course  = $item['course'];
        @endphp
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">

            <div class="h-32 flex items-center justify-center" style="background:#F4E294;">
                @if($product->image_path)
                    <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}"
                         class="h-full w-full object-cover">
                @else
                    <svg class="w-10 h-10 opacity-40" fill="none" stroke="#3D3000" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 10l4.553-2.069A1 1 0 0121 8.847v6.306a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                @endif
            </div>

            <div class="p-5 flex flex-col gap-3 flex-1">
                <div>
                    <h3 class="text-base font-bold text-gray-900 leading-snug">{{ $product->name }}</h3>
                    <p class="text-sm text-gray-500 mt-0.5">R$ {{ number_format($product->price, 2, ',', '.') }}</p>
                </div>

                <div class="flex items-center gap-3 text-sm text-gray-500">
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                        </svg>
                        {{ $item['total_lessons'] }} aula{{ $item['total_lessons'] !== 1 ? 's' : '' }}
                    </span>
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ $item['total_enrollments'] }} matrícula{{ $item['total_enrollments'] !== 1 ? 's' : '' }}
                    </span>
                </div>

                @if($course)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $item['is_published'] ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700' }}">
                    {{ $item['is_published'] ? 'Publicado' : 'Rascunho' }}
                </span>
                @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                    Sem curso vinculado
                </span>
                @endif

                <div class="mt-auto pt-2 flex gap-2">
                    @if($course)
                    <a href="{{ route('lojista.ava.builder', $course) }}"
                       class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-sm font-semibold text-white"
                       style="background:#E8A000;">
                        Editar Curso
                    </a>
                    @else
                    <span class="flex-1 text-xs text-gray-400 text-center py-2">
                        Salve o produto novamente para criar o curso.
                    </span>
                    @endif
                    <a href="{{ route('lojista.produtos.edit', $product) }}"
                       class="px-3 py-2 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                        Produto
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
