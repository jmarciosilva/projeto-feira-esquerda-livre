<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    @if($errors->has('approve'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ $errors->first('approve') }}</div>
    @endif

    {{-- Filtros --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por loja, responsável ou e-mail..."
               class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
        <select wire:model.live="filterStatus" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
            <option value="pendente">Pendentes</option>
            <option value="aprovado">Aprovadas</option>
            <option value="bloqueado">Bloqueadas</option>
            <option value="">Todas</option>
        </select>
    </div>

    <x-admin.card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Loja / Responsável</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Contato</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Data</th>
                        <th class="text-center py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="text-right py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($solicitacoes as $s)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-2">
                            <p class="font-medium text-gray-900">{{ $s->nome_loja }}</p>
                            <p class="text-xs text-gray-500">{{ $s->responsavel }} · CPF/CNPJ: {{ $s->cpf_cnpj }}</p>
                            @if($s->pix_chave)
                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full mt-1"
                                  style="background:#fef9c3; color:#713f12;">
                                PIX · {{ $s->pix_tipo ?? '' }}: {{ Str::limit($s->pix_chave, 22) }}
                            </span>
                            @elseif($s->banco_nome)
                            <p class="text-xs text-gray-400 mt-1">Banco: {{ $s->banco_nome }}</p>
                            @else
                            <p class="text-xs text-red-400 mt-1">⚠ Sem dados bancários</p>
                            @endif
                            @if($s->descricao)
                            <p class="text-xs text-gray-400 mt-1 max-w-xs truncate">{{ $s->descricao }}</p>
                            @endif
                        </td>
                        <td class="py-3 px-2">
                            <p class="text-gray-700">{{ $s->email }}</p>
                            <p class="text-xs text-gray-500">{{ $s->whatsapp }}</p>
                            @if($s->instagram_url)
                            <a href="{{ $s->instagram_url }}" target="_blank"
                               class="inline-flex items-center gap-1 text-xs text-pink-600 hover:underline mt-0.5">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                Instagram
                            </a>
                            @endif
                            @if($s->facebook_url)
                            <a href="{{ $s->facebook_url }}" target="_blank"
                               class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline mt-0.5 ml-2">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                Facebook
                            </a>
                            @endif
                        </td>
                        <td class="py-3 px-2 text-xs text-gray-500 whitespace-nowrap">
                            {{ $s->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="py-3 px-2 text-center">
                            @php
                            $colors = ['pendente' => 'yellow', 'aprovado' => 'green', 'bloqueado' => 'red'];
                            $color = $colors[$s->status->value] ?? 'gray';
                            @endphp
                            <x-admin.badge :color="$color">{{ $s->status->label() }}</x-admin.badge>
                            @if($s->status->value === 'bloqueado' && $s->motivo_bloqueio)
                            <p class="text-xs text-gray-400 mt-1 max-w-[140px] truncate" title="{{ $s->motivo_bloqueio }}">{{ $s->motivo_bloqueio }}</p>
                            @endif
                        </td>
                        <td class="py-3 px-2">
                            <div class="flex items-center justify-end gap-2">
                                @if($s->status->value === 'pendente')
                                <button wire:click="confirmApprove({{ $s->id }})"
                                        class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 font-medium transition-colors">
                                    Aprovar
                                </button>
                                <button wire:click="confirmBlock({{ $s->id }})"
                                        class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 font-medium transition-colors">
                                    Bloquear
                                </button>
                                @endif
                                @if($s->user_id)
                                <a href="#" class="text-gray-400 hover:text-[#1a472a]" title="Ver usuário">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-10 text-center text-gray-400">Nenhuma solicitação encontrada.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $solicitacoes->links() }}</div>
    </x-admin.card>

    {{-- Modal: Confirmar Aprovação --}}
    @if($approveId)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6">
            <h3 class="text-lg font-semibold mb-2">Confirmar Aprovação</h3>
            <p class="text-sm text-gray-600 mb-1">Isso criará:</p>
            <ul class="text-sm text-gray-700 mb-4 list-disc list-inside space-y-1">
                <li>Uma conta de <strong>lojista</strong> com o e-mail da solicitação</li>
                <li>Um perfil de loja vinculado à conta</li>
            </ul>
            <p class="text-xs text-amber-700 bg-amber-50 rounded-lg px-3 py-2 mb-5">
                O lojista precisará redefinir a senha pelo link "Esqueci minha senha" no login.
            </p>
            <div class="flex gap-3 justify-end">
                <x-admin.button variant="secondary" wire:click="cancelModals">Cancelar</x-admin.button>
                <x-admin.button wire:click="approve" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="approve">Aprovar Lojista</span>
                    <span wire:loading wire:target="approve">Aprovando...</span>
                </x-admin.button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal: Bloquear --}}
    @if($blockId)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6">
            <h3 class="text-lg font-semibold mb-4">Bloquear Solicitação</h3>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Motivo do bloqueio *</label>
                <textarea wire:model="motivoBloqueio" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-400"
                          placeholder="Ex.: Dados inconsistentes, atividade suspeita..."></textarea>
                @error('motivoBloqueio')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-3 justify-end">
                <x-admin.button variant="secondary" wire:click="cancelModals">Cancelar</x-admin.button>
                <x-admin.button variant="danger" wire:click="block">Confirmar Bloqueio</x-admin.button>
            </div>
        </div>
    </div>
    @endif
</div>
