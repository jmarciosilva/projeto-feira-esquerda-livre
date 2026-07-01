<div>
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.menus.index') }}" class="text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <p class="text-sm text-gray-500">Menu: <span class="font-medium text-gray-700">{{ $menu->name }}</span> — {{ $menu->location->label() }}</p>
        </div>
        @can('cms.editar')
        <x-admin.button wire:click="openAddItem" class="ml-auto">+ Adicionar Item</x-admin.button>
        @endcan
    </div>

    <x-admin.card title="Itens do Menu">
        @if($items->isEmpty())
        <p class="text-center text-gray-400 py-8">Nenhum item neste menu. Clique em "Adicionar Item".</p>
        @else
        <div class="space-y-2">
            @foreach($items as $item)
            <div class="border border-gray-200 rounded-lg p-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $item->title }}</p>
                            <p class="text-xs text-gray-500 font-mono">{{ $item->url }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-admin.badge :color="$item->is_active ? 'green' : 'gray'">{{ $item->is_active ? 'Ativo' : 'Inativo' }}</x-admin.badge>
                        <button wire:click="openEditItem({{ $item->id }})" class="text-gray-400 hover:text-[#1a472a]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button wire:click="confirmDelete({{ $item->id }})" class="text-gray-400 hover:text-red-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
                @if($item->children->isNotEmpty())
                <div class="mt-2 ml-8 space-y-2">
                    @foreach($item->children as $child)
                    <div class="border-l-2 border-gray-200 pl-4 flex items-center justify-between py-1">
                        <div>
                            <p class="text-sm text-gray-700">{{ $child->title }}</p>
                            <p class="text-xs text-gray-400 font-mono">{{ $child->url }}</p>
                        </div>
                        <div class="flex gap-2">
                            <button wire:click="openEditItem({{ $child->id }})" class="text-gray-400 hover:text-[#1a472a]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button wire:click="confirmDelete({{ $child->id }})" class="text-gray-400 hover:text-red-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </x-admin.card>

    {{-- Modal Item --}}
    @if($showItemForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6">
            <h3 class="text-lg font-semibold mb-4">{{ $editItemId ? 'Editar Item' : 'Novo Item de Menu' }}</h3>
            <div class="space-y-4">
                <x-admin.input label="Título *" wire:model="item_title" :error="$errors->first('item_title')"/>
                <x-admin.input label="URL *" wire:model="item_url" placeholder="/" :error="$errors->first('item_url')"/>
                <div class="grid grid-cols-2 gap-4">
                    <x-admin.input label="Ícone" wire:model="item_icon" placeholder="home, star..."/>
                    <x-admin.select label="Abrir em" wire:model="item_target">
                        <option value="_self">Mesma aba</option>
                        <option value="_blank">Nova aba</option>
                    </x-admin.select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <x-admin.input label="Ordem" wire:model="item_order" type="number" min="0"/>
                    <x-admin.select label="Item pai" wire:model="item_parent">
                        <option value="">Nenhum (raiz)</option>
                        @foreach($allItems as $parentItem)
                        @if(!$editItemId || $parentItem->id !== $editItemId)
                        <option value="{{ $parentItem->id }}">{{ $parentItem->title }}</option>
                        @endif
                        @endforeach
                    </x-admin.select>
                </div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" wire:model="item_active" class="w-4 h-4 text-[#1a472a] rounded border-gray-300">
                    <span class="text-sm font-medium text-gray-700">Item ativo</span>
                </label>
            </div>
            <div class="flex gap-3 justify-end mt-6">
                <x-admin.button variant="secondary" wire:click="$set('showItemForm', false)">Cancelar</x-admin.button>
                <x-admin.button wire:click="saveItem">{{ $editItemId ? 'Atualizar' : 'Adicionar' }}</x-admin.button>
            </div>
        </div>
    </div>
    @endif

    @if($confirmDelete)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6">
            <h3 class="text-lg font-semibold mb-2">Confirmar exclusão</h3>
            <p class="text-sm text-gray-600 mb-6">O item e seus subitens serão removidos.</p>
            <div class="flex gap-3 justify-end">
                <x-admin.button variant="secondary" wire:click="$set('confirmDelete', false)">Cancelar</x-admin.button>
                <x-admin.button variant="danger" wire:click="deleteItem">Excluir</x-admin.button>
            </div>
        </div>
    </div>
    @endif
</div>
