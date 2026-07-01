<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-blue-800 text-xs">
        A seleção da home é cacheada por <strong>{{ config('app.home_cache_ttl_minutes', 5) }} minutos</strong>
        · Exibe até <strong>{{ config('app.home_expositores_count', 8) }}</strong> expositores
        · Máximo de <strong>{{ config('app.home_featured_max', 2) }}</strong> destaques pagos simultâneos
    </div>

    <div class="mb-6">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar expositor..."
               class="w-full sm:w-80 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
    </div>

    <x-admin.card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Expositor</th>
                        <th class="text-center py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Impressões (30d)</th>
                        <th class="text-center py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Total</th>
                        <th class="text-center py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status na Home</th>
                        <th class="text-center py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Peso</th>
                        <th class="text-right py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($expositores as $expositor)
                    @php
                        $slot    = $expositor->activeSlot();
                        $onHome  = $expositor->is_featured;
                        $imp30   = $expositor->impressionsLastDays(30);
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-2">
                            <div class="flex items-center gap-3">
                                @if($expositor->logo_path)
                                <img src="{{ Storage::url($expositor->logo_path) }}" alt="" class="w-8 h-8 rounded-lg object-cover flex-shrink-0">
                                @else
                                <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0 text-gray-400 text-xs font-bold">
                                    {{ mb_substr($expositor->name, 0, 1) }}
                                </div>
                                @endif
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $expositor->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $expositor->city }}{{ $expositor->state ? ', '.$expositor->state : '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-2 text-center hidden md:table-cell">
                            <span class="font-semibold text-gray-800">{{ number_format($imp30) }}</span>
                        </td>
                        <td class="py-3 px-2 text-center hidden md:table-cell text-xs text-gray-500">
                            {{ number_format($expositor->total_impressions) }}
                        </td>
                        <td class="py-3 px-2 text-center">
                            @if(! $onHome)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">Fora da home</span>
                            @elseif($slot && $slot->slot_type === \App\Enums\VisibilitySlotType::HomeFeatured && $slot->isActive())
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                    ⭐ Destaque
                                    @if($slot->active_until)
                                    <span class="text-yellow-600">até {{ $slot->active_until->format('d/m/Y') }}</span>
                                    @endif
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Rotação</span>
                            @endif
                        </td>
                        <td class="py-3 px-2 text-center hidden sm:table-cell">
                            @if($onHome)
                            <button wire:click="openWeightModal({{ $expositor->id }})"
                                    class="text-xs font-mono font-semibold text-gray-700 hover:text-gray-900 px-2 py-1 rounded-lg border border-gray-200 hover:border-gray-400 transition-colors">
                                {{ $expositor->home_rotation_weight }}×
                            </button>
                            @else
                            <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-2 text-right">
                            <div class="flex items-center justify-end gap-2 flex-wrap">
                                @if($onHome)
                                <button wire:click="openSlotModal({{ $expositor->id }})"
                                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                    {{ $slot ? 'Editar slot' : 'Configurar destaque' }}
                                </button>
                                @if($slot)
                                <button wire:click="removeSlot({{ $expositor->id }})"
                                        wire:confirm="Remover slot de destaque de {{ $expositor->name }}? Ele voltará para rotação padrão."
                                        class="text-xs font-semibold text-red-500 hover:text-red-700">
                                    Remover slot
                                </button>
                                @endif
                                <button wire:click="toggleHomeVisibility({{ $expositor->id }})"
                                        wire:confirm="Retirar {{ $expositor->name }} da home?"
                                        class="text-xs font-semibold text-gray-400 hover:text-gray-600">
                                    Retirar da home
                                </button>
                                @else
                                <button wire:click="toggleHomeVisibility({{ $expositor->id }})"
                                        class="text-xs font-semibold text-green-600 hover:text-green-800">
                                    Adicionar à home
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-16 text-center">
                            <div class="text-5xl mb-4">🏪</div>
                            <p class="text-base font-semibold text-gray-500">Nenhum expositor encontrado.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($expositores->hasPages())
        <div class="pt-4">{{ $expositores->links() }}</div>
        @endif
    </x-admin.card>

    {{-- Modal: Configurar slot de destaque --}}
    @if($showSlotModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="$set('showSlotModal', false)">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-base font-bold text-gray-900 mb-5">Configurar slot de visibilidade</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo de slot</label>
                    <select wire:model="slotType" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
                        @foreach($slotTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Prioridade (0 = rotação, 1–100 = destaque)</label>
                    <input wire:model="slotPriority" type="number" min="0" max="100"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
                    @error('slotPriority') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Início</label>
                        <input wire:model="slotActiveFrom" type="datetime-local"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
                        @error('slotActiveFrom') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Fim</label>
                        <input wire:model="slotActiveUntil" type="datetime-local"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
                        @error('slotActiveUntil') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <p class="text-xs text-gray-400">Deixe as datas em branco para vigência indefinida.</p>
            </div>

            <div class="flex gap-3 mt-6">
                <button wire:click="saveSlot"
                        class="flex-1 py-2.5 rounded-xl font-bold text-sm"
                        style="background:#1a472a; color:#F4E294;">
                    Salvar
                </button>
                <button wire:click="$set('showSlotModal', false)"
                        class="flex-1 py-2.5 rounded-xl font-semibold text-sm border-2 border-gray-200 text-gray-600">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal: Ajustar peso de rotação --}}
    @if($showWeightModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="$set('showWeightModal', false)">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
            <h3 class="text-base font-bold text-gray-900 mb-2">Peso na rotação democrática</h3>
            <p class="text-sm text-gray-500 mb-5">
                Peso <strong>1</strong> = participação normal. Pesos maiores aumentam a frequência de aparição no sorteio.
            </p>

            <div class="flex items-center gap-4 mb-6">
                <input wire:model="weightValue" type="range" min="1" max="10" step="1"
                       class="flex-1 accent-[#1a472a]">
                <span class="text-2xl font-extrabold w-8 text-center" style="color:#1a472a;">{{ $weightValue }}</span>
            </div>

            @error('weightValue') <p class="text-red-500 text-xs mb-3">{{ $message }}</p> @enderror

            <div class="flex gap-3">
                <button wire:click="saveWeight"
                        class="flex-1 py-2.5 rounded-xl font-bold text-sm"
                        style="background:#1a472a; color:#F4E294;">
                    Salvar
                </button>
                <button wire:click="$set('showWeightModal', false)"
                        class="flex-1 py-2.5 rounded-xl font-semibold text-sm border-2 border-gray-200 text-gray-600">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
