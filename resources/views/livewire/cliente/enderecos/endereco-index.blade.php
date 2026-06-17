<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-base font-medium">{{ session('success') }}</div>
    @endif

    <div class="flex justify-end mb-4">
        <a href="{{ route('cliente.enderecos.create') }}">
            <button class="px-6 py-3 rounded-xl font-bold text-base text-white" style="background-color:#E8A000; min-height:52px;">
                + Novo Endereço
            </button>
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @forelse($enderecos as $endereco)
        <div class="bg-white rounded-2xl border-2 p-5"
             style="{{ $endereco->is_default ? 'border-color:#E8A000;' : 'border-color:#e5e7eb;' }}">
            <div class="flex items-center justify-between mb-2">
                <p class="font-bold text-gray-900">{{ $endereco->label }}</p>
                @if($endereco->is_default)
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full" style="background:#FFFBEB; color:#C47A00;">Padrão</span>
                @endif
            </div>
            <p class="text-sm text-gray-600 mb-4">
                {{ $endereco->rua }}, {{ $endereco->numero }}
                @if($endereco->complemento) — {{ $endereco->complemento }} @endif
                <br>{{ $endereco->bairro }} — {{ $endereco->cidade }}/{{ $endereco->estado }} · CEP {{ $endereco->cep }}
            </p>
            <div class="flex items-center gap-3 flex-wrap">
                <a href="{{ route('cliente.enderecos.edit', $endereco) }}" class="text-sm font-semibold" style="color:#C47A00;">Editar</a>
                @if(! $endereco->is_default)
                <button wire:click="setDefault({{ $endereco->id }})" class="text-sm font-semibold text-gray-500">Tornar padrão</button>
                @endif
                <button wire:click="delete({{ $endereco->id }})" wire:confirm="Remover o endereço '{{ $endereco->label }}'?" class="text-sm font-semibold text-red-500">Excluir</button>
            </div>
        </div>
        @empty
        <div class="sm:col-span-2 bg-white rounded-2xl border border-gray-200 p-10 text-center">
            <div class="text-5xl mb-4">📍</div>
            <p class="text-lg font-semibold text-gray-500">Nenhum endereço cadastrado ainda.</p>
        </div>
        @endforelse
    </div>
</div>
