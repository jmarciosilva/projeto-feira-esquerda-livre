<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <x-admin.card title="Conteúdo">
                    <div class="space-y-4">
                        <x-admin.input label="Título *" wire:model.live="title" :error="$errors->first('title')"/>
                        <x-admin.input label="Slug" wire:model="slug" hint="Gerado automaticamente"/>
                        <x-admin.textarea label="Resumo" wire:model="excerpt" placeholder="Breve descrição do post..." :rows="3"/>
                        <x-admin.textarea label="Conteúdo" wire:model="content" :rows="12"/>
                    </div>
                </x-admin.card>

                <x-admin.card title="SEO">
                    <div class="space-y-4">
                        <x-admin.input label="Meta Título" wire:model="meta_title"/>
                        <x-admin.textarea label="Meta Descrição" wire:model="meta_description" :rows="3"/>
                    </div>
                </x-admin.card>
            </div>

            <div class="space-y-6">
                <x-admin.card title="Configurações">
                    <div class="space-y-4">
                        <x-admin.select label="Tipo" wire:model="type">
                            @foreach($types as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </x-admin.select>

                        <x-admin.select label="Status" wire:model="status">
                            @foreach($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                            @endforeach
                        </x-admin.select>

                        <x-admin.select label="Categoria" wire:model="category_id">
                            <option value="0">Sem categoria</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </x-admin.select>

                        <x-admin.input label="Publicar em" wire:model="published_at" type="datetime-local"/>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="w-4 h-4 text-[#1a472a] rounded border-gray-300">
                            <span class="text-sm font-medium text-gray-700">Post ativo</span>
                        </label>
                    </div>
                </x-admin.card>

                <x-admin.card title="Imagem de Capa">
                    @if($image_path)
                    <img src="{{ Storage::url($image_path) }}" alt="Capa" class="w-full h-32 object-cover rounded-lg mb-3">
                    @endif
                    <input type="file" wire:model="image_upload" accept="image/*"
                           class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#f0fdf4] file:text-[#1a472a]">
                    @error('image_upload')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </x-admin.card>

                <div class="flex flex-col gap-3">
                    <x-admin.button type="submit" class="w-full justify-center">
                        {{ $post ? 'Atualizar Post' : 'Criar Post' }}
                    </x-admin.button>
                    <a href="{{ route('admin.posts.index') }}">
                        <x-admin.button variant="secondary" class="w-full justify-center">Cancelar</x-admin.button>
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
