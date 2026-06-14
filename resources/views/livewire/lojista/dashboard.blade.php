<div>
    @if(! $expositor)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
        <p class="text-amber-800 font-medium text-lg mb-2">Sua loja ainda não está configurada.</p>
        <p class="text-amber-700 text-sm mb-4">Entre em contato com a administração para ativar seu perfil.</p>
        <a href="{{ url('/') }}" class="inline-block px-6 py-3 rounded-lg font-semibold text-[#3D3000]" style="background:#F4E294;">
            Voltar ao site
        </a>
    </div>
    @else

    {{-- Boas-vindas --}}
    <div class="rounded-xl p-6 mb-6 flex flex-col sm:flex-row items-start sm:items-center gap-4" style="background:linear-gradient(135deg,#F4E294,#E8A000);">
        <div class="flex-1">
            <p class="text-[#3D3000] text-sm font-medium mb-1">Bem-vindo de volta,</p>
            <h2 class="text-2xl font-bold text-[#3D3000]">{{ $expositor->name }}</h2>
            @if($expositor->city)
            <p class="text-[#5C3000] text-sm mt-1">{{ $expositor->city }}@if($expositor->state) / {{ $expositor->state }}@endif</p>
            @endif
        </div>
        <a href="{{ route('lojista.loja') }}"
           class="flex-shrink-0 px-5 py-2.5 rounded-lg font-semibold text-sm text-white transition-colors"
           style="background:#3D3000;">
            Editar Perfil da Loja
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-3xl font-bold text-gray-900">{{ $totalProdutos }}</p>
            <p class="text-sm text-gray-500 mt-1">Produtos cadastrados</p>
            <p class="text-xs text-gray-400 mt-3">Disponível na Fase 3</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-3xl font-bold text-gray-900">{{ $upcomingEvents->count() }}</p>
            <p class="text-sm text-gray-500 mt-1">Feiras inscritas</p>
            @if($upcomingEvents->isNotEmpty())
            <p class="text-xs text-gray-400 mt-3">Próxima: {{ $upcomingEvents->first()->start_date->format('d/m/Y') }}</p>
            @endif
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 col-span-2 lg:col-span-1">
            <div class="flex items-center gap-2 mb-2">
                @if($expositor->is_active)
                <div class="w-2.5 h-2.5 rounded-full bg-green-500"></div>
                <span class="text-sm font-medium text-green-700">Loja ativa</span>
                @else
                <div class="w-2.5 h-2.5 rounded-full bg-red-500"></div>
                <span class="text-sm font-medium text-red-700">Loja inativa</span>
                @endif
            </div>
            @if(! $expositor->logo_path || ! $expositor->description)
            <p class="text-xs text-amber-700">Complete seu perfil para aparecer em destaque.</p>
            <a href="{{ route('lojista.loja') }}" class="text-xs font-medium underline text-amber-600">Completar agora →</a>
            @else
            <p class="text-xs text-gray-500">Perfil completo.</p>
            @endif
        </div>
    </div>

    {{-- Próximas feiras --}}
    @if($upcomingEvents->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-900 mb-4">Suas Próximas Feiras</h3>
        <div class="space-y-3">
            @foreach($upcomingEvents as $event)
            <div class="flex items-center gap-4 p-3 rounded-lg" style="background:#FDF8DC;">
                <div class="w-12 text-center flex-shrink-0 bg-white rounded-lg py-1.5 border border-[#F4E294]">
                    <p class="text-sm font-bold text-[#3D3000]">{{ $event->start_date->format('d') }}</p>
                    <p class="text-xs text-[#E8A000]">{{ $event->start_date->isoFormat('MMM') }}</p>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 text-sm">{{ $event->title }}</p>
                    <p class="text-xs text-gray-500">{{ $event->city }}@if($event->state) · {{ $event->state }}@endif</p>
                </div>
                <a href="{{ route('agenda.show', $event->slug) }}" class="text-xs font-medium text-[#E8A000] hover:underline whitespace-nowrap">
                    Ver feira →
                </a>
            </div>
            @endforeach
        </div>
        <div class="mt-4">
            <a href="{{ route('agenda.index') }}" class="text-sm font-medium" style="color:#E8A000;">
                Ver agenda completa →
            </a>
        </div>
    </div>
    @else
    <div class="bg-white rounded-xl border border-gray-200 p-6 text-center">
        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <p class="text-gray-500 text-sm">Você ainda não está inscrito em nenhuma feira.</p>
        <a href="{{ route('agenda.index') }}" class="mt-3 inline-block text-sm font-medium" style="color:#E8A000;">
            Ver feiras disponíveis →
        </a>
    </div>
    @endif

    @endif
</div>
