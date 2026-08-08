{{-- Indicador de status de conexão com a API --}}
<div class="flex items-center space-x-3">
    @if ($isOnline)
        <div class="flex items-center">
            <span class="flex h-3 w-3 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
            </span>
            <span class="ml-2 text-sm font-medium text-green-700">Conectado</span>
        </div>
    @else
        <div class="flex items-center">
            <span class="inline-flex rounded-full h-3 w-3 bg-red-500"></span>
            <span class="ml-2 text-sm font-medium text-red-700">Desconectado</span>
        </div>
    @endif
    @if ($lastChecked)
        <span class="text-xs text-gray-500">
            Verificado há {{ $lastChecked }}
        </span>
    @endif
</div>
