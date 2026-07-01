<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar eventos..." class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
        @if($states->isNotEmpty())
        <select wire:model.live="filterState" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
            <option value="">Todos os estados</option>
            @foreach($states as $state)
            <option value="{{ $state }}">{{ $state }}</option>
            @endforeach
        </select>
        @endif
        @can('cms.editar')
        <a href="{{ route('admin.events.create') }}">
            <x-admin.button>+ Novo Evento</x-admin.button>
        </a>
        @endcan
    </div>

    <x-admin.card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Evento</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Local</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Data Início</th>
                        <th class="text-center py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="text-right py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($events as $event)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-2">
                            <div class="flex items-center gap-3">
                                @if($event->image_path)
                                <img src="{{ Storage::url($event->image_path) }}" alt="{{ $event->title }}" class="w-10 h-10 rounded-lg object-cover flex-shrink-0">
                                @else
                                <div class="w-10 h-10 rounded-lg bg-[#f0fdf4] flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-[#52b788]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                @endif
                                <div>
                                    <p class="font-medium text-gray-900 max-w-xs truncate">{{ $event->title }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-2 text-gray-600">
                            {{ $event->city }}@if($event->state) — {{ $event->state }}@endif
                        </td>
                        <td class="py-3 px-2 text-gray-600 text-xs">
                            {{ $event->start_date->format('d/m/Y H:i') }}
                        </td>
                        <td class="py-3 px-2 text-center">
                            @can('cms.editar')
                            <button wire:click="toggleActive({{ $event->id }})">
                                <x-admin.badge :color="$event->is_active ? 'green' : 'gray'">
                                    {{ $event->is_active ? 'Ativo' : 'Inativo' }}
                                </x-admin.badge>
                            </button>
                            @else
                            <x-admin.badge :color="$event->is_active ? 'green' : 'gray'">
                                {{ $event->is_active ? 'Ativo' : 'Inativo' }}
                            </x-admin.badge>
                            @endcan
                        </td>
                        <td class="py-3 px-2">
                            @can('cms.editar')
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.events.edit', $event) }}" class="text-gray-400 hover:text-[#1a472a]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <button wire:click="confirmDelete({{ $event->id }})" class="text-gray-400 hover:text-red-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="py-10 text-center text-gray-400">Nenhum evento encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $events->links() }}</div>
    </x-admin.card>

    @if($confirmDelete)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6">
            <h3 class="text-lg font-semibold mb-2">Confirmar exclusão</h3>
            <p class="text-sm text-gray-600 mb-6">O evento será excluído permanentemente.</p>
            <div class="flex gap-3 justify-end">
                <x-admin.button variant="secondary" wire:click="$set('confirmDelete', false)">Cancelar</x-admin.button>
                <x-admin.button variant="danger" wire:click="deleteEvent">Excluir</x-admin.button>
            </div>
        </div>
    </div>
    @endif
</div>
