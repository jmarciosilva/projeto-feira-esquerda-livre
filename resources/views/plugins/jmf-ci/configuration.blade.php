{{-- Página de Configuração --}}
<div class="space-y-8">
    {{-- Header --}}
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Configuração</h1>
        <p class="text-gray-600 mt-1">Conecte com a plataforma JMF Customer Intelligence</p>
    </div>

    {{-- Status de Conexão --}}
    @if ($message)
        <div @class([
            'rounded-lg p-4',
            'bg-green-50 border border-green-200' => $isOnline,
            'bg-red-50 border border-red-200' => ! $isOnline,
        ])>
            <p @class([
                'text-sm font-medium',
                'text-green-800' => $isOnline,
                'text-red-800' => ! $isOnline,
            ])>
                {{ $message }}
            </p>
            @if ($lastCheckTime)
                <p class="text-xs text-gray-600 mt-1">Verificado em {{ $lastCheckTime }}</p>
            @endif
        </div>
    @endif

    {{-- Formulário de Configuração --}}
    <form wire:submit="validateConnection" class="bg-white rounded-lg shadow p-6 space-y-6">
        {{-- Base URL --}}
        <div>
            <label for="baseUrl" class="block text-sm font-medium text-gray-700 mb-2">
                Base URL da API
            </label>
            <input
                wire:model="baseUrl"
                type="url"
                id="baseUrl"
                placeholder="https://ci.example.com/api/v1"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <p class="text-xs text-gray-500 mt-1">
                Exemplo: https://ci.jmfsystem.com/api/v1
            </p>
        </div>

        {{-- Token --}}
        <div>
            <label for="token" class="block text-sm font-medium text-gray-700 mb-2">
                Token de API
            </label>
            <div class="relative">
                <input
                    wire:model="token"
                    :type="$tokenVisible ? 'text' : 'password'"
                    id="token"
                    placeholder="Cole seu token aqui"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 pr-12"
                />
                <button
                    type="button"
                    wire:click="toggleTokenVisibility"
                    class="absolute right-3 top-2.5 text-gray-500 hover:text-gray-700"
                >
                    @if ($tokenVisible)
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                        </svg>
                    @else
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
                            <path d="M15.171 13.576l1.474-1.474A2 2 0 0018 10c-1.274-4.057-5.064-7-9.542-7-1.69 0-3.306.356-4.764 1.001l2.252 2.252a4 4 0 015.578 5.578z" />
                        </svg>
                    @endif
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-1">
                Gerado no painel administrativo da plataforma (Aplicações → Tokens)
            </p>
        </div>

        {{-- Botões --}}
        <div class="flex space-x-4">
            <button
                type="submit"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition"
            >
                Validar Conexão
            </button>
        </div>
    </form>

    {{-- Status Indicator --}}
    @if ($message)
        <div class="bg-gray-50 rounded-lg p-4">
            <h3 class="text-sm font-medium text-gray-900 mb-3">Status de Conexão</h3>
            <x-jmf-ci-connection-status
                :isOnline="$isOnline"
                :lastChecked="$lastCheckTime ? 'há alguns segundos' : null"
            />
        </div>
    @endif
</div>
