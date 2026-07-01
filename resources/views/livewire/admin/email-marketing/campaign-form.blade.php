<div x-data="{ tab: 'editor', showPreview: false }">
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('admin.email-marketing.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Campanhas</a>
        <span class="text-gray-300">/</span>
        <span class="text-sm font-semibold text-gray-700">{{ $campaign ? 'Editar: ' . $campaign->name : 'Nova Campanha' }}</span>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Coluna principal --}}
        <div class="xl:col-span-2 space-y-5">

            {{-- Identificação --}}
            <x-admin.card>
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Identificação</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Nome interno <span class="text-red-500">*</span></label>
                        <input wire:model="name" type="text" placeholder="Ex.: Newsletter Julho 2026"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Assunto do e-mail <span class="text-red-500">*</span></label>
                        <input wire:model="subject" type="text" placeholder="O que aparecerá na caixa de entrada do destinatário"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
                        @error('subject') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Nome do remetente</label>
                            <input wire:model="fromName" type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">E-mail remetente</label>
                            <input wire:model="fromEmail" type="email"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
                        </div>
                    </div>
                </div>
            </x-admin.card>

            {{-- Templates --}}
            <x-admin.card>
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Templates</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    @foreach($templates as $key => $tpl)
                    <button wire:click="applyTemplate('{{ $key }}')"
                            type="button"
                            class="p-3 rounded-xl border-2 text-left transition-colors text-xs font-semibold
                                {{ $templateKey === $key ? 'border-[#1a472a] bg-green-50 text-[#1a472a]' : 'border-gray-200 hover:border-gray-300 text-gray-600' }}">
                        {{ $tpl['label'] }}
                    </button>
                    @endforeach
                </div>
                <p class="text-xs text-gray-400 mt-2">Selecionar um template substitui o conteúdo atual.</p>
            </x-admin.card>

            {{-- Editor de conteúdo --}}
            <x-admin.card>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-700">Conteúdo do e-mail</h3>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="showPreview = !showPreview"
                                class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-gray-300 hover:bg-gray-50 text-gray-600">
                            <span x-text="showPreview ? 'Fechar preview' : 'Visualizar'"></span>
                        </button>
                    </div>
                </div>

                <p class="text-xs text-gray-400 mb-3">
                    Escreva HTML. Variáveis disponíveis: <code class="bg-gray-100 px-1 rounded">&#123;&#123;nome&#125;&#125;</code> e <code class="bg-gray-100 px-1 rounded">&#123;&#123;email&#125;&#125;</code>.
                    O link de descadastro é adicionado automaticamente no rodapé.
                </p>

                <textarea wire:model="bodyHtml" rows="16"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#52b788]"
                          placeholder="<h2>Olá, @{{ nome }}!</h2><p>Seu conteúdo aqui...</p>"></textarea>
                @error('bodyHtml') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

                {{-- Preview --}}
                <div x-show="showPreview" x-cloak class="mt-4 border border-gray-200 rounded-xl overflow-hidden">
                    <div class="bg-gray-100 px-4 py-2 text-xs font-semibold text-gray-500 flex items-center justify-between">
                        <span>Preview</span>
                        <span class="text-gray-400">Rendering aproximado — o e-mail real pode variar por cliente de e-mail</span>
                    </div>
                    <div class="bg-white p-6 max-h-96 overflow-y-auto text-sm prose max-w-none">
                        <div x-html="$wire.bodyHtml"></div>
                    </div>
                </div>
            </x-admin.card>

        </div>

        {{-- Coluna lateral --}}
        <div class="space-y-5">

            {{-- Destinatários --}}
            <x-admin.card>
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Destinatários</h3>
                <div class="space-y-3">
                    @foreach($recipientTypes as $type)
                    <label class="flex items-start gap-3 cursor-pointer p-2 rounded-lg hover:bg-gray-50">
                        <input type="radio" wire:model.live="recipientType" value="{{ $type->value }}"
                               class="mt-0.5 accent-[#1a472a]">
                        <span class="text-sm text-gray-700">{{ $type->label() }}</span>
                    </label>
                    @endforeach
                </div>

                @if($recipientType === 'segment_manual')
                <div class="mt-3">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Um e-mail por linha</label>
                    <textarea wire:model.live.debounce.500ms="recipientEmailsManual" rows="6"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs font-mono focus:outline-none focus:ring-2 focus:ring-[#52b788]"
                              placeholder="email@exemplo.com&#10;outro@exemplo.com"></textarea>
                </div>
                @endif

                <div class="mt-3 p-3 bg-blue-50 border border-blue-100 rounded-lg">
                    <p class="text-xs font-semibold text-blue-800">
                        Estimativa: <strong>{{ number_format($estimatedRecipients) }}</strong> destinatários
                    </p>
                    <p class="text-xs text-blue-600 mt-0.5">Opt-outs e e-mails inválidos serão ignorados no envio.</p>
                </div>
            </x-admin.card>

            {{-- Agendamento --}}
            <x-admin.card>
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Agendamento</h3>
                <p class="text-xs text-gray-500 mb-3">Deixe em branco para salvar como rascunho sem data.</p>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Data e hora de envio</label>
                <input wire:model="scheduledAt" type="datetime-local"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
                @error('scheduledAt') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </x-admin.card>

            {{-- Ações --}}
            <x-admin.card>
                <div class="space-y-3">
                    <button wire:click="save" wire:loading.attr="disabled"
                            class="w-full py-2.5 rounded-xl font-bold text-sm"
                            style="background:#1a472a; color:#F4E294;">
                        <span wire:loading.remove wire:target="save">
                            {{ $scheduledAt ? 'Salvar e agendar' : 'Salvar rascunho' }}
                        </span>
                        <span wire:loading wire:target="save">Salvando…</span>
                    </button>

                    <button wire:click="sendTestEmail" wire:loading.attr="disabled"
                            class="w-full py-2.5 rounded-xl font-semibold text-sm border-2 border-gray-300 text-gray-600 hover:border-gray-400">
                        <span wire:loading.remove wire:target="sendTestEmail">Enviar e-mail de teste</span>
                        <span wire:loading wire:target="sendTestEmail">Enviando…</span>
                    </button>

                    <a href="{{ route('admin.email-marketing.index') }}"
                       class="block text-center text-sm text-gray-500 hover:text-gray-700 py-1">Cancelar</a>
                </div>
            </x-admin.card>

        </div>
    </div>
</div>
