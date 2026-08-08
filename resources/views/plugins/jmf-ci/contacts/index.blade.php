{{-- Tabela de Contatos --}}
<div class="space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Contatos</h1>
        <p class="text-gray-600 mt-1">Gerencie todos os seus contatos e clientes</p>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow p-4 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Busca --}}
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                    Buscar por email ou nome
                </label>
                <input
                    wire:model.live="search"
                    type="text"
                    id="search"
                    placeholder="Digite para filtrar..."
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
        title="Contatos"
        :headers="['Email', 'Nome', 'Lead Score', 'Último Evento', 'Ação']"
        :items="$contacts"
        :paginator="null"
        emptyMessage="Nenhum contato encontrado"
    >
        @foreach ($contacts as $contact)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                    {{ $contact['email'] ?? '-' }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">
                    {{ $contact['name'] ?? '-' }}
                </td>
                <td class="px-6 py-4 text-sm">
                    <div class="flex items-center">
                        <div class="w-16 bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ ($contact['lead_score'] ?? 0) * 1 }}%"></div>
                        </div>
                        <span class="ml-2 text-sm font-medium">{{ $contact['lead_score'] ?? 0 }}</span>
                    </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">
                    {{ $contact['last_event_at'] ?? 'Nunca' }}
                </td>
                <td class="px-6 py-4 text-sm">
                    <a href="#" class="text-blue-600 hover:text-blue-700 font-medium">
                        Ver detalhes
                    </a>
                </td>
            </tr>
        @endforeach
    </x-jmf-ci-event-table>

    {{-- Paginação --}}
    @if ($total > $perPage)
        <div class="flex justify-center">
            {{-- Simplificado: mostrar apenas links voltar/próximo --}}
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
