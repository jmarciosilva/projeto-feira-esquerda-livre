{{-- Dashboard principal com métricas e tabelas --}}
<div class="space-y-8">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-gray-600 mt-1">Visão geral da inteligência de clientes</p>
        </div>
        <div class="flex space-x-2">
            <button wire:click="setPeriod('today')" @class([
                'px-4 py-2 rounded-lg font-medium',
                'bg-blue-600 text-white' => $period === 'today',
                'bg-gray-200 text-gray-700' => $period !== 'today',
            ])>
                Hoje
            </button>
            <button wire:click="setPeriod('7')" @class([
                'px-4 py-2 rounded-lg font-medium',
                'bg-blue-600 text-white' => $period === '7',
                'bg-gray-200 text-gray-700' => $period !== '7',
            ])>
                7 dias
            </button>
            <button wire:click="setPeriod('30')" @class([
                'px-4 py-2 rounded-lg font-medium',
                'bg-blue-600 text-white' => $period === '30',
                'bg-gray-200 text-gray-700' => $period !== '30',
            ])>
                30 dias
            </button>
            <button wire:click="setPeriod('90')" @class([
                'px-4 py-2 rounded-lg font-medium',
                'bg-blue-600 text-white' => $period === '90',
                'bg-gray-200 text-gray-700' => $period !== '90',
            ])>
                90 dias
            </button>
        </div>
    </div>

    {{-- Métricas --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-jmf-ci-metrics-card
            label="Eventos"
            :value="$metrics['events'] ?? 0"
            color="blue"
        />
        <x-jmf-ci-metrics-card
            label="Visitantes"
            :value="$metrics['visitors'] ?? 0"
            color="green"
        />
        <x-jmf-ci-metrics-card
            label="Sessões"
            :value="$metrics['sessions'] ?? 0"
            color="purple"
        />
        <x-jmf-ci-metrics-card
            label="Conversões"
            :value="$metrics['conversions'] ?? 0"
            color="orange"
        />
    </div>

    {{-- Gráfico de Tendência --}}
    @if ($metrics['trend'] ?? false)
        <x-jmf-ci-event-chart
            :data="$metrics['trend']"
            label="Eventos"
            title="Tendência de Eventos"
            chartId="events-trend"
        />
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Contatos Recentes --}}
        <div>
            <x-jmf-ci-event-table
                title="Contatos Recentes"
                :headers="['Email', 'Nome', 'Lead Score', 'Último Evento']"
                :items="$recentContacts"
                emptyMessage="Nenhum contato encontrado"
            >
                @foreach ($recentContacts as $contact)
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
                            {{ $contact['last_event_at'] ?? '-' }}
                        </td>
                    </tr>
                @endforeach
            </x-jmf-ci-event-table>
        </div>

        {{-- Eventos Recentes --}}
        <div>
            <x-jmf-ci-event-table
                title="Eventos Recentes"
                :headers="['Evento', 'Visitante', 'Contato', 'Data']"
                :items="$recentEvents"
                emptyMessage="Nenhum evento encontrado"
            >
                @foreach ($recentEvents as $event)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            {{ $event['event_name'] ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ substr($event['visitor_id'] ?? '-', 0, 8) }}...</code>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $event['contact_email'] ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $event['occurred_at'] ?? '-' }}
                        </td>
                    </tr>
                @endforeach
            </x-jmf-ci-event-table>
        </div>
    </div>
</div>
