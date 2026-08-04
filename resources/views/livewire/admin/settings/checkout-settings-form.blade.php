<div>
    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-blue-800 text-sm">
        <strong>Checkout:</strong> o frete segue manual nesta etapa. Quando o Mercado Pago estiver ativo, todos os
        pagamentos dos clientes entram na conta Mercado Pago da Feira Esquerda Livre configurada abaixo.
    </div>

    @if($saved)
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
         class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        Configuracoes salvas com sucesso!
    </div>
    @endif

    @if(session('melhor_envio_success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('melhor_envio_success') }}
    </div>
    @endif

    @if(session('melhor_envio_error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
        {{ session('melhor_envio_error') }}
    </div>
    @endif

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-2 space-y-6">

                <x-admin.card title="Frete" description="Mensagem para o modo manual e escolha de qual provedor calcula o frete automatico">
                    <div class="space-y-4">
                        <x-admin.textarea
                            label="Mensagem exibida ao cliente no checkout"
                            wire:model="frete_mensagem_manual"
                            rows="3"
                            placeholder="Frete a combinar diretamente com o(s) lojista(s) via WhatsApp."
                            :error="$errors->first('frete_mensagem_manual')"
                        />
                        <x-admin.input
                            label="Valor de referencia (opcional, R$)"
                            wire:model="frete_valor_padrao"
                            type="number" step="0.01" min="0"
                            placeholder="0,00"
                            hint="Apenas informativo - nao e cobrado automaticamente nesta fase."
                            :error="$errors->first('frete_valor_padrao')"
                        />

                        <div class="pt-2 border-t border-gray-100">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Provedor de frete ativo no checkout</label>
                            <select wire:model="frete_provedor"
                                    class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788] {{ $errors->first('frete_provedor') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}">
                                <option value="melhor_envio">Melhor Envio</option>
                                <option value="frenet">Frenet</option>
                            </select>
                            @error('frete_provedor') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-1 text-xs text-gray-400">
                                Troca manual: so um provedor calcula o frete por vez. Ative o provedor escolhido no card correspondente antes de selecioná-lo aqui.
                            </p>
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.card title="Melhor Envio" description="Conecte via OAuth2 para calcular e comprar fretes automaticamente">
                    <div class="space-y-4">
                        <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg border"
                               style="{{ $melhor_envio_ativo ? 'background:#f0fdf4; border-color:#86efac;' : 'background:#fff; border-color:#e5e7eb;' }}">
                            <input type="checkbox" wire:model="melhor_envio_ativo" class="mt-1 w-4 h-4 text-[#52b788] rounded border-gray-300">
                            <span>
                                <span class="block text-sm font-semibold text-gray-800">Ativar Melhor Envio no checkout</span>
                                <span class="block text-xs text-gray-500">Quando ativo, o frete e calculado automaticamente com o token conectado abaixo.</span>
                            </span>
                        </label>

                        @if($melhor_envio_connected)
                        <div class="p-3 rounded-lg border border-green-200 bg-green-50 text-sm text-green-800 flex items-center justify-between gap-3">
                            <span>
                                <strong>Conectado.</strong>
                                @if($melhor_envio_expires_at)
                                    Token valido ate {{ $melhor_envio_expires_at }} (renovacao automatica).
                                @endif
                            </span>
                            <button type="button" wire:click="disconnectMelhorEnvio"
                                    wire:confirm="Desconectar o Melhor Envio? O frete automatico sera desativado."
                                    class="text-xs font-semibold text-red-700 hover:text-red-900 whitespace-nowrap">
                                Desconectar
                            </button>
                        </div>
                        @else
                        <div class="p-3 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-600">
                            Nao conectado. Salve o Client ID e o Client Secret abaixo e clique em "Conectar com Melhor Envio".
                        </div>
                        @endif

                        <x-admin.input
                            label="Client ID"
                            wire:model="melhor_envio_client_id"
                            placeholder="Disponivel apos cadastro do app na Melhor Envio"
                            :error="$errors->first('melhor_envio_client_id')"
                        />
                        <x-admin.input
                            label="Client Secret"
                            type="password"
                            wire:model="melhor_envio_client_secret"
                            placeholder="{{ $melhor_envio_client_secret ? '************' : '' }}"
                            :error="$errors->first('melhor_envio_client_secret')"
                        />

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="melhor_envio_sandbox" class="w-4 h-4 text-[#52b788] rounded border-gray-300">
                            <span class="text-sm text-gray-700">Usar ambiente de testes (sandbox)</span>
                        </label>

                        <a href="{{ route('admin.melhor-envio.connect') }}"
                           class="inline-flex items-center justify-center gap-2 w-full px-4 py-2 rounded-lg text-sm font-semibold text-white bg-[#52b788] hover:bg-[#3f9d70] transition-colors">
                            {{ $melhor_envio_connected ? 'Reconectar com Melhor Envio' : 'Conectar com Melhor Envio' }}
                        </a>
                        <p class="text-xs text-gray-400">
                            URL de redirecionamento a cadastrar no app da Melhor Envio:
                            <code class="break-all">{{ route('admin.melhor-envio.callback') }}</code>
                        </p>

                        <details class="text-xs text-gray-500">
                            <summary class="cursor-pointer select-none">Avancado: informar Access Token manualmente</summary>
                            <div class="mt-3">
                                <x-admin.input
                                    label="Access Token"
                                    type="password"
                                    wire:model="melhor_envio_token"
                                    placeholder="{{ $melhor_envio_token ? '************' : '' }}"
                                    :error="$errors->first('melhor_envio_token')"
                                />
                            </div>
                        </details>
                    </div>
                </x-admin.card>

                <x-admin.card title="Frenet" description="Plano B de calculo de frete - token unico, sem OAuth">
                    <div class="space-y-4">
                        <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg border"
                               style="{{ $frenet_ativo ? 'background:#f0fdf4; border-color:#86efac;' : 'background:#fff; border-color:#e5e7eb;' }}">
                            <input type="checkbox" wire:model="frenet_ativo" class="mt-1 w-4 h-4 text-[#52b788] rounded border-gray-300">
                            <span>
                                <span class="block text-sm font-semibold text-gray-800">Ativar Frenet</span>
                                <span class="block text-xs text-gray-500">So passa a calcular o frete se tambem for escolhida como provedor ativo acima.</span>
                            </span>
                        </label>

                        <x-admin.input
                            label="Token"
                            type="password"
                            wire:model="frenet_token"
                            placeholder="{{ $frenet_token ? '************' : '' }}"
                            hint="Painel Frenet → icone de usuario → Dados Cadastrais → Token."
                            :error="$errors->first('frenet_token')"
                        />
                    </div>
                </x-admin.card>

            </div>

            <div class="space-y-6">

                <x-admin.card title="Pagamento" description="Configure a comissao e escolha o meio de pagamento ativo">
                    <div class="space-y-4">
                        <x-admin.input
                            label="Comissao da plataforma (%)"
                            wire:model="comissao_percentual"
                            type="number" step="0.01" min="0" max="100"
                            hint="Usada apenas para registrar o valor liquido por loja nos relatorios internos. O dinheiro entra primeiro na conta da Feira Esquerda Livre."
                            :error="$errors->first('comissao_percentual')"
                        />
                    </div>
                </x-admin.card>

                <x-admin.card title="Mercado Pago" description="Pagamento embutido (Payment Brick) - cartao, Pix e boleto sem sair do site">
                    <div class="space-y-4">
                        <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg border"
                               style="{{ $mercado_pago_ativo ? 'background:#f0fdf4; border-color:#86efac;' : 'background:#fff; border-color:#e5e7eb;' }}">
                            <input type="checkbox" wire:model="mercado_pago_ativo" class="mt-1 w-4 h-4 text-[#52b788] rounded border-gray-300">
                            <span>
                                <span class="block text-sm font-semibold text-gray-800">Ativar Mercado Pago no checkout</span>
                                <span class="block text-xs text-gray-500">Quando ativo, o pagamento acontece direto na pagina do pedido, na conta Mercado Pago da Feira Esquerda Livre.</span>
                            </span>
                        </label>

                        <p class="text-xs text-gray-400">
                            Nao usa Client ID/Secret - pegue apenas Public Key e Access Token na aba "Credenciais" da sua aplicacao no painel do Mercado Pago.
                        </p>

                        <x-admin.input
                            label="Public Key"
                            wire:model="mercado_pago_public_key"
                            placeholder="APP_USR-..."
                            :error="$errors->first('mercado_pago_public_key')"
                        />
                        <x-admin.input
                            label="Access Token"
                            type="password"
                            wire:model="mercado_pago_access_token"
                            placeholder="{{ $mercado_pago_access_token ? '************' : '' }}"
                            :error="$errors->first('mercado_pago_access_token')"
                        />
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="mercado_pago_sandbox" class="w-4 h-4 text-[#52b788] rounded border-gray-300">
                            <span class="text-sm text-gray-700">Usar ambiente de testes (sandbox)</span>
                        </label>
                    </div>
                </x-admin.card>

                <x-admin.button type="submit" class="w-full justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Salvar Configuracoes
                </x-admin.button>
            </div>
        </div>
    </form>
</div>
