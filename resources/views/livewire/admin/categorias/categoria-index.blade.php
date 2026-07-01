<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar categorias..." class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
        <select wire:model.live="filterEixo" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
            <option value="">Todos os eixos</option>
            @foreach($itemTypes as $type)
            <option value="{{ $type->value }}">{{ $type->emoji() }} {{ $type->label() }}</option>
            @endforeach
        </select>
        @can('cms.editar')
        <a href="{{ route('admin.categorias.create') }}">
            <x-admin.button>+ Nova Categoria</x-admin.button>
        </a>
        @endcan
    </div>

    <x-admin.card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Categoria</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Eixo</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Categoria-pai</th>
                        <th class="text-center py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="text-right py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($categorias as $categoria)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-2">
                            <p class="font-medium text-gray-900">{{ $categoria->name }}</p>
                            <p class="text-xs text-gray-400">{{ $categoria->slug }}</p>
                        </td>
                        <td class="py-3 px-2 text-gray-600">
                            @php $eixo = $categoria->eixo ? \App\Enums\ItemType::from($categoria->eixo) : null; @endphp
                            @if($eixo)
                            <x-admin.badge color="brand">{{ $eixo->emoji() }} {{ $eixo->label() }}</x-admin.badge>
                            @else
                            <span class="text-gray-400 text-xs">Todos os eixos</span>
                            @endif
                        </td>
                        <td class="py-3 px-2 text-gray-600">
                            {{ $categoria->parent?->name ?? '—' }}
                        </td>
                        <td class="py-3 px-2 text-center">
                            @can('cms.editar')
                            <button wire:click="toggleActive({{ $categoria->id }})">
                                <x-admin.badge :color="$categoria->is_active ? 'green' : 'gray'">
                                    {{ $categoria->is_active ? 'Ativa' : 'Inativa' }}
                                </x-admin.badge>
                            </button>
                            @else
                            <x-admin.badge :color="$categoria->is_active ? 'green' : 'gray'">
                                {{ $categoria->is_active ? 'Ativa' : 'Inativa' }}
                            </x-admin.badge>
                            @endcan
                        </td>
                        <td class="py-3 px-2">
                            @can('cms.editar')
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.categorias.edit', $categoria) }}" class="text-gray-400 hover:text-[#1a472a]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <button wire:click="confirmDelete({{ $categoria->id }})" class="text-gray-400 hover:text-red-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="py-10 text-center text-gray-400">Nenhuma categoria encontrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $categorias->links() }}</div>
    </x-admin.card>

    @if($confirmDelete)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6">
            <h3 class="text-lg font-semibold mb-2">Confirmar exclusão</h3>
            <p class="text-sm text-gray-600 mb-6">A categoria será excluída permanentemente. Produtos e posts vinculados ficarão sem categoria.</p>
            <div class="flex gap-3 justify-end">
                <x-admin.button variant="secondary" wire:click="$set('confirmDelete', false)">Cancelar</x-admin.button>
                <x-admin.button variant="danger" wire:click="deleteCategoria">Excluir</x-admin.button>
            </div>
        </div>
    </div>
    @endif
</div>
