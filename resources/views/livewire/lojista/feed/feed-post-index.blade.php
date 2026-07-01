<div class="space-y-6">
    @if(session('success'))
    <div class="p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-base font-medium">
        {{ session('success') }}
    </div>
    @endif

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <div class="flex items-start justify-between gap-4 mb-5">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Nova publicação</h2>
                <p class="text-sm text-gray-500 mt-1">Publique novidades da loja na Comunidade Esquerda Livre.</p>
            </div>
            <a href="{{ route('feed.index') }}" target="_blank"
               class="hidden sm:inline-flex px-4 py-2 rounded-xl text-sm font-bold border-2"
               style="border-color:#E8A000; color:#C47A00;">
                Ver comunidade
            </a>
        </div>

        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo da publicação</label>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                    @foreach($types as $postType)
                    <button type="button"
                            wire:click="$set('type', '{{ $postType->value }}')"
                            class="px-4 py-3 rounded-xl border-2 text-sm font-bold text-left transition-colors"
                            style="{{ $type === $postType->value ? 'background:#E8A000; border-color:#E8A000; color:#fff;' : 'background:#fff; border-color:#e5e7eb; color:#4b5563;' }} min-height:52px;">
                        {{ $postType->label() }}
                    </button>
                    @endforeach
                </div>
                @error('type') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Texto</label>
                <textarea wire:model.live="content"
                          maxlength="500"
                          rows="5"
                          class="w-full px-4 py-3 border border-gray-300 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000]"
                          placeholder="Conte o que há de novo na sua loja, feira ou produto..."></textarea>
                <div class="flex justify-between mt-1 text-xs">
                    @error('content') <span class="text-red-600">{{ $message }}</span> @else <span class="text-gray-400">Limite de 500 caracteres.</span> @enderror
                    <span class="text-gray-400">{{ mb_strlen($content) }}/500</span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Imagens da publicação</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    @foreach(['upload1', 'upload2', 'upload3', 'upload4'] as $field)
                    <label class="block rounded-xl border-2 border-dashed border-gray-200 p-4 bg-gray-50 cursor-pointer hover:border-[#E8A000] transition-colors">
                        <span class="block text-sm font-semibold text-gray-600">Imagem {{ $loop->iteration }}</span>
                        <input type="file" wire:model="{{ $field }}" accept="image/*" class="sr-only">
                        @if($this->{$field})
                        <span class="mt-2 block text-xs text-green-700 font-semibold">Selecionada</span>
                        @else
                        <span class="mt-2 block text-xs text-gray-400">JPG, PNG ou WebP</span>
                        @endif
                    </label>
                    @error($field) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    @endforeach
                </div>
            </div>

            <button type="submit"
                    class="w-full sm:w-auto px-6 py-4 rounded-xl text-white text-base font-bold"
                    style="background:#E8A000; min-height:60px;">
                Publicar no feed
            </button>
        </form>
    </section>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-lg font-bold text-gray-900">Minhas publicações</h2>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($posts as $post)
            <div class="p-5">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold" style="{{ $post->type->color() }}">
                                {{ $post->type->label() }}
                            </span>
                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold {{ $post->is_visible ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $post->is_visible ? 'Visível' : 'Oculta' }}
                            </span>
                        </div>
                        <p class="mt-3 text-base text-gray-700 whitespace-pre-line">{{ $post->content }}</p>
                        <p class="mt-2 text-sm text-gray-400">
                            {{ $post->created_at->format('d/m/Y H:i') }} ·
                            {{ $post->likes_count }} curtidas ·
                            {{ $post->comments_count }} comentários ·
                            {{ $post->reports_count }} denúncias
                        </p>
                    </div>

                    <button wire:click="delete({{ $post->id }})"
                            wire:confirm="Remover esta publicação?"
                            class="px-4 py-2 rounded-xl border-2 border-red-200 text-red-600 text-sm font-bold hover:bg-red-50"
                            style="min-height:44px;">
                        Excluir
                    </button>
                </div>

                @if(! empty($post->images))
                <div class="mt-4 flex gap-2 overflow-x-auto pb-1">
                    @foreach($post->images as $image)
                    <img src="{{ Storage::url($image['thumb'] ?? $image['medium']) }}"
                         alt="Imagem da publicação"
                         class="w-20 h-20 rounded-xl object-cover border border-gray-100">
                    @endforeach
                </div>
                @endif
            </div>
            @empty
            <div class="py-14 px-5 text-center">
                <p class="text-lg font-bold text-gray-600">Você ainda não publicou no feed.</p>
                <p class="text-sm text-gray-400 mt-1">A primeira publicação aparecerá aqui.</p>
            </div>
            @endforelse
        </div>

        @if($posts->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $posts->links() }}
        </div>
        @endif
    </section>
</div>
