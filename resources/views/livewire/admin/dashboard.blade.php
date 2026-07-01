<div>
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
        {{ session('success') }}
    </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
        @foreach([
            ['label' => 'Páginas',   'value' => $stats['pages'],   'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'color' => 'blue'],
            ['label' => 'Banners',   'value' => $stats['banners'],  'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', 'color' => 'purple'],
            ['label' => 'Posts',     'value' => $stats['posts'],    'icon' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z', 'color' => 'yellow'],
            ['label' => 'Eventos',   'value' => $stats['events'],   'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'color' => 'green'],
            ['label' => 'Mídias',    'value' => $stats['media'],    'icon' => 'M3 7a2 2 0 012-2h3.586a1 1 0 01.707.293L11 7h9a2 2 0 012 2v9a2 2 0 01-2 2H4a2 2 0 01-2-2V7z', 'color' => 'red'],
            ['label' => 'Pendentes', 'value' => $stats['solicitacoes_pendentes'], 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'color' => $stats['solicitacoes_pendentes'] > 0 ? 'orange' : 'gray'],
        ] as $stat)
        @php
        $colors = [
            'blue'   => 'bg-blue-50 text-blue-600',
            'purple' => 'bg-purple-50 text-purple-600',
            'yellow' => 'bg-yellow-50 text-yellow-600',
            'green'  => 'bg-green-50 text-green-600',
            'red'    => 'bg-red-50 text-red-600',
            'orange' => 'bg-orange-50 text-orange-600',
            'gray'   => 'bg-gray-50 text-gray-400',
        ];
        @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 {{ $colors[$stat['color']] }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stat['icon'] }}"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900">{{ $stat['value'] }}</p>
                <p class="text-sm text-gray-500">{{ $stat['label'] }}</p>
            </div>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Posts Recentes --}}
        <x-admin.card title="Posts Recentes">
            @if($recentPosts->isEmpty())
            <p class="text-sm text-gray-400 text-center py-4">Nenhum post criado ainda.</p>
            @else
            <div class="space-y-3">
                @foreach($recentPosts as $post)
                <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $post->title }}</p>
                        <p class="text-xs text-gray-500">{{ $post->author->name }} · {{ $post->created_at->diffForHumans() }}</p>
                    </div>
                    <x-admin.badge :color="$post->status->color()">{{ $post->status->label() }}</x-admin.badge>
                </div>
                @endforeach
            </div>
            <div class="mt-4">
                <a href="{{ route('admin.posts.index') }}" class="text-sm text-[#1a472a] font-medium hover:underline">Ver todos →</a>
            </div>
            @endif
        </x-admin.card>

        {{-- Próximos Eventos --}}
        <x-admin.card title="Próximos Eventos">
            @if($upcomingEvents->isEmpty())
            <p class="text-sm text-gray-400 text-center py-4">Nenhum evento próximo.</p>
            @else
            <div class="space-y-3">
                @foreach($upcomingEvents as $event)
                <div class="flex items-start gap-3 py-2 border-b border-gray-50 last:border-0">
                    <div class="w-10 text-center flex-shrink-0 bg-[#f0fdf4] rounded-lg py-1">
                        <p class="text-xs text-[#1a472a] font-semibold">{{ $event->start_date->format('d') }}</p>
                        <p class="text-xs text-[#52b788]">{{ $event->start_date->isoFormat('MMM') }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $event->title }}</p>
                        <p class="text-xs text-gray-500">{{ $event->city }}@if($event->state) - {{ $event->state }}@endif</p>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4">
                <a href="{{ route('admin.events.index') }}" class="text-sm text-[#1a472a] font-medium hover:underline">Ver todos →</a>
            </div>
            @endif
        </x-admin.card>

        {{-- Solicitações de Lojistas --}}
        <x-admin.card title="Solicitações Pendentes">
            @if($recentSolicitacoes->isEmpty())
            <p class="text-sm text-gray-400 text-center py-4">Nenhuma solicitação pendente.</p>
            @else
            <div class="space-y-3">
                @foreach($recentSolicitacoes as $s)
                <div class="py-2 border-b border-gray-50 last:border-0">
                    <p class="text-sm font-medium text-gray-900">{{ $s->nome_loja }}</p>
                    <p class="text-xs text-gray-500">{{ $s->responsavel }} · {{ $s->created_at->diffForHumans() }}</p>
                </div>
                @endforeach
            </div>
            <div class="mt-4">
                <a href="{{ route('admin.lojistas.solicitacoes') }}" class="text-sm text-orange-600 font-medium hover:underline">
                    Revisar solicitações →
                </a>
            </div>
            @endif
        </x-admin.card>
    </div>

    {{-- Quick Actions --}}
    <div class="mt-6">
        <x-admin.card title="Ações Rápidas">
            <div class="flex flex-wrap gap-3">
                @can('cms.editar')
                <a href="{{ route('admin.pages.create') }}">
                    <x-admin.button variant="secondary">+ Nova Página</x-admin.button>
                </a>
                <a href="{{ route('admin.banners.create') }}">
                    <x-admin.button variant="secondary">+ Novo Banner</x-admin.button>
                </a>
                <a href="{{ route('admin.posts.create') }}">
                    <x-admin.button variant="secondary">+ Novo Post</x-admin.button>
                </a>
                <a href="{{ route('admin.events.create') }}">
                    <x-admin.button variant="secondary">+ Novo Evento</x-admin.button>
                </a>
                @endcan
                @can('cms.visualizar')
                <a href="{{ route('admin.media.index') }}">
                    <x-admin.button variant="secondary">+ Enviar Mídia</x-admin.button>
                </a>
                @endcan
                @can('lojistas.visualizar')
                <a href="{{ route('admin.lojistas.solicitacoes') }}">
                    <x-admin.button variant="secondary">Ver Lojistas</x-admin.button>
                </a>
                @endcan
            </div>
        </x-admin.card>
    </div>
</div>
