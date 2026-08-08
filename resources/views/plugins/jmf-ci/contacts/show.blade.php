{{-- Detalhe do Contato --}}
<div class="space-y-8">
    {{-- Header com Link Voltar --}}
    <div class="flex items-center space-x-4">
        <a href="#" class="text-blue-600 hover:text-blue-700 font-medium">
            ← Voltar para Contatos
        </a>
    </div>

    @if ($contact)
        {{-- Informações do Contato --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        {{ $contact['name'] ?? 'Sem Nome' }}
                    </h1>
                    <p class="text-gray-600 mt-1">{{ $contact['email'] ?? 'Sem Email' }}</p>
                </div>
                <div class="space-y-4 text-sm">
                    <div>
                        <span class="text-gray-600">Lead Score:</span>
                        <div class="flex items-center mt-1">
                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ ($contact['lead_score'] ?? 0) * 1 }}%"></div>
                            </div>
                            <span class="ml-2 font-medium">{{ $contact['lead_score'] ?? 0 }}</span>
                        </div>
                    </div>
                    <div>
                        <span class="text-gray-600">Cadastrado em:</span>
                        <p class="text-gray-900 mt-1">{{ $contact['created_at'] ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Último Evento:</span>
                        <p class="text-gray-900 mt-1">{{ $contact['last_event_at'] ?? 'Nunca' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Timeline de Eventos --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Timeline de Eventos</h2>
            </div>

            @if (count($events) > 0)
                <div class="divide-y divide-gray-200">
                    @foreach ($events as $event)
                        <div class="px-6 py-4 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $event['event_name'] ?? 'Evento' }}</p>
                                    <p class="text-sm text-gray-600 mt-1">{{ $event['occurred_at'] ?? '-' }}</p>
                                </div>
                                @if ($event['properties'] ?? false)
                                    <details class="text-sm">
                                        <summary class="text-blue-600 hover:text-blue-700 cursor-pointer">
                                            Ver propriedades
                                        </summary>
                                        <pre class="mt-2 text-xs bg-gray-50 p-2 rounded overflow-auto">{{ json_encode($event['properties'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-12 text-center">
                    <p class="text-gray-500">Nenhum evento encontrado</p>
                </div>
            @endif

            {{-- Paginação --}}
            @if ($total > $perPage)
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
                    @if ($currentPage > 1)
                        <button wire:click="previousPage" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            ← Anterior
                        </button>
                    @else
                        <div></div>
                    @endif

                    <span class="text-sm text-gray-600">
                        Página {{ $currentPage }} de {{ ceil($total / $perPage) }}
                    </span>

                    @if ($currentPage < ceil($total / $perPage))
                        <button wire:click="nextPage" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            Próximo →
                        </button>
                    @else
                        <div></div>
                    @endif
                </div>
            @endif
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <p class="text-gray-600">Contato não encontrado</p>
        </div>
    @endif
</div>
