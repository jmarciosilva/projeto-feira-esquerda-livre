<div>
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('admin.email-marketing.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Campanhas</a>
        <span class="text-gray-300">/</span>
        <span class="text-sm font-semibold text-gray-700">Relatório: {{ $campaign->name }}</span>
    </div>

    {{-- Cabeçalho da campanha --}}
    <x-admin.card class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900">{{ $campaign->name }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">Assunto: <strong>{{ $campaign->subject }}</strong></p>
                <p class="text-xs text-gray-400 mt-1">
                    De: {{ $campaign->from_name }} &lt;{{ $campaign->from_email }}&gt;
                    · Segmento: {{ $campaign->recipient_type->label() }}
                    @if($campaign->sent_at) · Enviado: {{ $campaign->sent_at->format('d/m/Y H:i') }} @endif
                </p>
            </div>
            <x-admin.badge :color="$campaign->status->color()">{{ $campaign->status->label() }}</x-admin.badge>
        </div>
    </x-admin.card>

    {{-- Métricas --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <x-admin.card>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Destinatários</p>
            <p class="text-2xl font-extrabold text-gray-900">{{ number_format($stats['recipients']) }}</p>
        </x-admin.card>
        <x-admin.card>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Enviados</p>
            <p class="text-2xl font-extrabold" style="color:#1a472a;">{{ number_format($stats['sent']) }}</p>
        </x-admin.card>
        <x-admin.card>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Aberturas</p>
            <p class="text-2xl font-extrabold text-blue-700">{{ number_format($stats['opened']) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $stats['open_rate'] }}% de taxa</p>
        </x-admin.card>
        <x-admin.card>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Cliques</p>
            <p class="text-2xl font-extrabold text-indigo-700">{{ number_format($stats['clicked']) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $stats['click_rate'] }}% de taxa</p>
        </x-admin.card>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-6">
        <x-admin.card>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Descadastros</p>
            <p class="text-xl font-extrabold text-orange-600">{{ number_format($stats['unsubscribed']) }}</p>
        </x-admin.card>
        <x-admin.card>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Falhas de envio</p>
            <p class="text-xl font-extrabold text-red-600">{{ number_format($stats['failed']) }}</p>
        </x-admin.card>
    </div>

    {{-- Lista de envios --}}
    <x-admin.card>
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Envios individuais</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">E-mail</th>
                        <th class="text-center py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Enviado</th>
                        <th class="text-center py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Aberto</th>
                        <th class="text-center py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Clicou</th>
                        <th class="text-center py-3 px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Descadastro</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($sends as $send)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-2">
                            <p class="font-medium text-gray-800">{{ $send->name ?? '—' }}</p>
                            <p class="text-xs text-gray-500">{{ $send->email }}</p>
                        </td>
                        <td class="py-3 px-2 text-center hidden sm:table-cell text-xs text-gray-500">
                            {{ $send->sent_at?->format('d/m H:i') ?? '—' }}
                        </td>
                        <td class="py-3 px-2 text-center">
                            @if($send->opened_at)
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-700">
                                ✓ {{ $send->opened_at->format('d/m H:i') }}
                            </span>
                            @else
                            <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-2 text-center hidden sm:table-cell">
                            @if($send->clicked_at)
                            <span class="text-xs font-semibold text-blue-700">✓</span>
                            @else
                            <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-2 text-center">
                            @if($send->unsubscribed_at)
                            <span class="text-xs font-semibold text-orange-600">{{ $send->unsubscribed_at->format('d/m') }}</span>
                            @else
                            <span class="text-gray-300">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-10 text-center text-gray-400 text-sm">Nenhum envio registrado.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sends->hasPages())
        <div class="pt-4">{{ $sends->links() }}</div>
        @endif
    </x-admin.card>
</div>
