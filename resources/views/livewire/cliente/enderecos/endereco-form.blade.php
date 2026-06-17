@php
    $inputClass = fn (string $field) => 'w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#E8A000] '
        . ($errors->has($field) ? 'border-red-400 bg-red-50' : 'border-gray-300');
@endphp

<div class="max-w-2xl">
    <div class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Identificação (ex: Casa, Praia, Trabalho)</label>
            <input type="text" wire:model="label" placeholder="Casa" class="{{ $inputClass('label') }}">
            @error('label') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                <input type="text" wire:model="cep" placeholder="00000-000" class="{{ $inputClass('cep') }}">
                @error('cep') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Rua</label>
                <input type="text" wire:model="rua" class="{{ $inputClass('rua') }}">
                @error('rua') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                <input type="text" wire:model="numero" class="{{ $inputClass('numero') }}">
                @error('numero') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Complemento (opcional)</label>
                <input type="text" wire:model="complemento" class="{{ $inputClass('complemento') }}">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                <input type="text" wire:model="bairro" class="{{ $inputClass('bairro') }}">
                @error('bairro') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                <input type="text" wire:model="cidade" class="{{ $inputClass('cidade') }}">
                @error('cidade') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">UF</label>
                <input type="text" wire:model="estado" maxlength="2" placeholder="SP" class="{{ $inputClass('estado') }} uppercase">
                @error('estado') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" wire:model="is_default" class="w-4 h-4 text-[#E8A000] rounded border-gray-300">
            <span class="text-sm font-medium text-gray-700">Usar como endereço padrão</span>
        </label>

        <div class="flex gap-3 pt-2">
            <button wire:click="save" class="px-6 py-3 rounded-xl font-bold text-base text-white" style="background-color:#E8A000; min-height:52px;">
                Salvar Endereço
            </button>
            <a href="{{ route('cliente.enderecos.index') }}" class="px-6 py-3 rounded-xl font-semibold text-gray-500" style="min-height:52px; display:inline-flex; align-items:center;">
                Cancelar
            </a>
        </div>
    </div>
</div>
