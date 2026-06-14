<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="mb-4">
        <a href="{{ route('admin.expositores.index') }}" class="text-sm text-[#52b788] hover:text-[#1a472a] flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Voltar para Expositores
        </a>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ============================================================ --}}
            {{-- Coluna Principal --}}
            {{-- ============================================================ --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Info do expositor (somente leitura) --}}
                <x-admin.card title="Informações do Expositor">
                    <div class="flex items-center gap-4">
                        @if($expositor->logo_path)
                        <img src="{{ Storage::url($expositor->logo_path) }}" alt="{{ $expositor->name }}"
                             class="w-16 h-16 rounded-full object-cover border-2 border-gray-200 flex-shrink-0">
                        @else
                        <div class="w-16 h-16 rounded-full bg-[#f0fdf4] flex items-center justify-center flex-shrink-0 text-[#52b788] font-bold text-xl">
                            {{ strtoupper(mb_substr($expositor->name, 0, 1)) }}
                        </div>
                        @endif
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ $expositor->name }}</h3>
                            @if($expositor->city || $expositor->state)
                            <p class="text-sm text-gray-500">{{ $expositor->city }}@if($expositor->city && $expositor->state), @endif{{ $expositor->state }}</p>
                            @endif
                            @if($expositor->email)
                            <p class="text-xs text-gray-400 mt-1">{{ $expositor->email }}</p>
                            @endif
                        </div>
                    </div>

                    @if($expositor->instagram_url || $expositor->facebook_url)
                    <div class="flex gap-3 mt-4 pt-4 border-t border-gray-100">
                        @if($expositor->instagram_url)
                        <a href="{{ $expositor->instagram_url }}" target="_blank"
                           class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg"
                           style="background:#fce7f3; color:#9d174d;">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                            Instagram
                        </a>
                        @endif
                        @if($expositor->facebook_url)
                        <a href="{{ $expositor->facebook_url }}" target="_blank"
                           class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg"
                           style="background:#dbeafe; color:#1e40af;">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            Facebook
                        </a>
                        @endif
                    </div>
                    @endif

                    @if($expositor->description)
                    <p class="mt-4 text-sm text-gray-600 leading-relaxed">{{ Str::limit($expositor->description, 200) }}</p>
                    @endif
                </x-admin.card>

                {{-- Imagem de Capa --}}
                <x-admin.card title="Imagem de Capa (aparece na Home)">
                    <p class="text-sm text-gray-500 mb-3">
                        Exibida no card do expositor na página inicial — seção <strong>Expositores em Destaque</strong>.
                        Recomendado: <strong>600×400px</strong>.
                    </p>

                    @if($image_path)
                    <div class="mb-3 rounded-lg overflow-hidden border border-gray-200">
                        <img src="{{ Storage::url($image_path) }}" alt="Imagem de capa" class="w-full h-48 object-cover">
                    </div>
                    <button type="button" wire:click="removeImage"
                            wire:confirm="Remover a imagem de capa?"
                            class="mb-3 text-xs text-red-500 hover:text-red-700 font-medium">
                        × Remover imagem
                    </button>
                    @endif

                    <input type="file" wire:model="image_upload" accept="image/*"
                           class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#f0fdf4] file:text-[#1a472a] hover:file:bg-[#dcfce7]">
                    <p class="mt-1 text-xs text-gray-400">Formatos aceitos: JPG, PNG, WebP. Máx: 4 MB.</p>
                    @error('image_upload')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </x-admin.card>

                {{-- ======================================================== --}}
                {{-- Dados Bancários e PIX --}}
                {{-- ======================================================== --}}
                <x-admin.card title="Dados Bancários e PIX">
                    <p class="text-sm text-gray-500 mb-5">
                        Informações necessárias para processamento de splits de pagamento. Preencha a chave PIX
                        preferencialmente — os dados bancários são opcionais quando o PIX está informado.
                    </p>

                    {{-- Seção PIX --}}
                    <div class="p-4 rounded-xl border-2 mb-6" style="border-color:#F4E294; background:#FEFCE8;">
                        <div class="flex items-center gap-2 mb-4">
                            <svg class="w-4 h-4" fill="none" stroke="#92400E" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                            </svg>
                            <span class="text-sm font-bold" style="color:#92400E;">Chave PIX</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo da Chave</label>
                                <select wire:model="pix_tipo"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
                                    @foreach($tiposPix as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('pix_tipo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Chave PIX</label>
                                <input type="text" wire:model="pix_chave"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                       placeholder="Ex.: 11999887766 / email@exemplo.com">
                                @error('pix_chave')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- Dados Bancários --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Conta Bancária</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Banco</label>
                                <input type="text" wire:model="banco_nome"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]"
                                       placeholder="Ex.: Nubank, Itaú, Bradesco, Caixa Econômica...">
                                @error('banco_nome')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Agência</label>
                                <input type="text" wire:model="banco_agencia"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]"
                                       placeholder="0001">
                                @error('banco_agencia')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Número da Conta</label>
                                <input type="text" wire:model="banco_conta"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]"
                                       placeholder="12345-6">
                                @error('banco_conta')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Conta</label>
                                <select wire:model="banco_tipo_conta"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
                                    @foreach($tiposContaBanco as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('banco_tipo_conta')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </x-admin.card>

            </div>

            {{-- ============================================================ --}}
            {{-- Coluna Lateral --}}
            {{-- ============================================================ --}}
            <div class="space-y-6">

                <x-admin.card title="Destaque e Ordenação">
                    <div class="space-y-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="is_featured" class="w-4 h-4 text-[#E8A000] rounded border-gray-300">
                            <div>
                                <span class="text-sm font-medium text-gray-700">Destacar na Home</span>
                                <p class="text-xs text-gray-400">Aparece na seção "Expositores em Destaque"</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="w-4 h-4 text-[#1a472a] rounded border-gray-300">
                            <span class="text-sm font-medium text-gray-700">Expositor ativo</span>
                        </label>
                        <x-admin.input
                            label="Ordem de exibição"
                            wire:model="sort_order"
                            type="number"
                            min="0"
                            placeholder="0"
                            hint="Menor número = aparece primeiro"
                            :error="$errors->first('sort_order')"
                        />
                    </div>
                </x-admin.card>

                {{-- Indicador de status PIX --}}
                <div class="p-4 rounded-xl border"
                     style="{{ $pix_chave ? 'background:#f0fdf4; border-color:#86efac;' : 'background:#fefce8; border-color:#fde047;' }}">
                    <div class="flex items-center gap-2 mb-1">
                        @if($pix_chave)
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-xs font-bold text-green-700">PIX cadastrado</span>
                        @else
                        <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span class="text-xs font-bold text-yellow-700">PIX não cadastrado</span>
                        @endif
                    </div>
                    <p class="text-xs {{ $pix_chave ? 'text-green-600' : 'text-yellow-600' }}">
                        {{ $pix_chave ? 'Split de pagamento habilitado.' : 'Necessário para splits de pagamento.' }}
                    </p>
                </div>

                <x-admin.button type="submit" class="w-full justify-center">
                    Salvar Alterações
                </x-admin.button>
            </div>
        </div>
    </form>
</div>
