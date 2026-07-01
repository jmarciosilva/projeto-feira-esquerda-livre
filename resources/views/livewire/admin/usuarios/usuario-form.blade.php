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

            <x-admin.button type="submit" class="w-full justify-center">
                Salvar Usuário
            </x-admin.button>
        </div>
    </form>
</div>
