<div>
    @if($saved)
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
         class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        Configurações de inteligência artificial salvas com sucesso!
    </div>
    @endif

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Coluna Principal --}}
            <div class="lg:col-span-2 space-y-6">

                <x-admin.card title="Provider externo" description="Sugestões do assistente de cadastro de produtos com um provider de IA externo">
                    <div class="space-y-4">
                        <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg border"
                               style="{{ $ativo ? 'background:#f0fdf4; border-color:#86efac;' : 'background:#fff; border-color:#e5e7eb;' }}">
                            <input type="checkbox" wire:model="ativo" class="mt-1 w-4 h-4 text-[#52b788] rounded border-gray-300">
                            <span>
                                <span class="block text-sm font-semibold text-gray-800">Ativar provider externo</span>
                                <span class="block text-xs text-gray-500">Desativado, o assistente usa só a inteligência interna da Feira.</span>
                            </span>
                        </label>
                        @error('ativo')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

                        <x-admin.select label="Provider" wire:model="provider" :error="$errors->first('provider')">
                            <option value="openai">OpenAI</option>
                        </x-admin.select>

                        <x-admin.input
                            label="Modelo"
                            wire:model="modelo"
                            autocomplete="off"
                            spellcheck="false"
                            placeholder="Identificador do modelo no provider"
                            hint="Informe exatamente o identificador de um modelo disponível na conta do provider."
                            :error="$errors->first('modelo')"
                        />

                        <x-admin.input
                            label="Timeout (segundos)"
                            wire:model="timeout"
                            type="number" min="1" max="8" step="1"
                            placeholder="8"
                            hint="De 1 a 8 segundos. Em branco, vale 8."
                            :error="$errors->first('timeout')"
                        />
                    </div>
                </x-admin.card>

                <x-admin.card title="API Key" description="Fica criptografada e nunca é exibida depois de salva">
                    <div class="space-y-4">
                        @if($chaveConfigurada)
                        <div class="p-3 rounded-lg border border-green-200 bg-green-50 text-sm text-green-800 flex items-center justify-between gap-3">
                            <span><strong>Chave configurada.</strong></span>
                            <button type="button" wire:click="removerChave"
                                    wire:confirm="Remover a API key? O provider externo será desativado."
                                    class="text-xs font-semibold text-red-700 hover:text-red-900 whitespace-nowrap">
                                Remover chave
                            </button>
                        </div>
                        @else
                        <div class="p-3 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-600">
                            Nenhuma chave configurada.
                        </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $chaveConfigurada ? 'Substituir chave' : 'API Key' }}</label>
                            <input
                                type="password"
                                wire:model="novaChave"
                                autocomplete="new-password"
                                spellcheck="false"
                                placeholder="{{ $chaveConfigurada ? 'Digite a nova chave para substituir a atual' : 'Cole a API key do provider' }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#52b788] focus:border-[#52b788] transition-colors"
                            >
                            <p class="mt-1 text-xs text-gray-400">
                                {{ $chaveConfigurada ? 'Deixe em branco para manter a chave atual.' : 'Obrigatória para ativar o provider externo.' }}
                            </p>
                            @error('novaChave')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </x-admin.card>

            </div>

            {{-- Coluna Lateral --}}
            <div class="space-y-6">

                <x-admin.card title="Como funciona">
                    <div class="space-y-2 text-xs text-gray-600">
                        <p>A alteração vale para as próximas sugestões geradas, sem reiniciar a aplicação.</p>
                        <p>Configuração incompleta ou desativada mantém o assistente só com a inteligência interna, sem erro para o lojista.</p>
                        <p>Esta tela guarda só a credencial e os parâmetros do provider — nenhum prompt, resposta ou conteúdo de produto.</p>
                    </div>
                </x-admin.card>

                <x-admin.button type="submit" class="w-full justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Salvar Configurações
                </x-admin.button>

            </div>
        </div>
    </form>
</div>
