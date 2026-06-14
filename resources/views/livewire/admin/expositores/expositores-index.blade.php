<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por nome, cidade ou e-mail..."
               class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
        <select wire:model.live="filterStatus" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
            <option value="">Todos</option>
            <option value="featured">Em Destaque</option>
            <option value="active">Ativos</option>
        </select>
    </div>

    <x-admin.card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Expositor</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Local</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Redes Sociais</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">PIX / Banco</th>
                        <th class="text-center py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Destaque</th>
                        <th class="text-center py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ativo</th>
                        <th class="text-right py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($expositores as $expositor)
                    <tr class="hover:bg-gray-50 transition-colors">

                        {{-- Expositor: logo + nome + email --}}
                        <td class="py-3 px-2">
                            <div class="flex items-center gap-3">
                                @if($expositor->logo_path)
                                <img src="{{ Storage::url($expositor->logo_path) }}" alt="{{ $expositor->name }}"
                                     class="w-10 h-10 rounded-full object-cover flex-shrink-0 border border-gray-200">
                                @else
                                <div class="w-10 h-10 rounded-full bg-[#f0fdf4] flex items-center justify-center flex-shrink-0 text-[#52b788] font-bold text-sm">
                                    {{ strtoupper(mb_substr($expositor->name, 0, 1)) }}
                                </div>
                                @endif
                                <div>
                                    <p class="font-semibold text-gray-900 max-w-[160px] truncate">{{ $expositor->name }}</p>
                                    @if($expositor->email)
                                    <p class="text-xs text-gray-400 truncate max-w-[160px]">{{ $expositor->email }}</p>
                                    @endif
                                    @if($expositor->whatsapp)
                                    <p class="text-xs text-gray-400">{{ $expositor->whatsapp }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Local --}}
                        <td class="py-3 px-2 text-xs text-gray-600">
                            @if($expositor->city || $expositor->state)
                            <p>{{ $expositor->city }}@if($expositor->city && $expositor->state) — @endif{{ $expositor->state }}</p>
                            @else
                            <span class="text-gray-300 italic">—</span>
                            @endif
                        </td>

                        {{-- Redes Sociais --}}
                        <td class="py-3 px-2">
                            <div class="flex flex-col gap-1">
                                @if($expositor->instagram_url)
                                <a href="{{ $expositor->instagram_url }}" target="_blank"
                                   class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-1 rounded-md"
                                   style="background:#fce7f3; color:#9d174d;">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                    Instagram
                                </a>
                                @endif
                                @if($expositor->facebook_url)
                                <a href="{{ $expositor->facebook_url }}" target="_blank"
                                   class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-1 rounded-md"
                                   style="background:#dbeafe; color:#1e40af;">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                    Facebook
                                </a>
                                @endif
                                @if(!$expositor->instagram_url && !$expositor->facebook_url)
                                <span class="text-xs text-gray-300 italic">Não informado</span>
                                @endif
                            </div>
                        </td>

                        {{-- PIX / Banco --}}
                        <td class="py-3 px-2">
                            @if($expositor->pix_chave)
                            <div>
                                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full"
                                      style="background:#fef9c3; color:#713f12;">
                                    PIX · {{ $expositor->pix_tipo ?? 'Chave' }}
                                </span>
                                <p class="text-xs text-gray-500 mt-1 max-w-[140px] truncate" title="{{ $expositor->pix_chave }}">{{ $expositor->pix_chave }}</p>
                            </div>
                            @elseif($expositor->banco_nome)
                            <p class="text-xs text-gray-500">{{ $expositor->banco_nome }}</p>
                            @else
                            <span class="text-xs text-gray-300 italic">Não cadastrado</span>
                            @endif
                        </td>

                        {{-- Destaque --}}
                        <td class="py-3 px-2 text-center">
                            <button wire:click="toggleFeatured({{ $expositor->id }})" title="Alternar destaque">
                                <x-admin.badge :color="$expositor->is_featured ? 'yellow' : 'gray'">
                                    {{ $expositor->is_featured ? '⭐ Destaque' : 'Normal' }}
                                </x-admin.badge>
                            </button>
                        </td>

                        {{-- Ativo --}}
                        <td class="py-3 px-2 text-center">
                            <button wire:click="toggleActive({{ $expositor->id }})">
                                <x-admin.badge :color="$expositor->is_active ? 'green' : 'gray'">
                                    {{ $expositor->is_active ? 'Ativo' : 'Inativo' }}
                                </x-admin.badge>
                            </button>
                        </td>

                        {{-- Ações --}}
                        <td class="py-3 px-2 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="openView({{ $expositor->id }})"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-800 transition-colors px-2 py-1 rounded-lg hover:bg-blue-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Visualizar
                                </button>
                                <a href="{{ route('admin.expositores.edit', $expositor) }}"
                                   class="inline-flex items-center gap-1 text-xs font-medium text-[#52b788] hover:text-[#1a472a] transition-colors px-2 py-1 rounded-lg hover:bg-green-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Editar
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-gray-400 text-sm">
                            Nenhum expositor encontrado.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($expositores->hasPages())
        <div class="mt-4 px-2">
            {{ $expositores->links() }}
        </div>
        @endif
    </x-admin.card>

    {{-- ================================================================ --}}
    {{-- MODAL: Visualizar Expositor --}}
    {{-- ================================================================ --}}
    @if($viewId && $viewExpositor)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         wire:click.self="closeView">

        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

            {{-- Cabeçalho do modal --}}
            <div class="relative overflow-hidden rounded-t-2xl" style="background:linear-gradient(135deg,#3D3000,#5C4500);">
                {{-- Imagem de capa como fundo --}}
                @if($viewExpositor->image_path)
                <img src="{{ Storage::url($viewExpositor->image_path) }}" alt="Capa"
                     class="absolute inset-0 w-full h-full object-cover opacity-25">
                @endif

                <div class="relative px-6 py-5 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        {{-- Logo --}}
                        <div class="w-16 h-16 rounded-xl border-2 overflow-hidden flex-shrink-0"
                             style="border-color:rgba(244,226,148,0.4); background:#F4E294;">
                            @if($viewExpositor->logo_path)
                            <img src="{{ Storage::url($viewExpositor->logo_path) }}" alt="{{ $viewExpositor->name }}" class="w-full h-full object-cover">
                            @else
                            <div class="w-full h-full flex items-center justify-center font-bold text-xl" style="color:#3D3000;">
                                {{ strtoupper(mb_substr($viewExpositor->name, 0, 1)) }}
                            </div>
                            @endif
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">{{ $viewExpositor->name }}</h2>
                            <div class="flex items-center gap-2 mt-1">
                                <x-admin.badge :color="$viewExpositor->is_active ? 'green' : 'gray'">
                                    {{ $viewExpositor->is_active ? 'Ativo' : 'Inativo' }}
                                </x-admin.badge>
                                @if($viewExpositor->is_featured)
                                <x-admin.badge color="yellow">⭐ Destaque</x-admin.badge>
                                @endif
                            </div>
                        </div>
                    </div>
                    <button wire:click="closeView"
                            class="w-8 h-8 rounded-full flex items-center justify-center text-white/70 hover:text-white hover:bg-white/10 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Imagem da Home --}}
            @if($viewExpositor->image_path)
            <div class="mx-6 mt-5 rounded-xl overflow-hidden border border-gray-100">
                <div class="px-3 py-2 border-b border-gray-100 bg-gray-50">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Imagem da Home</p>
                </div>
                <img src="{{ Storage::url($viewExpositor->image_path) }}" alt="Imagem da Home"
                     class="w-full h-40 object-cover">
            </div>
            @endif

            {{-- Corpo do modal --}}
            <div class="px-6 py-5 space-y-5">

                {{-- Informações de Contato --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Informações de Contato</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @if($viewExpositor->email)
                        <div class="flex items-center gap-2 p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <div class="min-w-0">
                                <p class="text-xs text-gray-400">E-mail</p>
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $viewExpositor->email }}</p>
                            </div>
                        </div>
                        @endif
                        @if($viewExpositor->whatsapp)
                        <div class="flex items-center gap-2 p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" style="color:#25D366" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            <div>
                                <p class="text-xs text-gray-400">WhatsApp</p>
                                <p class="text-sm font-medium text-gray-900">{{ $viewExpositor->whatsapp }}</p>
                            </div>
                        </div>
                        @endif
                        @if($viewExpositor->city || $viewExpositor->state)
                        <div class="flex items-center gap-2 p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <div>
                                <p class="text-xs text-gray-400">Localização</p>
                                <p class="text-sm font-medium text-gray-900">{{ $viewExpositor->city }}@if($viewExpositor->city && $viewExpositor->state), @endif{{ $viewExpositor->state }}</p>
                            </div>
                        </div>
                        @endif
                        @if($viewExpositor->website_url)
                        <div class="flex items-center gap-2 p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            <div class="min-w-0">
                                <p class="text-xs text-gray-400">Site</p>
                                <a href="{{ $viewExpositor->website_url }}" target="_blank" class="text-sm font-medium text-blue-600 hover:underline truncate block">{{ $viewExpositor->website_url }}</a>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Redes Sociais --}}
                @if($viewExpositor->instagram_url || $viewExpositor->facebook_url)
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Redes Sociais</h3>
                    <div class="flex flex-wrap gap-3">
                        @if($viewExpositor->instagram_url)
                        <a href="{{ $viewExpositor->instagram_url }}" target="_blank"
                           class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-opacity hover:opacity-80"
                           style="background:#fce7f3; color:#9d174d;">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                            Instagram
                        </a>
                        @endif
                        @if($viewExpositor->facebook_url)
                        <a href="{{ $viewExpositor->facebook_url }}" target="_blank"
                           class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-opacity hover:opacity-80"
                           style="background:#dbeafe; color:#1e40af;">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            Facebook
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Dados Bancários --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Dados Bancários e PIX</h3>
                    @php
                        $hasBankData = $viewExpositor->pix_chave || $viewExpositor->banco_nome || $viewExpositor->banco_conta;
                    @endphp
                    @if($hasBankData)
                    <div class="rounded-xl border border-gray-100 overflow-hidden">
                        @if($viewExpositor->pix_chave)
                        <div class="p-4 border-b border-gray-50" style="background:#fefce8;">
                            <div class="flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4" fill="none" stroke="#ca8a04" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                </svg>
                                <span class="text-xs font-bold" style="color:#92400e;">Chave PIX · {{ $viewExpositor->pix_tipo ?? 'Não especificado' }}</span>
                            </div>
                            <p class="text-sm font-mono font-semibold text-gray-800 mt-1 break-all">{{ $viewExpositor->pix_chave }}</p>
                        </div>
                        @endif
                        @if($viewExpositor->banco_nome || $viewExpositor->banco_agencia || $viewExpositor->banco_conta)
                        <div class="p-4 grid grid-cols-2 sm:grid-cols-3 gap-3 bg-white">
                            @if($viewExpositor->banco_nome)
                            <div>
                                <p class="text-xs text-gray-400">Banco</p>
                                <p class="text-sm font-medium text-gray-900">{{ $viewExpositor->banco_nome }}</p>
                            </div>
                            @endif
                            @if($viewExpositor->banco_agencia)
                            <div>
                                <p class="text-xs text-gray-400">Agência</p>
                                <p class="text-sm font-medium text-gray-900">{{ $viewExpositor->banco_agencia }}</p>
                            </div>
                            @endif
                            @if($viewExpositor->banco_conta)
                            <div>
                                <p class="text-xs text-gray-400">Conta</p>
                                <p class="text-sm font-medium text-gray-900">{{ $viewExpositor->banco_conta }}</p>
                            </div>
                            @endif
                            @if($viewExpositor->banco_tipo_conta)
                            <div>
                                <p class="text-xs text-gray-400">Tipo de Conta</p>
                                <p class="text-sm font-medium text-gray-900">{{ $viewExpositor->banco_tipo_conta }}</p>
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>
                    @else
                    <div class="flex items-center gap-3 p-4 rounded-xl border border-dashed border-gray-200 bg-gray-50">
                        <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-gray-500">Dados bancários não cadastrados</p>
                            <p class="text-xs text-gray-400">O expositor ainda não informou dados bancários ou PIX.</p>
                        </div>
                        <a href="{{ route('admin.expositores.edit', $viewExpositor) }}"
                           class="ml-auto text-xs font-semibold text-[#52b788] hover:underline flex-shrink-0">
                            Preencher →
                        </a>
                    </div>
                    @endif
                </div>

            </div>

            {{-- Rodapé do modal --}}
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50 rounded-b-2xl">
                <a href="{{ route('loja.show', $viewExpositor->slug) }}" target="_blank"
                   class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 hover:text-gray-700">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Ver loja pública
                </a>
                <div class="flex items-center gap-3">
                    <button wire:click="closeView"
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 font-medium">
                        Fechar
                    </button>
                    <a href="{{ route('admin.expositores.edit', $viewExpositor) }}"
                       class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition-opacity hover:opacity-90"
                       style="background:#52b788;">
                        Editar Expositor
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
