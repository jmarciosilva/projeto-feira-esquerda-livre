<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    @can('cms.editar')
    <div class="flex justify-end mb-6">
        <x-admin.button wire:click="openCreate">+ Novo Menu</x-admin.button>
    </div>
    @endcan

    <x-admin.card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nome</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Localização</th>
                        <th class="text-center py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Itens</th>
                        <th class="text-center py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="text-right py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($menus as $menu)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-2 font-medium text-gray-900">{{ $menu->name }}</td>
                        <td class="py-3 px-2 text-gray-600">{{ $menu->location->label() }}</td>
                        <td class="py-3 px-2 text-center text-gray-600">{{ $menu->all_items_count }}</td>
                        <td class="py-3 px-2 text-center">
                            <x-admin.badge :color="$menu->is_active ? 'green' : 'gray'">
                                {{ $menu->is_active ? 'Ativo' : 'Inativo' }}
                            </x-admin.badge>
                        </td>
                        <td class="py-3 px-2">
                            @can('cms.editar')
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.menus.edit', $menu) }}" class="text-gray-400 hover:text-[#1a472a]" title="Gerenciar itens">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                                </a>
                                <button wire:click="openEdit({{ $menu->id }})" class="text-gray-400 hover:text-[#1a472a]" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button
                                    @click="$dispatch('open-confirm', {
                                        title: 'Excluir menu',
                                        message: 'O menu e todos os seus itens serão excluídos permanentemente.',
                                        confirmText: 'Excluir',
                                        variant: 'danger',
                                        action: () => $wire.deleteMenu({{ $menu->id }})
                                    })"
                                    class="text-gray-400 hover:text-red-600"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="py-10 text-center text-gray-400">Nenhum menu criado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $menus->links() }}</div>
    </x-admin.card>

    {{-- Modal: Criar / Editar menu --}}
    @if($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ $editId ? 'Editar Menu' : 'Novo Menu' }}</h3>
            <div class="space-y-4">
                <x-admin.input label="Nome *" wire:model="name" :error="$errors->first('name')"/>
                <x-admin.select label="Localização *" wire:model="location">
                    <option value="header">Cabeçalho</option>
                    <option value="footer">Rodapé</option>
                    <option value="sidebar">Barra Lateral</option>
                    <option value="mobile">Mobile</option>
                </x-admin.select>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" wire:model="is_active" class="w-4 h-4 text-[#1a472a] rounded border-gray-300">
                    <span class="text-sm font-medium text-gray-700">Menu ativo</span>
                </label>
            </div>
            <div class="flex gap-3 justify-end mt-6">
                <x-admin.button variant="secondary" wire:click="$set('showForm', false)">Cancelar</x-admin.button>
                <x-admin.button wire:click="save">{{ $editId ? 'Atualizar' : 'Criar' }}</x-admin.button>
            </div>
        </div>
    </div>
    @endif

    <x-admin.confirm-modal />
</div>
