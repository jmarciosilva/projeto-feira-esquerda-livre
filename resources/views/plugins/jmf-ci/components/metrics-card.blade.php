{{-- Card exibindo métrica com label e cor --}}
<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center">
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">
                {{ $label }}
            </p>
            <p class="text-3xl font-bold text-gray-900 mt-2">
                {{ $value }}
            </p>
        </div>
        @if ($icon ?? false)
            <div class="ml-4">
                <div class="rounded-full p-3 bg-{{ $color }}-100">
                    <svg class="h-8 w-8 text-{{ $color }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
                    </svg>
                </div>
            </div>
        @endif
    </div>
    @if ($trend ?? false)
        <div class="mt-4 flex items-center text-sm">
            <span class="text-{{ $trendPositive ? 'green' : 'red' }}-600 font-semibold">
                {{ $trendPositive ? '+' : '-' }}{{ abs($trend) }}%
            </span>
            <span class="text-gray-500 ml-2">vs. período anterior</span>
        </div>
    @endif
</div>
