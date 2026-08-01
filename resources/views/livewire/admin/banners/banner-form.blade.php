<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <x-admin.card title="Informações">
                    <div class="space-y-4">
                        <x-admin.input label="Título *" wire:model="title" :error="$errors->first('title')"/>
                        <x-admin.input label="Subtítulo" wire:model="subtitle"/>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-admin.input label="Texto do Botão" wire:model="button_text" placeholder="Saiba mais"/>
                            <x-admin.select label="Destino do Botão" wire:model.live="button_link_preset">
                                <option value="">Sem link</option>
                                @foreach($buttonLinkOptions as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                                <option value="custom">Link personalizado</option>
                            </x-admin.select>
                            <div class="md:col-span-2">
                                <x-admin.input
                                    label="Link do Botão"
                                    wire:model.live="button_link"
                                    type="text"
                                    placeholder="/agenda ou https://exemplo.com"
                                    :error="$errors->first('button_link')"
                                    hint="Use as opções acima para caminhos internos. Caminhos como /agenda funcionam no ambiente local e em produção."
                                />
                            </div>
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.card title="Imagens">
                    <div class="space-y-4">
                        @if($image_path)
                        <div class="rounded-lg overflow-hidden border border-gray-200">
                            <img src="{{ Storage::url($image_path) }}" alt="Banner" class="w-full h-40 object-cover">
                        </div>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Imagem Principal {{ !$banner ? '*' : '' }}</label>
                            <input type="file" wire:model="image_upload" accept="image/*" class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#f0fdf4] file:text-[#1a472a]">
                            @error('image_upload')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Imagem Mobile</label>
                            <input type="file" wire:model="mobile_image_upload" accept="image/*" class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#f0fdf4] file:text-[#1a472a]">
                        </div>
                    </div>
                </x-admin.card>
            </div>

            <div class="space-y-6">
                <x-admin.card title="Configurações">
                    <div class="space-y-4">
                        <x-admin.input label="Ordem de Exibição" wire:model="sort_order" type="number" min="0"/>
                        <x-admin.input label="Data Início" wire:model="start_date" type="date" :error="$errors->first('start_date')"/>
                        <x-admin.input label="Data Fim" wire:model="end_date" type="date" :error="$errors->first('end_date')"/>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="w-4 h-4 text-[#1a472a] rounded border-gray-300 focus:ring-[#52b788]">
                            <span class="text-sm font-medium text-gray-700">Banner ativo</span>
                        </label>
                    </div>
                </x-admin.card>

                <div class="flex flex-col gap-3">
                    <x-admin.button type="submit" class="w-full justify-center">
                        {{ $banner ? 'Atualizar Banner' : 'Criar Banner' }}
                    </x-admin.button>
                    <a href="{{ route('admin.banners.index') }}">
                        <x-admin.button variant="secondary" class="w-full justify-center">Cancelar</x-admin.button>
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
