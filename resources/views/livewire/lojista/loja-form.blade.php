<div>
    @if(session('success'))
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-sm font-medium">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
        {{ session('error') }}
    </div>
    @endif

    <form wire:submit="save" class="space-y-6">

        {{-- Nome da loja (somente leitura) --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Identidade da Loja</h3>
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome da Loja</label>
                    <div class="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200 text-gray-700 text-sm">
                        {{ $expositor?->name ?? '—' }}
                    </div>
                    <p class="mt-1 text-xs text-gray-400">O nome da loja é definido pela administração. Entre em contato para alterá-lo.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Slug (URL da loja)</label>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-400 whitespace-nowrap">feiraesquerdalivre.com.br/loja/</span>
                        <input wire:model="slug" type="text"
                               class="flex-1 min-w-0 px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#E8A000] focus:border-transparent"
                               placeholder="minha-loja">
                    </div>
                    @error('slug')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descrição da Loja</label>
                    <textarea wire:model="description" rows="5"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000] focus:border-transparent resize-none"
                              placeholder="Fale sobre sua loja, seus produtos, sua história..."></textarea>
                    @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Eixos de Atuação --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-1">Eixos de Atuação</h3>
            <p class="text-sm text-gray-500 mb-4">Em quais pilares da feira sua loja atua. Você pode marcar mais de um.</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach(\App\Enums\ItemType::cases() as $type)
                <label class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all"
                       style="border-color: {{ in_array($type->value, $eixos) ? '#E8A000' : '#e5e7eb' }}; background: {{ in_array($type->value, $eixos) ? '#FFFBEB' : '#fff' }};">
                    <input type="checkbox" wire:model="eixos" value="{{ $type->value }}" class="w-4 h-4 text-[#E8A000] rounded border-gray-300">
                    <span class="text-sm font-semibold" style="color:#3D3000;">{{ $type->emoji() }} {{ $type->label() }}</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Imagens --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Imagens</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Logotipo</label>
                    @if($logo_path)
                    <img src="{{ Storage::url($logo_path) }}" alt="Logo" class="w-24 h-24 rounded-full object-cover border-4 border-[#F4E294] mb-3">
                    @else
                    <div class="w-24 h-24 rounded-full flex items-center justify-center mb-3 border-4 border-dashed border-gray-300 text-gray-300">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    @endif
                    <input type="file" wire:model="logo_upload" accept="image/*"
                           class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:cursor-pointer" style="--tw-file-bg:#FDF8DC;">
                    <p class="mt-1 text-xs text-gray-400">400×400px · JPG/PNG/WebP · Máx 2MB</p>
                    @error('logo_upload')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Banner da Loja</label>
                    @if($image_path)
                    <img src="{{ Storage::url($image_path) }}" alt="Banner" class="w-full h-24 rounded-lg object-cover border border-gray-200 mb-3">
                    @else
                    <div class="w-full h-24 rounded-lg flex items-center justify-center mb-3 border-2 border-dashed border-gray-300 text-gray-300">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01"/></svg>
                    </div>
                    @endif
                    <input type="file" wire:model="banner_upload" accept="image/*"
                           class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:cursor-pointer">
                    <p class="mt-1 text-xs text-gray-400">1200×400px · JPG/PNG/WebP · Máx 4MB</p>
                    @error('banner_upload')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Contato e Localização --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Contato e Localização</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">WhatsApp</label>
                    <input wire:model="whatsapp" type="tel"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000] focus:border-transparent"
                           placeholder="(11) 9 9999-9999">
                    @error('whatsapp')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Instagram</label>
                    <input wire:model="instagram_url" type="url"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000] focus:border-transparent"
                           placeholder="https://instagram.com/sualore">
                    @error('instagram_url')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Facebook</label>
                    <input wire:model="facebook_url" type="url"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000] focus:border-transparent"
                           placeholder="https://facebook.com/sualore">
                    @error('facebook_url')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Site (opcional)</label>
                    <input wire:model="website_url" type="url"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000] focus:border-transparent"
                           placeholder="https://seusite.com.br">
                    @error('website_url')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cidade</label>
                        <input wire:model="city" type="text"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000] focus:border-transparent"
                               placeholder="São Paulo">
                        @error('city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                        <select wire:model="state"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000] focus:border-transparent">
                            <option value="">UF</option>
                            @foreach($brazilStates as $uf)
                            <option value="{{ $uf }}">{{ $uf }}</option>
                            @endforeach
                        </select>
                        @error('state')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Dados Bancários e PIX --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white flex-shrink-0"
                     style="background:#E8A000;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Dados Bancários e PIX</h3>
                    <p class="text-xs text-gray-400">Necessários para o processamento de splits de pagamento</p>
                </div>
            </div>

            {{-- Status PIX --}}
            <div class="flex items-center gap-2 px-3 py-2 rounded-lg mb-5 text-xs font-semibold"
                 style="{{ $pix_chave ? 'background:#f0fdf4; color:#15803d;' : 'background:#fefce8; color:#92400e;' }}">
                @if($pix_chave)
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                PIX cadastrado — split de pagamento habilitado
                @else
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Chave PIX não informada — cadastre para receber pagamentos
                @endif
            </div>

            {{-- PIX --}}
            <div class="p-4 rounded-xl border-2 mb-5" style="border-color:#F4E294; background:#FEFCE8;">
                <p class="text-xs font-bold mb-3" style="color:#92400E;">Chave PIX</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo da Chave</label>
                        <select wire:model="pix_tipo"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#E8A000] focus:border-transparent">
                            @foreach($tiposPix as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('pix_tipo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Chave PIX</label>
                        <input wire:model="pix_chave" type="text"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000] focus:border-transparent"
                               placeholder="Ex.: 11999887766 / email@exemplo.com">
                        @error('pix_chave')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- Dados bancários --}}
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Conta Bancária (opcional)</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Banco</label>
                        <input wire:model="banco_nome" type="text"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000] focus:border-transparent"
                               placeholder="Ex.: Nubank, Itaú, Bradesco, Caixa Econômica...">
                        @error('banco_nome')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Agência</label>
                        <input wire:model="banco_agencia" type="text"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000] focus:border-transparent"
                               placeholder="0001">
                        @error('banco_agencia')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Número da Conta</label>
                        <input wire:model="banco_conta" type="text"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000] focus:border-transparent"
                               placeholder="12345-6">
                        @error('banco_conta')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Conta</label>
                        <select wire:model="banco_tipo_conta"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-[#E8A000] focus:border-transparent">
                            @foreach($tiposContaBanco as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('banco_tipo_conta')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Botão salvar --}}
        <button type="submit"
                class="w-full py-4 rounded-xl font-bold text-lg text-[#3D3000] transition-colors hover:opacity-90"
                style="background:#F4E294; min-height:56px;"
                wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Salvar Alterações</span>
            <span wire:loading wire:target="save">Salvando...</span>
        </button>
    </form>
</div>
