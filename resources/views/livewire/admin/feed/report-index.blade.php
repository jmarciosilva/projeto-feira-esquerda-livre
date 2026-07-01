<div class="space-y-6">
    @if(session('success'))
    <div class="p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-sm font-medium">
        {{ session('success') }}
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
            <p class="text-sm text-gray-500">Reportes pendentes</p>
            <p class="text-3xl font-black text-gray-900 mt-1">{{ $pendingCount }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm md:col-span-2">
            <p class="text-sm font-semibold text-gray-700 mb-3">Filtro</p>
            <div class="flex gap-2 flex-wrap">
                <button wire:click="$set('filter', 'pendentes')"
                        class="px-4 py-2 rounded-xl text-sm font-bold border-2"
                        style="{{ $filter === 'pendentes' ? 'background:#52b788; border-color:#52b788; color:#fff;' : 'border-color:#e5e7eb; color:#6b7280;' }}">
                    Pendentes
                </button>
                <button wire:click="$set('filter', 'ocultas')"
                        class="px-4 py-2 rounded-xl text-sm font-bold border-2"
                        style="{{ $filter === 'ocultas' ? 'background:#52b788; border-color:#52b788; color:#fff;' : 'border-color:#e5e7eb; color:#6b7280;' }}">
                    Ocultas
                </button>
                <button wire:click="$set('filter', 'todos')"
                        class="px-4 py-2 rounded-xl text-sm font-bold border-2"
                        style="{{ $filter === 'todos' ? 'background:#52b788; border-color:#52b788; color:#fff;' : 'border-color:#e5e7eb; color:#6b7280;' }}">
                    Todos
                </button>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        @forelse($posts as $post)
        <article class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-5">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold" style="{{ $post->type->color() }}">
                                {{ $post->type->label() }}
                            </span>
                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold {{ $post->is_visible ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $post->is_visible ? 'Visível' : 'Oculta' }}
                            </span>
                            <span class="text-xs text-gray-400">{{ $post->created_at->format('d/m/Y H:i') }}</span>
                        </div>

                        <h2 class="mt-3 text-base font-bold text-gray-900">{{ $post->expositor?->name ?? 'Expositor removido' }}</h2>
                        <p class="mt-2 text-sm text-gray-700 whitespace-pre-line">{{ $post->content }}</p>
                        <p class="mt-3 text-xs text-gray-400">
                            {{ $post->reports_count }} denúncias · {{ $post->likes_count }} curtidas · {{ $post->comments_count }} comentários
                        </p>
                    </div>

                    @if(! empty($post->images))
                    <div class="flex gap-2 lg:w-56 overflow-x-auto">
                        @foreach($post->images as $image)
                        <img src="{{ Storage::url($image['thumb'] ?? $image['medium']) }}"
                             alt="Imagem da publicação"
                             class="w-20 h-20 rounded-xl object-cover border border-gray-100 flex-shrink-0">
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            <div class="px-5 py-4 bg-gray-50 border-t border-gray-100">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Motivos enviados</p>
                        <div class="space-y-2 max-h-44 overflow-y-auto">
                            @forelse($post->reports as $report)
                            <div class="rounded-xl bg-white border border-gray-100 px-3 py-2">
                                <p class="text-sm text-gray-700">{{ $report->reason }}</p>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ $report->user?->name ?? 'Usuário removido' }} · {{ $report->created_at->format('d/m/Y H:i') }} · {{ ucfirst($report->status) }}
                                </p>
                            </div>
                            @empty
                            <p class="text-sm text-gray-400">Sem denúncias registradas.</p>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Motivo da moderação</label>
                        <textarea wire:model="moderationReason.{{ $post->id }}"
                                  rows="3"
                                  maxlength="500"
                                  class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]"
                                  placeholder="Registre o motivo da ação administrativa"></textarea>
                        @error('moderationReason.' . $post->id)
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror

                        <div class="mt-3 flex gap-2 flex-wrap">
                            @if($post->is_visible)
                            <button wire:click="hidePost({{ $post->id }})"
                                    class="px-4 py-3 rounded-xl text-white text-sm font-bold"
                                    style="background:#dc2626; min-height:48px;">
                                Ocultar publicação
                            </button>
                            @else
                            <button wire:click="restorePost({{ $post->id }})"
                                    class="px-4 py-3 rounded-xl text-white text-sm font-bold"
                                    style="background:#16a34a; min-height:48px;">
                                Restaurar publicação
                            </button>
                            @endif
                        </div>

                        @if($post->moderationLogs->isNotEmpty())
                        <div class="mt-4">
                            <p class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Log</p>
                            <div class="space-y-1">
                                @foreach($post->moderationLogs->take(3) as $log)
                                <p class="text-xs text-gray-500">
                                    {{ ucfirst($log->action) }} por {{ $log->admin?->name ?? 'Admin removido' }} em {{ $log->created_at->format('d/m/Y H:i') }}
                                </p>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </article>
        @empty
        <div class="bg-white rounded-2xl border border-gray-100 py-16 px-6 text-center">
            <p class="text-lg font-bold text-gray-600">Nenhum item encontrado para este filtro.</p>
        </div>
        @endforelse
    </div>

    @if($posts->hasPages())
    <div class="bg-white rounded-xl border border-gray-100 px-4 py-3">
        {{ $posts->links() }}
    </div>
    @endif
</div>
