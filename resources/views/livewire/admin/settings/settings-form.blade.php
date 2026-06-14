<div>
    @if($saved)
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
         class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        Configurações salvas com sucesso!
    </div>
    @endif

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Coluna Principal --}}
            <div class="lg:col-span-2 space-y-6">

                <x-admin.card title="Informações Gerais">
                    <div class="space-y-4">
                        <x-admin.input
                            label="Nome do Site *"
                            wire:model="site_name"
                            placeholder="Feira Esquerda Livre"
                            :error="$errors->first('site_name')"
                        />
                        <x-admin.textarea
                            label="Descrição do Site"
                            wire:model="site_description"
                            placeholder="Breve descrição do site..."
                            :rows="3"
                        />
                        <x-admin.textarea
                            label="Texto do Rodapé"
                            wire:model="footer_text"
                            placeholder="© 2026 Feira Esquerda Livre..."
                            :rows="2"
                        />
                        <x-admin.textarea
                            label="Endereço"
                            wire:model="address"
                            placeholder="Avenida das Flores..."
                            :rows="2"
                        />
                    </div>
                </x-admin.card>

                <x-admin.card title="Seção Quem Somos (Home)">
                    <p class="text-xs text-gray-500 mb-4">Personalize a seção "Sobre" exibida na página inicial. Deixe em branco para usar o conteúdo padrão.</p>
                    <div class="space-y-4">
                        <x-admin.input
                            label="Título da seção"
                            wire:model="sobre_titulo"
                            placeholder="Quem Somos"
                        />
                        <x-admin.textarea
                            label="Texto (use linha em branco para separar parágrafos)"
                            wire:model="sobre_texto"
                            placeholder="A Feira Esquerda Livre é um movimento cultural..."
                            :rows="6"
                        />
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Imagem da seção</label>
                            @if($sobre_imagem_path)
                            <div class="rounded-lg overflow-hidden border border-gray-200 mb-2">
                                <img src="{{ Storage::url($sobre_imagem_path) }}" alt="Imagem Quem Somos" class="w-full h-32 object-cover">
                            </div>
                            @endif
                            <input type="file" wire:model="sobre_imagem_upload" accept="image/*"
                                   class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#f0fdf4] file:text-[#1a472a] hover:file:bg-[#dcfce7]">
                            <p class="mt-1 text-xs text-gray-400">Recomendado: 600×450px. Máx: 4 MB.</p>
                            @error('sobre_imagem_upload')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.card title="Cores do Site">
                    <p class="text-xs text-gray-500 mb-5">Personalize a paleta de cores exibida em todas as páginas públicas do site. As cores são aplicadas em tempo real após salvar.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Cor Principal</label>
                            <p class="text-xs text-gray-400 mb-2">Botões, destaques e badges</p>
                            <div class="flex items-center gap-3">
                                <input type="color" wire:model.live="color_primary"
                                       class="w-12 h-12 rounded-xl border border-gray-200 cursor-pointer p-0.5"
                                       style="min-height: 48px;">
                                <div class="flex-1">
                                    <input type="text" wire:model.live="color_primary"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#E8A000]"
                                           placeholder="#E8A000" maxlength="7">
                                </div>
                                <div class="w-10 h-10 rounded-lg border border-gray-200 flex-shrink-0"
                                     style="background-color: {{ $color_primary }};"></div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Cor Principal (hover)</label>
                            <p class="text-xs text-gray-400 mb-2">Versão escura para hover e links</p>
                            <div class="flex items-center gap-3">
                                <input type="color" wire:model.live="color_primary_dark"
                                       class="w-12 h-12 rounded-xl border border-gray-200 cursor-pointer p-0.5"
                                       style="min-height: 48px;">
                                <div class="flex-1">
                                    <input type="text" wire:model.live="color_primary_dark"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#E8A000]"
                                           placeholder="#C47A00" maxlength="7">
                                </div>
                                <div class="w-10 h-10 rounded-lg border border-gray-200 flex-shrink-0"
                                     style="background-color: {{ $color_primary_dark }};"></div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Cor Secundária</label>
                            <p class="text-xs text-gray-400 mb-2">Navbar, rodapé e fundos coloridos</p>
                            <div class="flex items-center gap-3">
                                <input type="color" wire:model.live="color_secondary"
                                       class="w-12 h-12 rounded-xl border border-gray-200 cursor-pointer p-0.5"
                                       style="min-height: 48px;">
                                <div class="flex-1">
                                    <input type="text" wire:model.live="color_secondary"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#E8A000]"
                                           placeholder="#F4E294" maxlength="7">
                                </div>
                                <div class="w-10 h-10 rounded-lg border border-gray-200 flex-shrink-0"
                                     style="background-color: {{ $color_secondary }};"></div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Cor de Fundo Claro</label>
                            <p class="text-xs text-gray-400 mb-2">Seções alternadas e fundos suaves</p>
                            <div class="flex items-center gap-3">
                                <input type="color" wire:model.live="color_secondary_light"
                                       class="w-12 h-12 rounded-xl border border-gray-200 cursor-pointer p-0.5"
                                       style="min-height: 48px;">
                                <div class="flex-1">
                                    <input type="text" wire:model.live="color_secondary_light"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#E8A000]"
                                           placeholder="#FDF8DC" maxlength="7">
                                </div>
                                <div class="w-10 h-10 rounded-lg border border-gray-200 flex-shrink-0"
                                     style="background-color: {{ $color_secondary_light }};"></div>
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Cor Escura (texto e contrastes)</label>
                            <p class="text-xs text-gray-400 mb-2">Títulos, sidebar do painel do lojista e elementos de alto contraste</p>
                            <div class="flex items-center gap-3 max-w-sm">
                                <input type="color" wire:model.live="color_dark"
                                       class="w-12 h-12 rounded-xl border border-gray-200 cursor-pointer p-0.5"
                                       style="min-height: 48px;">
                                <div class="flex-1">
                                    <input type="text" wire:model.live="color_dark"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#E8A000]"
                                           placeholder="#3D3000" maxlength="7">
                                </div>
                                <div class="w-10 h-10 rounded-lg border border-gray-200 flex-shrink-0"
                                     style="background-color: {{ $color_dark }};"></div>
                            </div>
                        </div>

                    </div>

                    {{-- Preview --}}
                    <div class="mt-5 p-4 rounded-xl border border-gray-200">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Pré-visualização</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="px-4 py-2 rounded-lg text-sm font-bold text-white" style="background-color: {{ $color_primary }};">Botão Principal</span>
                            <span class="px-4 py-2 rounded-lg text-sm font-bold border-2" style="border-color: {{ $color_primary }}; color: {{ $color_primary_dark }};">Botão Outline</span>
                            <span class="px-3 py-1.5 rounded-full text-xs font-bold" style="background-color: {{ $color_secondary }}; color: {{ $color_dark }};">Badge</span>
                            <div class="px-3 py-2 rounded-lg text-sm font-bold" style="background-color: {{ $color_dark }}; color: {{ $color_secondary }};">Navbar</div>
                            <div class="w-8 h-8 rounded-lg" style="background-color: {{ $color_secondary_light }}; border: 1px solid {{ $color_secondary }};"></div>
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.card title="Contrato para Expositores">
                    <p class="text-xs text-gray-500 mb-3">Este texto será exibido na página "Seja um Expositor" antes do formulário. O candidato deverá marcar que leu e concorda antes de enviar a solicitação.</p>
                    <textarea wire:model="contrato_expositor" rows="12"
                              class="w-full px-3 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#E8A000] font-mono resize-y"
                              placeholder="Digite aqui o contrato ou termos de uso para expositores...&#10;&#10;Ex.: CONTRATO DE EXPOSIÇÃO&#10;&#10;1. O expositor declara..."></textarea>
                    <p class="mt-1 text-xs text-gray-400">Suporta texto simples e quebras de linha. Deixe em branco para omitir o contrato da página pública.</p>
                </x-admin.card>

            <x-admin.card title="Redes Sociais e Contato">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-admin.input label="WhatsApp" wire:model="whatsapp" placeholder="(11) 99999-9999"/>
                        <x-admin.input label="E-mail" wire:model="email" type="email" placeholder="contato@site.com" :error="$errors->first('email')"/>
                        <x-admin.input label="Instagram URL" wire:model="instagram_url" placeholder="https://instagram.com/..."/>
                        <x-admin.input label="Facebook URL" wire:model="facebook_url" placeholder="https://facebook.com/..."/>
                        <x-admin.input label="YouTube URL" wire:model="youtube_url" placeholder="https://youtube.com/..."/>
                    </div>
                </x-admin.card>
            </div>

            {{-- Coluna Lateral --}}
            <div class="space-y-6">
                <x-admin.card title="Logo e Favicon">
                    <div class="space-y-4">
                        @if($logo_path)
                        <div class="rounded-lg overflow-hidden border border-gray-200">
                            <img src="{{ Storage::url($logo_path) }}" alt="Logo" class="w-full h-24 object-contain bg-gray-50 p-2">
                        </div>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                            <input type="file" wire:model="logo_upload" accept="image/*" class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#f0fdf4] file:text-[#1a472a] hover:file:bg-[#dcfce7]">
                            @error('logo_upload')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        @if($favicon_path)
                        <div class="flex items-center gap-2">
                            <img src="{{ Storage::url($favicon_path) }}" alt="Favicon" class="w-8 h-8 object-contain border border-gray-200 rounded">
                            <span class="text-xs text-gray-500">Favicon atual</span>
                        </div>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Favicon</label>
                            <input type="file" wire:model="favicon_upload" accept="image/*" class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#f0fdf4] file:text-[#1a472a] hover:file:bg-[#dcfce7]">
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.card title="Status do Site">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" wire:model="maintenance_mode" class="sr-only peer">
                            <div class="w-10 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-[#52b788] rounded-full peer peer-checked:bg-red-500 transition-colors"></div>
                            <div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform peer-checked:translate-x-4"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Modo Manutenção</span>
                    </label>
                    @if($maintenance_mode)
                    <p class="mt-2 text-xs text-red-600 font-medium">⚠ O site está em manutenção — visitantes verão uma página de aviso.</p>
                    @endif
                </x-admin.card>

                <x-admin.button type="submit" class="w-full justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Salvar Configurações
                </x-admin.button>
            </div>
        </div>
    </form>
</div>
