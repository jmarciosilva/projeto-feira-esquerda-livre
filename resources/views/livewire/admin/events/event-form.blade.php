<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <x-admin.card title="Informações do Evento">
                    <div class="space-y-4">
                        <x-admin.input label="Título *" wire:model.live="title" :error="$errors->first('title')"/>
                        <x-admin.input label="Slug" wire:model="slug" hint="Gerado automaticamente"/>
                        <x-admin.textarea label="Descrição" wire:model="description" :rows="6"/>
                        <x-admin.input label="URL de Inscrição" wire:model="registration_url" type="url" placeholder="https://..." :error="$errors->first('registration_url')"/>
                    </div>
                </x-admin.card>

                <x-admin.card title="Local">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-admin.select label="Estado" wire:model="state">
                            <option value="">Selecione...</option>
                            @foreach($brazilStates as $uf)
                            <option value="{{ $uf }}">{{ $uf }}</option>
                            @endforeach
                        </x-admin.select>
                        <x-admin.input label="Cidade" wire:model="city"/>
                        <div class="sm:col-span-2">
                            <x-admin.textarea label="Endereço completo" wire:model="address" :rows="2"/>
                        </div>
                        <x-admin.input label="Latitude" wire:model="latitude" type="number" step="any" placeholder="-23.5505" :error="$errors->first('latitude')"/>
                        <x-admin.input label="Longitude" wire:model="longitude" type="number" step="any" placeholder="-46.6333" :error="$errors->first('longitude')"/>
                    </div>
                </x-admin.card>

                <x-admin.card title="Capacidade">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-admin.input label="Capacidade de Expositores" wire:model="capacidade_expositores" type="number" min="0" placeholder="Ex.: 50" :error="$errors->first('capacidade_expositores')"/>
                        <x-admin.input label="Vagas Restantes" wire:model="vagas_restantes" type="number" min="0" placeholder="Ex.: 30" :error="$errors->first('vagas_restantes')"/>
                    </div>
                </x-admin.card>
            </div>

            <div class="space-y-6">
                <x-admin.card title="Datas">
                    <div class="space-y-4">
                        <x-admin.input label="Data/Hora Início *" wire:model="start_date" type="datetime-local" :error="$errors->first('start_date')"/>
                        <x-admin.input label="Data/Hora Fim" wire:model="end_date" type="datetime-local" :error="$errors->first('end_date')"/>
                    </div>
                </x-admin.card>

                <x-admin.card title="Imagem do Evento (aparece na Home)">
                    @if($image_path)
                    <img src="{{ Storage::url($image_path) }}" alt="{{ $title }}" class="w-full h-32 object-cover rounded-lg mb-3">
                    @endif
                    <input type="file" wire:model="image_upload" accept="image/*"
                           class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#f0fdf4] file:text-[#1a472a]">
                    <p class="mt-1 text-xs text-gray-400">Recomendado: 600×400px. Aparece no card da home e na listagem de eventos.</p>
                    @error('image_upload')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </x-admin.card>

                <x-admin.card title="Banner da Página do Evento">
                    @if($banner_image_path)
                    <img src="{{ Storage::url($banner_image_path) }}" alt="Banner" class="w-full h-24 object-cover rounded-lg mb-3">
                    @endif
                    <input type="file" wire:model="banner_image_upload" accept="image/*"
                           class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#f0fdf4] file:text-[#1a472a]">
                    <p class="mt-1 text-xs text-gray-400">Recomendado: 1920×600px. Exibido na página pública.</p>
                    @error('banner_image_upload')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </x-admin.card>

                <x-admin.card title="Status e Destaque">
                    <div class="space-y-3">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="w-4 h-4 text-[#1a472a] rounded border-gray-300">
                            <span class="text-sm font-medium text-gray-700">Evento ativo</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="is_featured" class="w-4 h-4 text-[#E8A000] rounded border-gray-300">
                            <span class="text-sm font-medium text-gray-700">Destacar na Home</span>
                        </label>
                    </div>
                </x-admin.card>

                <div class="flex flex-col gap-3">
                    <x-admin.button type="submit" class="w-full justify-center">
                        {{ $event ? 'Atualizar Evento' : 'Criar Evento' }}
                    </x-admin.button>
                    <a href="{{ route('admin.events.index') }}">
                        <x-admin.button variant="secondary" class="w-full justify-center">Cancelar</x-admin.button>
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
