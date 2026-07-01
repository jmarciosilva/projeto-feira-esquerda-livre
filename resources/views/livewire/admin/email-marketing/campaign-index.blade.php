<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex flex-col sm:flex-row gap-3 flex-1">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar campanha ou assunto..."
                   class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
            <select wire:model.live="filterStatus" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]">
                <option value="">Todos os status</option>
                @foreach($statuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        @can('email-marketing.gerenciar')
        <a href="{{ route('admin.email-marketing.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-sm"
           style="background:#1a472a; color:#F4E294;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nova Campanha
        </a>
        @endcan
    </div>

    <x-admin.card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Campanha</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Segmento</th>
                        <th class="text-center py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Enviados</th>
                        <th class="text-center py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="text-right py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($campaigns as $campaign)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-2">
                            <p class="font-semibold text-gray-900">{{ $campaign->name }}</p>
                            <p class="text-xs text-gray-500">{{ $campaign->subject }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $campaign->created_at->format('d/m/Y') }}
                                @if($campaign->scheduled_at && $campaign->status === \App\Enums\CampaignStatus::Scheduled)
                                · Agendado: {{ $campaign->scheduled_at->format('d/m H:i') }}
                                @endif
                            </p>
                        </td>
                        <td class="py-3 px-2 hidden md:table-cell text-xs text-gray-600">
                            {{ $campaign->recipient_type->label() }}
                        </td>
                        <td class="py-3 px-2 hidden sm:table-cell text-center">
                            @if($campaign->sent_count > 0)
                            <p class="font-semibold text-gray-800">{{ number_format($campaign->sent_count) }}</p>
                            <p class="text-xs text-gray-400">{{ $campaign->openRate() }}% aberturas</p>
                            @else
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-2 text-center">
                            <x-admin.badge :color="$campaign->status->color()">{{ $campaign->status->label() }}</x-admin.badge>
                        </td>
                        <td class="py-3 px-2 text-right">
                            <div class="flex items-center justify-end gap-2 flex-wrap">
                                @if($campaign->sent_count > 0)
                                <a href="{{ route('admin.email-marketing.report', $campaign->id) }}"
                                   class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Relatório</a>
                                @endif

                                @can('email-marketing.gerenciar')
                                @if($campaign->status->isEditable())
                                <a href="{{ route('admin.email-marketing.edit', $campaign->id) }}"
                                   class="text-xs font-semibold text-gray-500 hover:text-gray-700">Editar</a>
                                @endif

                                <button wire:click="duplicate({{ $campaign->id }})"
                                        class="text-xs font-semibold text-gray-500 hover:text-gray-700">Duplicar</button>

                                @if($campaign->status === \App\Enums\CampaignStatus::Draft)
                                <button wire:click="sendNow({{ $campaign->id }})"
                                        wire:confirm="Enviar esta campanha agora para {{ $campaign->recipient_type->label() }}?"
                                        class="text-xs font-semibold text-green-600 hover:text-green-800">Enviar agora</button>

                                <button wire:click="delete({{ $campaign->id }})"
                                        wire:confirm="Excluir esta campanha? Esta ação não pode ser desfeita."
                                        class="text-xs font-semibold text-red-500 hover:text-red-700">Excluir</button>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-16 text-center">
                            <div class="text-5xl mb-4">📧</div>
                            <p class="text-base font-semibold text-gray-500">Nenhuma campanha encontrada.</p>
                            @can('email-marketing.gerenciar')
                            <a href="{{ route('admin.email-marketing.create') }}"
                               class="mt-3 inline-flex items-center gap-1 text-sm font-semibold" style="color:#1a472a;">
                                Criar primeira campanha
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($campaigns->hasPages())
        <div class="pt-4">{{ $campaigns->links() }}</div>
        @endif
    </x-admin.card>
</div>
