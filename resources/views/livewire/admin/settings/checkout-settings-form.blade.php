<div>
    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-blue-800 text-sm">
        🔧 <strong>MVP em modo manual:</strong> frete e pagamento são combinados diretamente com o lojista (WhatsApp/PIX).
        Os campos de integração com Melhor Envio e Mercado Pago abaixo já podem ser preenchidos e ficam guardados,
        mas a ativação automática será habilitada em uma fase futura.
    </div>

    @if($saved)
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
         class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        Configurações salvas com sucesso!
    </div>
    @endif

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Coluna Principal --}}
            <div class="lg:col-span-2 space-y-6">

                <x-admin.card title="Frete" description="Modo atual: Manual — calculado e combinado com o lojista">
                    <div class="space-y-4">
                        <x-admin.textarea
                            label="Mensagem exibida ao cliente no checkout"
                            wire:model="frete_mensagem_manual"
                            rows="3"
                            placeholder="Frete a combinar diretamente com o(s) lojista(s) via WhatsApp."
                            :error="$errors->first('frete_mensagem_manual')"
                        />
                        <x-admin.input
                            label="Valor de referência (opcional, R$)"
                            wire:model="frete_valor_padrao"
                            type="number" step="0.01" min="0"
                            placeholder="0,00"
                            hint="Apenas informativo — não é cobrado automaticamente nesta fase."
                            :error="$errors->first('frete_valor_padrao')"
                        />
                    </div>
                </x-admin.card>

                <x-admin.card title="Melhor Envio" description="Integração futura — credenciais salvas para ativação posterior">
                    <div class="space-y-4 opacity-80">
                        <x-admin.input
                            label="Client ID"
                            wire:model="melhor_envio_client_id"
                            placeholder="Disponível após cadastro na Melhor Envio"
                            :error="$errors->first('melhor_envio_client_id')"
                        />
                        <x-admin.input
                            label="Client Secret"
                            type="password"
                            wire:model="melhor_envio_client_secret"
                            placeholder="{{ $melhor_envio_client_secret ? '••••••••••••' : '' }}"
                            :error="$errors->first('melhor_envio_client_secret')"
                        />
                        <x-admin.input
                            label="Access Token"
                            type="password"
                            wire:model="melhor_envio_token"
                            placeholder="{{ $melhor_envio_token ? '••••••••••••' : '' }}"
                            :error="$errors->first('melhor_envio_token')"
                        />
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="melhor_envio_sandbox" class="w-4 h-4 text-[#52b788] rounded border-gray-300">
                            <span class="text-sm text-gray-700">Usar ambiente de testes (sandbox) quando a integração for ativada</span>
                        </label>
                    </div>
                </x-admin.card>

            </div>

            {{-- Coluna Lateral --}}
            <div class="space-y-6">

                <x-admin.card title="Pagamento" description="Modo atual: Manual — PIX/banco direto com o lojista">
                    <div class="space-y-4">
                        <x-admin.input
                            label="Comissão da plataforma (%)"
                            wire:model="comissao_percentual"
                            type="number" step="0.01" min="0" max="100"
                            hint="Usada apenas para registro/relatório do repasse — não é retida automaticamente nesta fase."
                            :error="$errors->first('comissao_percentual')"
                        />
                    </div>
                </x-admin.card>

                <x-admin.card title="Mercado Pago" description="Integração futura — credenciais salvas para ativação posterior">
                    <div class="space-y-4 opacity-80">
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
                            placeholder="{{ $mercado_pago_access_token ? '••••••••••••' : '' }}"
                            :error="$errors->first('mercado_pago_access_token')"
                        />
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="mercado_pago_sandbox" class="w-4 h-4 text-[#52b788] rounded border-gray-300">
                            <span class="text-sm text-gray-700">Usar ambiente de testes (sandbox) quando a integração for ativada</span>
                        </label>
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
