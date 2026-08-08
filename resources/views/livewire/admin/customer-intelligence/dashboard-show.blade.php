<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Inteligência de Cliente</h1>
            <p class="mt-2 text-gray-600">
                Rastreamento e análise comportamental de visitantes e clientes
            </p>
        </div>

        <!-- Status de Conexão -->
        <div class="mb-8">
            <livewire:jmf-ci-configuration />
        </div>

        <!-- Dashboard Metrics -->
        <div class="mb-8">
            <livewire:jmf-ci-dashboard />
        </div>

        <!-- Seção de Contatos -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Contatos Rastreados</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Visitantes e clientes que interagiram com a plataforma
                </p>
            </div>
            <div class="p-6">
                <livewire:jmf-ci-contact-index />
            </div>
        </div>

        <!-- Seção de Eventos -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Eventos Rastreados</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Visualizações, adicionar ao carrinho, compras e outras ações
                </p>
            </div>
            <div class="p-6">
                <livewire:jmf-ci-event-index />
            </div>
        </div>

        <!-- Footer Info -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0zM8 9a1 1 0 100-2 1 1 0 000 2zm5-1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        <strong>Documentação:</strong> Consulte
                        <a href="{{ route('admin.customer-intelligence.docs') }}" class="font-medium underline hover:text-blue-600">
                            o guia de integração completo
                        </a>
                        para mais informações sobre rastreamento de eventos e configuração.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
