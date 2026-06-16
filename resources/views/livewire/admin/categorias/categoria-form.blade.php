<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="mb-4">
        <a href="{{ route('admin.categorias.index') }}" class="text-sm text-[#52b788] hover:text-[#1a472a] flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Voltar para Categorias
        </a>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <x-admin.card title="Informações da Categoria">
                    <div class="space-y-4">
                        <x-admin.input label="Nome *" wire:model.live="name" :error="$errors->first('name')"/>
                        <x-admin.input label="Slug" wire:model="slug" hint="Gerado automaticamente"/>
                        <x-admin.textarea label="Descrição" wire:model="description" :rows="4"/>
                    </div>
                </x-admin.card>
            </div>

            <div class="space-y-6">
                <x-admin.card title="Eixo">
                    <p class="text-xs text-gray-500 mb-3">Define em qual catálogo (Produtos, Serviços ou Cuidados) esta categoria aparece como filtro. Deixe em branco para aparecer em todos.</p>
                    <x-admin.select wire:model="eixo" :error="$errors->first('eixo')">
                        <option value="">Todos os eixos</option>
                        @foreach($itemTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->emoji() }} {{ $type->label() }}</option>
                        @endforeach
                    </x-admin.select>
                </x-admin.card>

                <x-admin.card title="Categoria-pai">
                    <x-admin.select wire:model="parent_id" :error="$errors->first('parent_id')">
                        <option value="">Nenhuma (categoria principal)</option>
                        @foreach($parentOptions as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                        @endforeach
                    </x-admin.select>
                </x-admin.card>

                <x-admin.card title="Status">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="w-4 h-4 text-[#1a472a] rounded border-gray-300">
                        <span class="text-sm font-medium text-gray-700">Categoria ativa</span>
                    </label>
                </x-admin.card>

                <div class="flex flex-col gap-3">
                    <x-admin.button type="submit" class="w-full justify-center">
                        {{ $categoria ? 'Atualizar Categoria' : 'Criar Categoria' }}
                    </x-admin.button>
                    <a href="{{ route('admin.categorias.index') }}">
                        <x-admin.button variant="secondary" class="w-full justify-center">Cancelar</x-admin.button>
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
