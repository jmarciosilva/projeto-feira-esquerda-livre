@php
    $inputClass = fn (string $field) => 'w-full px-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors '
        . ($errors->has($field) ? 'border-red-400' : 'border-gray-200 focus:border-yellow-400');
@endphp

<div>

{{-- Modal de autenticação para convidados --}}
@if($showAuthModal)
<div class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,0.55);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden"
         x-data="{ tab: '{{ old('_form', 'login') }}' }">

        <div class="px-8 py-6 text-center" style="background:#1a472a;">
            <h2 class="text-lg font-bold text-white">Para finalizar seu pedido</h2>
            <p class="text-sm mt-1" style="color:#b7e4c7;">Entre na sua conta ou crie uma nova em segundos</p>
        </div>

        {{-- Abas --}}
        <div class="flex" style="border-bottom:1px solid #e5e7eb;">
            <button type="button" @click="tab = 'login'"
                    class="flex-1 py-3 text-sm font-semibold transition-colors"
                    :style="tab === 'login' ? 'color:#C47A00; border-bottom:2px solid #E8A000;' : 'color:#6b7280;'">
                Já tenho conta
            </button>
            <button type="button" @click="tab = 'register'"
                    class="flex-1 py-3 text-sm font-semibold transition-colors"
                    :style="tab === 'register' ? 'color:#C47A00; border-bottom:2px solid #E8A000;' : 'color:#6b7280;'">
                Criar conta
            </button>
        </div>

        {{-- Erros --}}
        @if($errors->any())
        <div class="mx-6 mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Formulário de Login --}}
        <div x-show="tab === 'login'" class="px-6 py-5">
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="_form" value="login">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <input type="password" name="password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
                </div>
                <button type="submit"
                        class="w-full py-3 rounded-xl text-white text-sm font-bold" style="background:#1a472a;">
                    Entrar e continuar
                </button>
            </form>
        </div>

        {{-- Formulário de Cadastro --}}
        <div x-show="tab === 'register'" class="px-6 py-5">
            <form method="POST" action="{{ route('register') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="_form" value="register">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome completo</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp</label>
                    <input type="text" name="whatsapp" value="{{ old('whatsapp') }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400"
                           placeholder="(11) 91234-5678">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                        <input type="password" name="password" required placeholder="Mín. 8 caracteres"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
                    </div>
                </div>
                <button type="submit"
                        class="w-full py-3 rounded-xl text-white text-sm font-bold" style="background:#1a472a;">
                    Criar conta e continuar
                </button>
            </form>
        </div>

    </div>
</div>
@endif
    @if(session('error'))
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    @if($total <= 0)
    <div class="rounded-2xl border border-gray-200 p-10 text-center bg-white">
        <div class="text-5xl mb-4">🛒</div>
        <p class="text-lg font-semibold text-gray-700 mb-2">Seu carrinho está vazio</p>
        <p class="text-sm text-gray-400 mb-6">Adicione produtos antes de continuar para o checkout.</p>
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-white" style="background:#3D3000;">
            ← Voltar às lojas
        </a>
    </div>
    @else
    <form wire:submit="confirmar" class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Formulário --}}
        <div class="lg:col-span-2 space-y-6">

            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="font-bold text-base mb-4" style="color:#3D3000;">Seus dados</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome completo</label>
                        <input type="text" wire:model="customer_name" class="{{ $inputClass('customer_name') }}">
                        @error('customer_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp</label>
                            <input type="text" wire:model="customer_whatsapp" placeholder="(11) 91234-5678" class="{{ $inputClass('customer_whatsapp') }}">
                            @error('customer_whatsapp') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail (opcional)</label>
                            <input type="email" wire:model="customer_email" class="{{ $inputClass('customer_email') }}">
                            @error('customer_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="font-bold text-base mb-4" style="color:#3D3000;">Como você quer receber?</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <button type="button" wire:click="$set('delivery_type', 'retirada')"
                            class="p-4 rounded-xl border-2 text-left transition-colors"
                            style="{{ $delivery_type === 'retirada' ? 'border-color:#E8A000; background:#FFFBEB;' : 'border-color:#e5e7eb;' }}">
                        <span class="text-2xl">📦</span>
                        <p class="font-bold mt-2" style="color:#3D3000;">Retirar no local</p>
                        <p class="text-xs text-gray-500 mt-1">Sem necessidade de endereço — combine com o lojista pelo WhatsApp.</p>
                    </button>
                    <button type="button" wire:click="$set('delivery_type', 'entrega')"
                            class="p-4 rounded-xl border-2 text-left transition-colors"
                            style="{{ $delivery_type === 'entrega' ? 'border-color:#E8A000; background:#FFFBEB;' : 'border-color:#e5e7eb;' }}">
                        <span class="text-2xl">🚚</span>
                        <p class="font-bold mt-2" style="color:#3D3000;">Receber em casa</p>
                        <p class="text-xs text-gray-500 mt-1">Escolha um dos seus endereços salvos ou cadastre um novo.</p>
                    </button>
                </div>
            </div>

            @if($delivery_type === 'entrega')
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="font-bold text-base mb-4" style="color:#3D3000;">Endereço de entrega</h2>

                @error('customer_address_id') <p class="mb-3 text-sm text-red-600">{{ $message }}</p> @enderror

                @if($addresses->isNotEmpty())
                <div class="space-y-2 mb-4">
                    @foreach($addresses as $addr)
                    <label class="flex items-start gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors"
                           style="{{ $customer_address_id === $addr->id ? 'border-color:#E8A000; background:#FFFBEB;' : 'border-color:#e5e7eb;' }}">
                        <input type="radio" wire:model="customer_address_id" value="{{ $addr->id }}" class="mt-1 w-4 h-4">
                        <span>
                            <span class="font-semibold text-gray-900 block">{{ $addr->label }}{{ $addr->is_default ? ' (padrão)' : '' }}</span>
                            <span class="text-sm text-gray-500">{{ $addr->rua }}, {{ $addr->numero }} — {{ $addr->bairro }}, {{ $addr->cidade }}/{{ $addr->estado }}</span>
                        </span>
                    </label>
                    @endforeach
                </div>
                @endif

                @if(! $addingAddress)
                <button type="button" wire:click="startAddingAddress" class="text-sm font-semibold" style="color:#C47A00;">
                    + Adicionar novo endereço
                </button>
                @else
                <div class="space-y-4 mt-2 p-4 rounded-xl border border-gray-100" style="background:#FAFAFA;">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Identificação (ex: Casa, Praia, Trabalho)</label>
                        <input type="text" wire:model="new_label" placeholder="Casa" class="{{ $inputClass('new_label') }}">
                        @error('new_label') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                            <input type="text" wire:model="new_cep" placeholder="00000-000" class="{{ $inputClass('new_cep') }}">
                            @error('new_cep') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rua</label>
                            <input type="text" wire:model="new_rua" class="{{ $inputClass('new_rua') }}">
                            @error('new_rua') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                            <input type="text" wire:model="new_numero" class="{{ $inputClass('new_numero') }}">
                            @error('new_numero') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Complemento (opcional)</label>
                            <input type="text" wire:model="new_complemento" class="{{ $inputClass('new_complemento') }}">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                            <input type="text" wire:model="new_bairro" class="{{ $inputClass('new_bairro') }}">
                            @error('new_bairro') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                            <input type="text" wire:model="new_cidade" class="{{ $inputClass('new_cidade') }}">
                            @error('new_cidade') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">UF</label>
                            <input type="text" wire:model="new_estado" maxlength="2" placeholder="SP" class="{{ $inputClass('new_estado') }} uppercase">
                            @error('new_estado') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" wire:click="saveNewAddress"
                                class="px-5 py-2.5 rounded-xl text-white text-sm font-bold" style="background:#E8A000;">
                            Salvar endereço
                        </button>
                        <button type="button" wire:click="$set('addingAddress', false)"
                                class="px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-500">
                            Cancelar
                        </button>
                    </div>
                </div>
                @endif
            </div>
            @else
            <div class="p-4 rounded-xl text-sm" style="background:#FDF8DC; color:#5C4500;">
                📦 <strong>Retirada:</strong> combine o local e horário diretamente com o(s) lojista(s) pelo WhatsApp após a confirmação do pedido.
            </div>
            @endif
        </div>

        {{-- Resumo do pedido --}}
        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-gray-200 p-6 sticky top-24">
                <h2 class="font-bold text-base mb-4" style="color:#3D3000;">Resumo do pedido</h2>

                <div class="space-y-5 mb-5">
                    @foreach($grouped as $expositorId => $storeItems)
                    @php $firstItem = $storeItems->first(); @endphp
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">
                            {{ $firstItem->expositor?->name ?? 'Loja' }}
                        </p>
                        <div class="space-y-2">
                            @foreach($storeItems as $item)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ $item->quantity }}x {{ $item->product?->name }}</span>
                                <span class="font-semibold text-gray-800">R$ {{ number_format($item->subtotal(), 2, ',', '.') }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="border-t border-gray-100 pt-4 flex justify-between items-center">
                    <span class="font-semibold text-gray-700">Total</span>
                    <span class="text-xl font-bold" style="color:#3D3000;">R$ {{ number_format($total, 2, ',', '.') }}</span>
                </div>

                <button type="submit"
                        class="w-full mt-5 py-4 rounded-xl text-white text-base font-bold transition-colors"
                        style="background:#E8A000; min-height:56px;">
                    <span wire:loading.remove wire:target="confirmar">Confirmar Pedido</span>
                    <span wire:loading wire:target="confirmar">Enviando...</span>
                </button>
            </div>
        </div>
    </form>
    @endif
</div>
