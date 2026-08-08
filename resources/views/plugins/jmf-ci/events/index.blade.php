{{-- Tabela de Eventos --}}
<div class="space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Eventos</h1>
        <p class="text-gray-600 mt-1">Veja todos os eventos rastreados</p>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow p-4 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Busca Geral --}}
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                    Buscar
                </label>
                <input
                    wire:model.live="search"
                    type="text"
                    id="search"
                    placeholder="Buscar por visitante..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
            </div>

            {{-- Tipo de Evento --}}
            <div>
                <label for="eventName" class="block text-sm font-medium text-gray-700 mb-1">
                    Tipo de Evento
                </label>
                <input
                    wire:model.live="eventName"
                    type="text"
                    id="eventName"
                    placeholder="Ex: product.viewed"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
            </div>

            {{-- Período --}}
            <div>
                <label for="period" class="block text-sm font-medium text-gray-700 mb-1">
                    Período
                </label>
                <select
                    wire:model.live="period"
                    id="period"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="today">Hoje</option>
                    <option value="7">Últimos 7 dias</option>
                    <option value="30">Últimos 30 dias</option>
                    <option value="90">Últimos 90 dias</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Tabela --}}
    <x-jmf-ci-event-table
        title="Eventos"
        :headers="['Evento', 'Visitante', 'Contato', 'Data', 'Propriedades']"
        :items="$events"
        :paginator="null"
        emptyMessage="Nenhum evento encontrado"
    >
        @foreach ($events as $event)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                    {{ $event['event_name'] ?? '-' }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">
                    <code class="text-xs bg-gray-100 px-2 py-1 rounded">
                        {{ substr($event['visitor_id'] ?? '-', 0, 8) }}...
                    </code>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">
                    {{ $event['contact_email'] ?? '-' }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">
                    {{ $event['occurred_at'] ?? '-' }}
                </td>
                <td class="px-6 py-4 text-sm">
                    @if ($event['properties'] ?? false)
                        <details>
                            <summary class="text-blue-600 hover:text-blue-700 cursor-pointer">
                                Ver
                            </summary>
                            <pre class="mt-2 text-xs bg-gray-50 p-2 rounded overflow-auto">{{ json_encode($event['properties'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </details>
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-jmf-ci-event-table>

    {{-- Paginação --}}
    @if ($total > $perPage)
        <div class="flex justify-center">
            <div class="flex space-x-2">
                @if ($currentPage > 1)
                    <button wire:click="previousPage" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        ← Anterior
                    </button>
                @endif

                <span class="px-4 py-2 text-gray-700">
                    Página {{ $currentPage }} de {{ ceil($total / $perPage) }}
                </span>

                @if ($currentPage < ceil($total / $perPage))
                    <button wire:click="nextPage" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        Próximo →
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>
