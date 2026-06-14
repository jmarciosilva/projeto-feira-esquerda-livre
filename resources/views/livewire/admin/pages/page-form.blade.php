<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <x-admin.card title="Conteúdo da Página">
                    <div class="space-y-4">
                        <x-admin.input label="Título *" wire:model.live="title" placeholder="Sobre nós" :error="$errors->first('title')"/>
                        <x-admin.input label="Slug (URL)" wire:model="slug" placeholder="sobre-nos" hint="Gerado automaticamente a partir do título"/>
                    </div>
                </x-admin.card>

                <x-admin.card title="SEO">
                    <div class="space-y-4">
                        <x-admin.input label="Meta Título" wire:model="meta_title" placeholder="Sobre nós | Feira Esquerda Livre"/>
                        <x-admin.textarea label="Meta Descrição" wire:model="meta_description" placeholder="Descrição para motores de busca..." :rows="3"/>
                    </div>
                </x-admin.card>
            </div>

            <div class="space-y-6">
                <x-admin.card title="Configurações">
                    <div class="space-y-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="is_homepage" class="w-4 h-4 text-[#1a472a] rounded border-gray-300 focus:ring-[#52b788]">
                            <span class="text-sm font-medium text-gray-700">Definir como Homepage</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="w-4 h-4 text-[#1a472a] rounded border-gray-300 focus:ring-[#52b788]">
                            <span class="text-sm font-medium text-gray-700">Página ativa</span>
                        </label>
                    </div>
                </x-admin.card>

                <div class="flex flex-col gap-3">
                    <x-admin.button type="submit" class="w-full justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ $page ? 'Atualizar Página' : 'Criar Página' }}
                    </x-admin.button>
                    <a href="{{ route('admin.pages.index') }}">
                        <x-admin.button variant="secondary" class="w-full justify-center">Cancelar</x-admin.button>
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
