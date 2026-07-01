<div>
    @if(! $onHome)
    <div class="max-w-lg mx-auto py-16 text-center">
        <div class="text-5xl mb-6">🏪</div>
        <h2 class="text-xl font-bold mb-3" style="color:#1a472a;">Sua loja não está na vitrine</h2>
        <p class="text-gray-600 text-sm">Sua loja ainda não aparece na página inicial da Feira Esquerda Livre. Entre em contato com a administração para solicitar visibilidade.</p>
    </div>
    @else

    {{-- Status atual --}}
    <x-admin.card class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-gray-900">{{ $expositor->name }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    @if($activeSlot && $activeSlot->slot_type === \App\Enums\VisibilitySlotType::HomeFeatured && $activeSlot->isActive())
                        ⭐ <strong class="text-yellow-700">Em destaque pago</strong>
                        @if($activeSlot->active_until)
                        · válido até {{ $activeSlot->active_until->format('d/m/Y') }}
                        @else
                        · sem data de término
                        @endif
                    @else
                        🔄 Participando da <strong>rotação democrática</strong>
                    @endif
                </p>
            </div>
            <div class="flex-shrink-0 text-right">
                <p class="text-xs text-gray-400">Peso na rotação</p>
                <p class="text-2xl font-extrabold" style="color:#1a472a;">{{ $expositor->home_rotation_weight }}×</p>
            </div>
        </div>
    </x-admin.card>

    {{-- Cards de métricas --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <x-admin.card>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Últimos 7 dias</p>
            <p class="text-2xl font-extrabold" style="color:#1a472a;">{{ number_format($stats['last7']) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">impressões</p>
        </x-admin.card>
        <x-admin.card>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Últimos 30 dias</p>
            <p class="text-2xl font-extrabold text-blue-700">{{ number_format($stats['last30']) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">impressões</p>
        </x-admin.card>
        <x-admin.card>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Total acumulado</p>
            <p class="text-2xl font-extrabold text-gray-700">{{ number_format($stats['total']) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">impressões</p>
        </x-admin.card>
    </div>

    {{-- Gráfico de barras (Alpine.js) --}}
    <x-admin.card class="mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Impressões por dia — últimos 30 dias</h3>

        @if(count($chartData) > 0)
        @php
            $maxCount = max(array_column($chartData, 'count')) ?: 1;
        @endphp
        <div class="flex items-end gap-0.5 h-24 w-full overflow-x-auto">
            @foreach($chartData as $point)
            @php $height = round($point['count'] / $maxCount * 100); @endphp
            <div class="flex-1 min-w-[6px] flex flex-col items-center justify-end group relative">
                <div class="absolute bottom-full mb-1 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-800 text-white text-xs rounded px-1.5 py-0.5 whitespace-nowrap z-10">
                    {{ \Carbon\Carbon::parse($point['day'])->format('d/m') }}: {{ $point['count'] }}
                </div>
                <div class="w-full rounded-t" style="height: {{ $height }}%; min-height: 2px; background:#52b788;"></div>
            </div>
            @endforeach
        </div>
        <div class="flex justify-between mt-1 text-xs text-gray-400">
            <span>{{ \Carbon\Carbon::parse($chartData[0]['day'])->format('d/m') }}</span>
            <span>{{ \Carbon\Carbon::parse(end($chartData)['day'])->format('d/m') }}</span>
        </div>
        @else
        <div class="py-10 text-center text-gray-400 text-sm">Sem dados de impressão nos últimos 30 dias.</div>
        @endif
    </x-admin.card>

    {{-- Top-10 dias --}}
    @if(isset($topDays) && $topDays->isNotEmpty())
    <x-admin.card>
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Top 10 dias com mais exposição</h3>
        <div class="space-y-2">
            @foreach($topDays as $i => $row)
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-3">
                    <span class="w-6 text-center text-xs font-bold text-gray-400">{{ $i + 1 }}</span>
                    <span class="text-gray-700">{{ \Carbon\Carbon::parse($row->day)->isoFormat('dddd, D [de] MMMM') }}</span>
                </div>
                <span class="font-semibold" style="color:#1a472a;">{{ number_format($row->count) }}</span>
            </div>
            @endforeach
        </div>
    </x-admin.card>
    @endif

    <div class="mt-6 p-4 bg-blue-50 border border-blue-100 rounded-xl">
        <p class="text-xs text-blue-700">
            💡 <strong>Sua loja participou da vitrine da Feira em
            {{ \App\Models\ExpositorImpression::where('expositor_id', $expositor->id)->where('rendered_at', '>=', now()->subDays(30))->selectRaw('COUNT(DISTINCT DATE(rendered_at)) as days')->value('days') ?? 0 }}
            dias no último mês.</strong>
            A seleção de expositores exibidos é renovada a cada poucos minutos com rotação aleatória, garantindo exposição justa para todos.
        </p>
    </div>

    @endif
</div>
