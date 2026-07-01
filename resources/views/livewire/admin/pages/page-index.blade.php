<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar páginas..." class="w-full sm:w-72 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
        @can('cms.editar')
        <a href="{{ route('admin.pages.create') }}">
            <x-admin.button>+ Nova Página</x-admin.button>
        </a>
        @endcan
    </div>

    <x-admin.card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Título</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Slug</th>
                        <th class="text-center py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Homepage</th>
                        <th class="text-center py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="text-right py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($pages as $page)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-2">
                            <p class="font-medium text-gray-900">{{ $page->title }}</p>
                        </td>
                        <td class="py-3 px-2 text-gray-500 font-mono text-xs">/{{ $page->slug }}</td>
                        <td class="py-3 px-2 text-center">
                            @if($page->is_homepage)
                            <x-admin.badge color="brand">Home</x-admin.badge>
                            @endif
                        </td>
                        <td class="py-3 px-2 text-center">
                            @can('cms.editar')
                            <button wire:click="toggleActive({{ $page->id }})" class="focus:outline-none">
                                <x-admin.badge :color="$page->is_active ? 'green' : 'gray'">
                                    {{ $page->is_active ? 'Ativo' : 'Inativo' }}
                                </x-admin.badge>
                            </button>
                            @else
                            <x-admin.badge :color="$page->is_active ? 'green' : 'gray'">
                                {{ $page->is_active ? 'Ativo' : 'Inativo' }}
                            </x-admin.badge>
                            @endcan
                        </td>
                        <td class="py-3 px-2">
                            @can('cms.editar')
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="text-gray-400 hover:text-[#1a472a] transition-colors" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <button
                                    @click="$dispatch('open-confirm', {
                                        title: 'Excluir página',
                                        message: 'Esta ação não pode ser desfeita. A página e todas as suas seções serão removidas permanentemente.',
                                        confirmText: 'Excluir',
                                        variant: 'danger',
                                        action: () => $wire.deletePage({{ $page->id }})
                                    })"
                                    class="text-gray-400 hover:text-red-600 transition-colors"
                                    title="Excluir"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="py-10 text-center text-gray-400">Nenhuma página encontrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $pages->links() }}</div>
    </x-admin.card>

    <x-admin.confirm-modal />
</div>
