<div wire:poll.30s="refreshFeed" class="space-y-5">
    @if(session('success'))
    <div class="p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-base font-medium">
        {{ session('success') }}
    </div>
    @endif

    @auth
        @if(auth()->user()->isLojista())
        <a href="{{ route('lojista.feed.index') }}"
           class="block bg-white rounded-2xl border border-gray-100 shadow-sm p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3">
                @php $expositor = auth()->user()->expositor; @endphp
                @if($expositor?->logo_path)
                <img src="{{ Storage::url($expositor->logo_path) }}"
                     alt="{{ $expositor->name }}"
                     class="w-12 h-12 rounded-xl object-cover border border-gray-100">
                @else
                <div class="w-12 h-12 rounded-xl flex items-center justify-center font-bold"
                     style="background:#F4E294; color:#3D3000;">
                    {{ strtoupper(substr($expositor?->name ?? auth()->user()->name, 0, 1)) }}
                </div>
                @endif

                <div class="flex-1 rounded-full bg-gray-50 px-4 py-3 text-gray-500 text-base border border-gray-100">
                    O que você quer publicar na Comunidade?
                </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-bold" style="background:#dbeafe; color:#1e40af;">Foto da feira</span>
                <span class="px-3 py-1 rounded-full text-xs font-bold" style="background:#fef3c7; color:#92400e;">Novo produto</span>
                <span class="px-3 py-1 rounded-full text-xs font-bold" style="background:#fee2e2; color:#991b1b;">Aviso</span>
                <span class="px-3 py-1 rounded-full text-xs font-bold" style="background:#dcfce7; color:#166534;">Texto livre</span>
            </div>
        </a>
        @endif
    @else
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex-1">
                <p class="font-bold text-gray-900">Entre para participar da comunidade</p>
                <p class="text-sm text-gray-500 mt-1">Com uma conta, você pode curtir, comentar e reportar publicações.</p>
            </div>
            <a href="{{ route('login') }}"
               class="inline-flex items-center justify-center px-5 py-3 rounded-xl font-bold"
               style="background:#3D3000; color:#F4E294; min-height:48px;">
                Entrar
            </a>
        </div>
    </div>
    @endauth

    @forelse($posts as $post)
    <article class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden" wire:key="feed-post-{{ $post->id }}">
        <div class="p-5">
            <div class="flex items-start gap-3">
                <a href="{{ route('loja.show', [$post->expositor->slug, 'return_to' => route('feed.index')]) }}" class="flex-shrink-0">
                    @if($post->expositor->logo_path)
                    <img src="{{ Storage::url($post->expositor->logo_path) }}" alt="{{ $post->expositor->name }}"
                         class="w-12 h-12 rounded-xl object-cover border border-gray-100">
                    @else
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg"
                         style="background:#F4E294; color:#3D3000;">
                        {{ strtoupper(substr($post->expositor->name, 0, 1)) }}
                    </div>
                    @endif
                </a>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <a href="{{ route('loja.show', [$post->expositor->slug, 'return_to' => route('feed.index')]) }}"
                               class="font-bold text-gray-900 hover:underline">{{ $post->expositor->name }}</a>
                            <p class="text-sm text-gray-400">{{ $post->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="inline-flex w-fit items-center px-3 py-1 rounded-full text-xs font-bold"
                              style="{{ $post->type->color() }}">
                            {{ $post->type->label() }}
                        </span>
                    </div>

                    <p class="mt-4 text-base leading-relaxed text-gray-700 whitespace-pre-line">{{ $post->content }}</p>
                </div>
            </div>

            @if(! empty($post->images))
            <div class="mt-4 grid gap-2 {{ count($post->images) === 1 ? 'grid-cols-1' : 'grid-cols-2' }}">
                @foreach($post->images as $image)
                <a href="{{ Storage::url($image['medium'] ?? $image['thumb']) }}" target="_blank"
                   class="block aspect-square overflow-hidden rounded-xl bg-gray-100">
                    <img src="{{ Storage::url($image['medium'] ?? $image['thumb']) }}"
                         alt="Imagem da publicação"
                         loading="lazy"
                         class="w-full h-full object-cover">
                </a>
                @endforeach
            </div>
            @endif
        </div>

        <div class="px-5 py-3 border-t border-gray-100 flex flex-wrap items-center gap-2">
            @php $liked = $post->isLikedBy(auth()->user()); @endphp
            <button type="button"
                    wire:click="toggleLike({{ $post->id }})"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition-colors"
                    style="{{ $liked ? 'background:#F4E294; color:#3D3000;' : 'background:#f9fafb; color:#4b5563;' }} min-height:44px;">
                <span>{{ $liked ? 'Curtido' : 'Curtir' }}</span>
                <span>{{ $post->likes_count }}</span>
            </button>

            <a href="#comentarios-{{ $post->id }}"
               class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-bold bg-gray-50 text-gray-600"
               style="min-height:44px;">
                Comentar <span>{{ $post->comments_count }}</span>
            </a>

            <button type="button"
                    wire:click="openReport({{ $post->id }})"
                    class="ml-auto inline-flex items-center justify-center px-4 py-2 rounded-xl text-sm font-semibold text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                    style="min-height:44px;">
                Reportar
            </button>
        </div>

        <div id="comentarios-{{ $post->id }}" class="px-5 pb-5 pt-2 space-y-3">
            @auth
            <form wire:submit="addComment({{ $post->id }})" class="flex flex-col sm:flex-row gap-2">
                <input type="text"
                       wire:model="commentText.{{ $post->id }}"
                       maxlength="500"
                       placeholder="Escreva um comentário..."
                       class="flex-1 min-w-0 px-4 py-3 border border-gray-200 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000]">
                <button type="submit"
                        class="px-5 py-3 rounded-xl text-white font-bold"
                        style="background:#E8A000; min-height:52px;">
                    Enviar
                </button>
            </form>
            @error('commentText.' . $post->id)
            <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
            @else
            <a href="{{ route('login') }}" class="block px-4 py-3 rounded-xl bg-gray-50 text-sm font-semibold text-gray-600">
                Entre na sua conta para comentar ou curtir.
            </a>
            @endauth

            @foreach($post->comments->take(3) as $comment)
            <div class="rounded-xl bg-gray-50 px-4 py-3">
                <p class="text-sm font-bold text-gray-800">{{ $comment->user->name }}</p>
                <p class="text-sm text-gray-600 mt-1">{{ $comment->content }}</p>
            </div>
            @endforeach
        </div>
    </article>
    @empty
    <div class="bg-white rounded-2xl border border-gray-100 py-16 px-6 text-center">
        <p class="text-lg font-bold text-gray-700">Ainda não há publicações na Comunidade.</p>
        <p class="text-sm text-gray-400 mt-1">As novidades dos expositores aparecerão aqui.</p>
        @auth
            @if(auth()->user()->isLojista())
            <a href="{{ route('lojista.feed.index') }}"
               class="mt-5 inline-flex items-center justify-center px-6 py-3 rounded-xl text-white font-bold"
               style="background:#E8A000; min-height:52px;">
                Fazer primeira publicação
            </a>
            @endif
        @endauth
    </div>
    @endforelse

    @if($posts->hasPages())
    <div class="bg-white rounded-xl border border-gray-100 px-4 py-3">
        {{ $posts->links() }}
    </div>
    @endif

    @if($reportingPostId)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,0.65);">
        <form wire:submit="report" class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-6">
            <h2 class="text-lg font-bold text-gray-900">Reportar publicação</h2>
            <p class="text-sm text-gray-500 mt-1">Informe o motivo para a equipe de moderação.</p>
            <textarea wire:model="reportReason"
                      maxlength="500"
                      rows="4"
                      class="mt-4 w-full px-4 py-3 border border-gray-200 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000]"></textarea>
            @error('reportReason')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
            <div class="mt-4 flex gap-2 justify-end">
                <button type="button" wire:click="closeReport"
                        class="px-4 py-3 rounded-xl font-semibold text-gray-500">Cancelar</button>
                <button type="submit"
                        class="px-5 py-3 rounded-xl text-white font-bold"
                        style="background:#E8A000;">Enviar denúncia</button>
            </div>
        </form>
    </div>
    @endif
</div>
