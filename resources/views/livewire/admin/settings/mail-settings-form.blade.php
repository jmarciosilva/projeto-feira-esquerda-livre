<div>
    @if($saved)
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
         class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        Configurações de e-mail salvas com sucesso!
    </div>
    @endif

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Coluna Principal --}}
            <div class="lg:col-span-2 space-y-6">

                <x-admin.card title="Servidor de Envio (SMTP)">
                    <div class="space-y-4">
                        <x-admin.select label="Driver de envio" wire:model.live="mail_mailer">
                            <option value="smtp">SMTP</option>
                            <option value="sendmail">Sendmail</option>
                            <option value="log">Log (sem envio real — para testes)</option>
                        </x-admin.select>

                        @if($mail_mailer === 'smtp')
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <x-admin.input
                                    label="Servidor (host)"
                                    wire:model="mail_host"
                                    placeholder="smtp.gmail.com"
                                    :error="$errors->first('mail_host')"
                                />
                            </div>
                            <x-admin.input
                                label="Porta"
                                wire:model="mail_port"
                                type="number"
                                placeholder="587"
                                :error="$errors->first('mail_port')"
                            />
                        </div>

                        <x-admin.select label="Criptografia" wire:model="mail_encryption" :error="$errors->first('mail_encryption')">
                            <option value="tls">TLS (recomendado)</option>
                            <option value="ssl">SSL</option>
                            <option value="">Nenhuma</option>
                        </x-admin.select>

                        <x-admin.input
                            label="Usuário (username)"
                            wire:model="mail_username"
                            placeholder="seu@email.com"
                            :error="$errors->first('mail_username')"
                        />

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                            <input
                                type="password"
                                wire:model="mail_password"
                                autocomplete="new-password"
                                placeholder="{{ $mail_password ? '••••••••••••' : 'Senha do servidor SMTP' }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#52b788] focus:border-[#52b788] transition-colors"
                            >
                            <p class="mt-1 text-xs text-gray-400">Deixe em branco para manter a senha atual.</p>
                            @error('mail_password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        @endif
                    </div>
                </x-admin.card>

                <x-admin.card title="Remetente Padrão">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-admin.input
                            label="E-mail de envio (From)"
                            wire:model="mail_from_address"
                            type="email"
                            placeholder="noreply@suafeira.com.br"
                            :error="$errors->first('mail_from_address')"
                        />
                        <x-admin.input
                            label="Nome do remetente"
                            wire:model="mail_from_name"
                            placeholder="Feira Esquerda Livre"
                            :error="$errors->first('mail_from_name')"
                        />
                    </div>
                </x-admin.card>

            </div>

            {{-- Coluna Lateral --}}
            <div class="space-y-6">

                <x-admin.card title="Provedores Comuns">
                    <div class="space-y-3 text-xs text-gray-600">
                        <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                            <p class="font-semibold text-gray-800 mb-1">Gmail</p>
                            <p>Host: <code class="bg-gray-200 px-1 rounded">smtp.gmail.com</code></p>
                            <p>Porta: <code class="bg-gray-200 px-1 rounded">587</code> · TLS</p>
                            <p class="mt-1 text-gray-500">Requer "Senha de app" nas configurações de segurança do Google.</p>
                        </div>
                        <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                            <p class="font-semibold text-gray-800 mb-1">Outlook / Hotmail</p>
                            <p>Host: <code class="bg-gray-200 px-1 rounded">smtp-mail.outlook.com</code></p>
                            <p>Porta: <code class="bg-gray-200 px-1 rounded">587</code> · TLS</p>
                        </div>
                        <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                            <p class="font-semibold text-gray-800 mb-1">Mailgun / SendGrid</p>
                            <p>Consulte o painel do provedor para obter as credenciais SMTP.</p>
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.button type="submit" class="w-full justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Salvar Configurações
                </x-admin.button>

                {{-- Teste de envio --}}
                <x-admin.card title="Testar Envio">
                    <p class="text-xs text-gray-500 mb-3">Salve as configurações antes de testar. Um e-mail será enviado para o endereço abaixo.</p>

                    @if($test_result)
                    <div class="mb-3 p-3 rounded-lg text-sm font-medium {{ $test_ok ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' }}">
                        {{ $test_ok ? '✅' : '❌' }} {{ $test_result }}
                    </div>
                    @endif

                    <x-admin.input
                        label="E-mail de destino"
                        wire:model="test_email"
                        type="email"
                        placeholder="voce@email.com"
                        :error="$errors->first('test_email')"
                    />
                    <button type="button" wire:click="sendTest"
                            class="mt-3 w-full px-4 py-2 rounded-lg text-sm font-semibold border-2 transition-colors"
                            style="border-color: #52b788; color: #1a472a;"
                            onmouseover="this.style.backgroundColor='#f0fdf4'"
                            onmouseout="this.style.backgroundColor=''">
                        <span wire:loading.remove wire:target="sendTest">Enviar E-mail de Teste</span>
                        <span wire:loading wire:target="sendTest">Enviando...</span>
                    </button>
                </x-admin.card>

            </div>
        </div>
    </form>
</div>
