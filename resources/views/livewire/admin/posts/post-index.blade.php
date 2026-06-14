<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar posts..." class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
        <select wire:model.live="filterType" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
            <option value="">Todos os tipos</option>
            @foreach($types as $type)
            <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </select>
        <select wire:model.live="filterStatus" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
            <option value="">Todos os status</option>
            @foreach($statuses as $status)
            <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
        <a href="{{ route('admin.posts.create') }}">
            <x-admin.button>+ Novo Post</x-admin.button>
        </a>
    </div>

    <x-admin.card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Título</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Autor</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Publicado em</th>
                        <th class="text-right py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($posts as $post)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-2">
                            <p class="font-medium text-gray-900 max-w-xs truncate">{{ $post->title }}</p>
                            @if($post->category)
                            <p class="text-xs text-gray-500">{{ $post->category->name }}</p>
                            @endif
                        </td>
                        <td class="py-3 px-2 text-gray-600">{{ $post->author->name }}</td>
                        <td class="py-3 px-2">
                            <x-admin.badge color="blue">{{ $post->type->label() }}</x-admin.badge>
                        </td>
                        <td class="py-3 px-2">
                            <x-admin.badge :color="$post->status->color()">{{ $post->status->label() }}</x-admin.badge>
                        </td>
                        <td class="py-3 px-2 text-gray-500 text-xs">
                            {{ $post->published_at?->format('d/m/Y H:i') ?? '—' }}
                        </td>
                        <td class="py-3 px-2">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.posts.edit', $post) }}" class="text-gray-400 hover:text-[#1a472a]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <button wire:click="confirmDelete({{ $post->id }})" class="text-gray-400 hover:text-red-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="py-10 text-center text-gray-400">Nenhum post encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $posts->links() }}</div>
    </x-admin.card>

    @if($confirmDelete)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6">
            <h3 class="text-lg font-semibold mb-2">Confirmar exclusão</h3>
            <p class="text-sm text-gray-600 mb-6">O post será excluído permanentemente.</p>
            <div class="flex gap-3 justify-end">
                <x-admin.button variant="secondary" wire:click="$set('confirmDelete', false)">Cancelar</x-admin.button>
                <x-admin.button variant="danger" wire:click="deletePost">Excluir</x-admin.button>
            </div>
        </div>
    </div>
    @endif
</div>
