<div>
    <div class="mb-5">
        <a href="{{ route('admin.usuarios.index') }}" class="text-sm font-semibold" style="color:#1a472a;">
            ← Voltar para usuários
        </a>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-admin.card title="Dados do usuário" description="Usuários internos acessam o painel administrativo conforme o papel e as permissões vinculadas.">
                <div class="space-y-4">
                    <x-admin.input
                        label="Nome"
                        wire:model="name"
                        placeholder="Nome completo"
                        :error="$errors->first('name')"
                    />

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-admin.input
                            label="E-mail"
                            type="email"
                            wire:model="email"
                            placeholder="nome@exemplo.com"
                            :error="$errors->first('email')"
                        />

                        <x-admin.input
                            label="WhatsApp"
                            wire:model="whatsapp"
                            placeholder="(11) 99999-9999"
                            :error="$errors->first('whatsapp')"
                        />
                    </div>

                    <x-admin.select label="Papel" wire:model="role" :error="$errors->first('role')">
                        @foreach($roles as $roleOption)
                        <option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</option>
                        @endforeach
                    </x-admin.select>
                </div>
            </x-admin.card>

            <x-admin.card title="Acesso" description="Informe uma senha ou deixe vazio para gerar uma senha temporária.">
                <div class="space-y-4">
                    <x-admin.input
                        label="{{ $user ? 'Nova senha (opcional)' : 'Senha inicial (opcional)' }}"
                        type="password"
                        wire:model="password"
                        placeholder="Mínimo 8 caracteres"
                        hint="Se ficar vazio, o sistema gera uma senha temporária segura."
                        :error="$errors->first('password')"
                    />

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="send_credentials" class="mt-1 w-4 h-4 rounded border-gray-300" style="accent-color:#1a472a;">
                        <span>
                            <span class="block text-sm font-semibold text-gray-800">Enviar credenciais por e-mail</span>
                            <span class="block text-xs text-gray-500">Usado ao criar usuário ou redefinir senha pelo formulário.</span>
                        </span>
                    </label>
                </div>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="Status">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" wire:model="is_active" class="mt-1 w-4 h-4 rounded border-gray-300" style="accent-color:#1a472a;">
                    <span>
                        <span class="block text-sm font-semibold text-gray-800">Usuário ativo</span>
                        <span class="block text-xs text-gray-500">Usuários inativos não acessam o painel.</span>
                    </span>
                </label>
            </x-admin.card>

            @if($user)
            <x-admin.card title="Perfil de Cliente">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" wire:model="isAlsoCustomer" class="mt-1 w-4 h-4 rounded border-gray-300" style="accent-color:#1a472a;">
                    <span>
                        <span class="block text-sm font-semibold text-gray-800">Também é cliente do marketplace</span>
                        <span class="block text-xs text-gray-500">
                            Permite que este usuário realize compras e tenha histórico de pedidos como cliente.
                        </span>
                    </span>
                </label>

                @if($user->customerProfile)
                <div class="mt-3 flex items-center gap-1.5 text-xs text-gray-500">
                    <svg class="w-3.5 h-3.5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Perfil de cliente ativo —
                    status marketplace: <strong>{{ $user->customerProfile->marketplace_status->label() }}</strong>.
                </div>
                @endif
            </x-admin.card>
            @endif

            <x-admin.button type="submit" class="w-full justify-center">
                Salvar Usuário
            </x-admin.button>
        </div>
    </form>

    {{-- Modal: Promover cliente existente a usuário interno --}}
    @if($showPromoteModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6">

            <div class="flex items-start gap-4 mb-5">
                <div class="w-10 h-10 rounded-full bg-yellow-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-semibold text-gray-900">Usuário já existe como cliente</h3>
                    @if($candidateUser)
                    <p class="text-sm text-gray-600 mt-1">
                        <strong>{{ $candidateUser->name }}</strong>
                        <span class="text-gray-400">({{ $candidateUser->email }})</span>
                        já é um cliente do marketplace.
                    </p>
                    @endif
                </div>
            </div>

            <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800 mb-5">
                Deseja conceder acesso ao painel administrativo para este usuário?
                Pedidos, endereços e histórico de compras serão totalmente preservados.
            </div>

            <x-admin.select
                label="Papel interno a conceder"
                wire:model="promoteRole"
                :error="$errors->first('promoteRole')"
            >
                @foreach($roles as $roleOption)
                <option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</option>
                @endforeach
            </x-admin.select>

            <div class="flex gap-3 justify-end mt-6">
                <x-admin.button variant="secondary" wire:click="cancelPromote">
                    Cancelar
                </x-admin.button>
                <x-admin.button wire:click="promoteToInternal">
                    Conceder Acesso Interno
                </x-admin.button>
            </div>
        </div>
    </div>
    @endif
</div>
