<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Upload --}}
    @can('cms.editar')
    <x-admin.card title="Enviar Arquivos" class="mb-6">
        <div class="space-y-3">
            <input type="file" wire:model="uploads" multiple accept="image/*,video/mp4,application/pdf"
                   class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#f0fdf4] file:text-[#1a472a] hover:file:bg-[#dcfce7]">
            @error('uploads.*')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            <div wire:loading wire:target="uploads" class="text-sm text-gray-500 flex items-center gap-2">
                <svg class="animate-spin w-4 h-4 text-[#52b788]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Carregando...
            </div>
            <x-admin.button wire:click="uploadFiles" :disabled="empty($uploads)">
                Enviar {{ count($uploads) > 0 ? count($uploads) . ' arquivo(s)' : '' }}
            </x-admin.button>
        </div>
    </x-admin.card>
    @endcan

    {{-- Filtros --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar arquivos..." class="w-full sm:w-72 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
        <select wire:model.live="filterType" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
            <option value="">Todos os tipos</option>
            <option value="image">Imagens</option>
            <option value="video">Vídeos</option>
            <option value="application/pdf">PDFs</option>
        </select>
    </div>

    {{-- Grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
        @forelse($media as $item)
        <div class="group relative bg-white border border-gray-200 rounded-xl overflow-hidden hover:border-[#52b788] transition-colors">
            @if($item->isImage())
            <img src="{{ $item->url() }}" alt="{{ $item->alt_text ?? $item->file_name }}" class="w-full h-24 object-cover bg-gray-50">
            @else
            <div class="w-full h-24 bg-gray-100 flex items-center justify-center">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            @endif

            <div class="p-2">
                <p class="text-xs text-gray-600 truncate font-medium">{{ $item->file_name }}</p>
                <p class="text-xs text-gray-400">{{ $item->humanSize() }}</p>
            </div>

            @can('cms.editar')
            <div class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <button wire:click="confirmDelete({{ $item->id }})" class="w-6 h-6 bg-red-600 rounded-full flex items-center justify-center text-white hover:bg-red-700">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            @endcan
        </div>
        @empty
        <div class="col-span-full py-16 text-center text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h3.586a1 1 0 01.707.293L11 7h9a2 2 0 012 2v9a2 2 0 01-2 2H4a2 2 0 01-2-2V7z"/></svg>
            Nenhuma mídia encontrada.
        </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $media->links() }}</div>

    @if($confirmDelete)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6">
            <h3 class="text-lg font-semibold mb-2">Confirmar exclusão</h3>
            <p class="text-sm text-gray-600 mb-6">O arquivo será excluído do servidor permanentemente.</p>
            <div class="flex gap-3 justify-end">
                <x-admin.button variant="secondary" wire:click="$set('confirmDelete', false)">Cancelar</x-admin.button>
                <x-admin.button variant="danger" wire:click="deleteMedia">Excluir</x-admin.button>
            </div>
        </div>
    </div>
    @endif
</div>
