<div class="space-y-6">
    @if(session('success'))
    <div class="p-4 rounded-xl border text-sm font-medium" style="background:#F0F8E8; border-color:#CDE7B0; color:#3D5C1F;">
        {{ session('success') }}
    </div>
    @endif

    <section class="rounded-2xl overflow-hidden border shadow-sm" style="border-color:#E8DFA8; background:#3D3000;">
        <div class="grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
            <div class="p-6 lg:p-8">
                <p class="text-sm font-bold uppercase tracking-wide" style="color:#F4E294;">Painel Administrativo</p>
                <h2 class="mt-2 text-2xl lg:text-3xl font-black text-white">Visao geral da Feira Esquerda Livre</h2>
                <p class="mt-3 max-w-2xl text-sm lg:text-base" style="color:#FFF4B8;">
                    Acompanhe conteudo, marketplace, lojistas e comunidade em um unico lugar.
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    @can('cms.editar')
                    <a href="{{ route('admin.posts.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl px-4 text-sm font-bold text-white transition-opacity hover:opacity-90" style="background:#C47A00;">
                        Novo post
                    </a>
                    <a href="{{ route('admin.events.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl px-4 text-sm font-bold transition-opacity hover:opacity-85" style="background:#F4E294; color:#3D3000;">
                        Novo evento
                    </a>
                    @endcan
                    <a href="{{ url('/') }}" target="_blank" class="inline-flex min-h-11 items-center justify-center rounded-xl px-4 text-sm font-bold border transition-opacity hover:opacity-85" style="border-color:#F4E294; color:#F4E294;">
                        Ver site publico
                    </a>
                </div>
            </div>
            <div class="p-6 lg:p-8 flex lg:items-end" style="background:linear-gradient(135deg, rgba(244,226,148,0.20), rgba(196,122,0,0.28));">
                <div class="w-full rounded-2xl p-5" style="background:rgba(255,253,240,0.94);">
                    <p class="text-xs font-bold uppercase tracking-wide" style="color:#7A5C00;">Pendencias agora</p>
                    <div class="mt-3 flex items-end justify-between gap-4">
                        <div>
                            <p class="text-4xl font-black" style="color:#3D3000;">{{ $stats['solicitacoes_pendentes'] }}</p>
                            <p class="text-sm" style="color:#7A5C00;">solicitacoes de lojistas</p>
                        </div>
                        <a href="{{ route('admin.lojistas.solicitacoes') }}" class="text-sm font-bold hover:underline" style="color:#C47A00;">
                            Revisar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Faturamento --}}
    @php
        $growth = $revenueGrowthPercent;
        $growthLabel = $growth === null
            ? 'sem comparação com mês anterior'
            : (($growth >= 0 ? '+' : '') . number_format($growth, 1, ',', '.') . '% vs mês passado');
        $growthColor = $growth === null ? '#7A5C00' : ($growth >= 0 ? '#3D5C1F' : '#A13D2E');
        $netToStores = $revenueTotal - $commissionTotal;
        $maxDaily = max(1, collect($dailyRevenue)->max('total'));
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="rounded-2xl border p-5 shadow-sm" style="background:#FFFDF0; border-color:#E8DFA8;">
            <p class="text-xs font-bold uppercase tracking-wide" style="color:#7A5C00;">Faturamento confirmado</p>
            <p class="mt-2 text-2xl font-black" style="color:#3D3000;">R$ {{ number_format($revenueTotal, 2, ',', '.') }}</p>
            <p class="mt-1 text-xs" style="color:#7A5C00;">{{ $confirmedOrdersCount }} {{ $confirmedOrdersCount === 1 ? 'pedido pago' : 'pedidos pagos' }} · total</p>
        </div>
        <div class="rounded-2xl border p-5 shadow-sm" style="background:#F8EFE5; border-color:#E8DFA8;">
            <p class="text-xs font-bold uppercase tracking-wide" style="color:#7A5C00;">Comissão da plataforma</p>
            <p class="mt-2 text-2xl font-black" style="color:#5C3000;">R$ {{ number_format($commissionTotal, 2, ',', '.') }}</p>
            <p class="mt-1 text-xs" style="color:#7A5C00;">Repasse aos lojistas: R$ {{ number_format($netToStores, 2, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border p-5 shadow-sm" style="background:#F0F8E8; border-color:#E8DFA8;">
            <p class="text-xs font-bold uppercase tracking-wide" style="color:#7A5C00;">Este mês</p>
            <p class="mt-2 text-2xl font-black" style="color:#3D3000;">R$ {{ number_format($revenueThisMonth, 2, ',', '.') }}</p>
            <p class="mt-1 text-xs font-semibold" style="color:{{ $growthColor }};">{{ $growthLabel }}</p>
        </div>
        <div class="rounded-2xl border p-5 shadow-sm" style="background:#FFF7DA; border-color:#E8DFA8;">
            <p class="text-xs font-bold uppercase tracking-wide" style="color:#7A5C00;">Aguardando pagamento</p>
            <p class="mt-2 text-2xl font-black" style="color:#7A5C00;">R$ {{ number_format($pendingAmount, 2, ',', '.') }}</p>
            <p class="mt-1 text-xs" style="color:#7A5C00;">{{ $pendingOrdersCount }} {{ $pendingOrdersCount === 1 ? 'pedido' : 'pedidos' }} ainda não confirmados</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[1.6fr_1fr] gap-6">
        <x-admin.card title="Faturamento — últimos 30 dias" description="Soma diária do valor confirmado por loja (pago via Mercado Pago ou confirmado manualmente).">
            @if($revenueTotal <= 0)
            <p class="text-sm text-gray-400 text-center py-10">Nenhum pagamento confirmado ainda. Assim que um pedido for pago, ele aparece aqui.</p>
            @else
            <div class="flex items-stretch gap-[3px]" style="height:160px;">
                @foreach($dailyRevenue as $i => $day)
                @php $pct = max(2, round(($day['total'] / $maxDaily) * 100)); @endphp
                <div class="flex-1 flex flex-col justify-end relative group" x-data="{ show: false }" @mouseenter="show = true" @mouseleave="show = false">
                    <div x-show="show" x-cloak
                         class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap z-10 shadow-lg pointer-events-none"
                         style="background:#3D3000; color:#F4E294;">
                        {{ $day['label'] }}: R$ {{ number_format($day['total'], 2, ',', '.') }}
                    </div>
                    <div class="w-full rounded-t transition-opacity group-hover:opacity-80"
                         style="height:{{ $pct }}%; min-height:2px; background:{{ $day['total'] > 0 ? '#E8A000' : '#F1E6AE' }};"></div>
                </div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between text-xs" style="color:#7A5C00;">
                <span>{{ $dailyRevenue[0]['label'] }}</span>
                <span>Hoje</span>
            </div>
            @endif
        </x-admin.card>

        <x-admin.card title="Top Lojas" description="Maior faturamento confirmado.">
            @if($topStores->isEmpty())
            <p class="text-sm text-gray-400 text-center py-10">Nenhuma venda confirmada ainda.</p>
            @else
            <div class="space-y-3">
                @foreach($topStores as $i => $store)
                <div class="flex items-center gap-3">
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-black flex-shrink-0" style="background:#F4E294; color:#3D3000;">{{ $i + 1 }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold truncate" style="color:#3D3000;">{{ $store->name }}</p>
                        <p class="text-xs" style="color:#7A5C00;">{{ $store->confirmed_orders_count }} {{ $store->confirmed_orders_count === 1 ? 'pedido' : 'pedidos' }}</p>
                    </div>
                    <p class="text-sm font-black flex-shrink-0" style="color:#3D3000;">R$ {{ number_format($store->confirmed_revenue, 2, ',', '.') }}</p>
                </div>
                @endforeach
            </div>
            @endif
        </x-admin.card>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
        @foreach([
            ['label' => 'Paginas', 'value' => $stats['pages'], 'href' => route('admin.pages.index'), 'tone' => 'cream', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['label' => 'Posts', 'value' => $stats['posts'], 'href' => route('admin.posts.index'), 'tone' => 'gold', 'icon' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z'],
            ['label' => 'Eventos', 'value' => $stats['events'], 'href' => route('admin.events.index'), 'tone' => 'green', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['label' => 'Produtos', 'value' => $stats['products'], 'href' => route('admin.categorias.index'), 'tone' => 'teal', 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
            ['label' => 'Pedidos', 'value' => $stats['orders'], 'href' => route('admin.pedidos.index'), 'tone' => 'brown', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 104 0M9 5a2 2 0 114 0'],
            ['label' => 'Clientes', 'value' => $stats['customers'], 'href' => route('admin.clientes.index'), 'tone' => 'blue', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
        ] as $stat)
        @php
            $tones = [
                'cream' => ['bg' => '#FFFDF0', 'iconBg' => '#F4E294', 'fg' => '#3D3000'],
                'gold'  => ['bg' => '#FFF7DA', 'iconBg' => '#F6D66B', 'fg' => '#7A5C00'],
                'green' => ['bg' => '#F0F8E8', 'iconBg' => '#DDEFC7', 'fg' => '#4A7030'],
                'teal'  => ['bg' => '#E7F0EF', 'iconBg' => '#CFE2DF', 'fg' => '#245C5A'],
                'brown' => ['bg' => '#F8EFE5', 'iconBg' => '#E8C7A8', 'fg' => '#5C3000'],
                'blue'  => ['bg' => '#EEF3FA', 'iconBg' => '#D7E4F5', 'fg' => '#345272'],
            ];
            $tone = $tones[$stat['tone']];
        @endphp
        <a href="{{ $stat['href'] }}" class="group rounded-2xl border p-4 shadow-sm transition-transform hover:-translate-y-0.5 hover:shadow-md"
           style="background:{{ $tone['bg'] }}; border-color:#E8DFA8;">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:{{ $tone['iconBg'] }}; color:{{ $tone['fg'] }};">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stat['icon'] }}"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-2xl font-black leading-tight" style="color:#3D3000;">{{ $stat['value'] }}</p>
                    <p class="text-sm font-medium truncate" style="color:#7A5C00;">{{ $stat['label'] }}</p>
                </div>
            </div>
        </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <x-admin.card title="Posts Recentes" description="Conteudos mais novos publicados no CMS.">
            @if($recentPosts->isEmpty())
            <p class="text-sm text-gray-400 text-center py-4">Nenhum post criado ainda.</p>
            @else
            <div class="space-y-3">
                @foreach($recentPosts as $post)
                <div class="flex items-center justify-between gap-3 py-2 border-b last:border-0" style="border-color:#F1E6AE;">
                    <div class="min-w-0">
                        <p class="text-sm font-bold truncate" style="color:#3D3000;">{{ $post->title }}</p>
                        <p class="text-xs" style="color:#7A5C00;">{{ $post->author->name }} · {{ $post->created_at->diffForHumans() }}</p>
                    </div>
                    <x-admin.badge :color="$post->status->color()">{{ $post->status->label() }}</x-admin.badge>
                </div>
                @endforeach
            </div>
            <div class="mt-4">
                <a href="{{ route('admin.posts.index') }}" class="text-sm font-bold hover:underline" style="color:#C47A00;">Ver todos -></a>
            </div>
            @endif
        </x-admin.card>

        <x-admin.card title="Proximos Eventos" description="Agenda ativa exibida para visitantes.">
            @if($upcomingEvents->isEmpty())
            <p class="text-sm text-gray-400 text-center py-4">Nenhum evento proximo.</p>
            @else
            <div class="space-y-3">
                @foreach($upcomingEvents as $event)
                <div class="flex items-start gap-3 py-2 border-b last:border-0" style="border-color:#F1E6AE;">
                    <div class="w-11 text-center flex-shrink-0 rounded-xl py-1.5" style="background:#F0F8E8;">
                        <p class="text-sm font-black" style="color:#3D3000;">{{ $event->start_date->format('d') }}</p>
                        <p class="text-xs font-semibold" style="color:#5C8A3C;">{{ $event->start_date->isoFormat('MMM') }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold truncate" style="color:#3D3000;">{{ $event->title }}</p>
                        <p class="text-xs" style="color:#7A5C00;">{{ $event->city }}@if($event->state) - {{ $event->state }}@endif</p>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4">
                <a href="{{ route('admin.events.index') }}" class="text-sm font-bold hover:underline" style="color:#C47A00;">Ver todos -></a>
            </div>
            @endif
        </x-admin.card>

        <x-admin.card title="Solicitacoes Pendentes" description="Novos lojistas aguardando revisao.">
            @if($recentSolicitacoes->isEmpty())
            <p class="text-sm text-gray-400 text-center py-4">Nenhuma solicitacao pendente.</p>
            @else
            <div class="space-y-3">
                @foreach($recentSolicitacoes as $s)
                <div class="py-2 border-b last:border-0" style="border-color:#F1E6AE;">
                    <p class="text-sm font-bold" style="color:#3D3000;">{{ $s->nome_loja }}</p>
                    <p class="text-xs" style="color:#7A5C00;">{{ $s->responsavel }} · {{ $s->created_at->diffForHumans() }}</p>
                </div>
                @endforeach
            </div>
            <div class="mt-4">
                <a href="{{ route('admin.lojistas.solicitacoes') }}" class="text-sm font-bold hover:underline" style="color:#C47A00;">
                    Revisar solicitacoes ->
                </a>
            </div>
            @endif
        </x-admin.card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_0.8fr] gap-6">
        <x-admin.card title="Pedidos Recentes" description="Movimento mais recente do marketplace.">
            @if($recentOrders->isEmpty())
            <p class="text-sm text-gray-400 text-center py-4">Nenhum pedido registrado ainda.</p>
            @else
            <div class="space-y-3">
                @foreach($recentOrders as $order)
                <div class="flex items-center justify-between gap-3 py-2 border-b last:border-0" style="border-color:#F1E6AE;">
                    <div class="min-w-0">
                        <p class="text-sm font-bold truncate" style="color:#3D3000;">Pedido {{ $order->reference }}</p>
                        <p class="text-xs" style="color:#7A5C00;">{{ $order->customer_name }} · {{ $order->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-black" style="color:#3D3000;">R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}</p>
                        <p class="text-xs" style="color:#7A5C00;">{{ $order->status->label() }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4">
                <a href="{{ route('admin.pedidos.index') }}" class="text-sm font-bold hover:underline" style="color:#C47A00;">Ver pedidos -></a>
            </div>
            @endif
        </x-admin.card>

        <x-admin.card title="Acoes Rapidas" description="Atalhos para as rotinas mais comuns.">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @can('cms.editar')
                <a href="{{ route('admin.pages.create') }}"><x-admin.button variant="secondary" class="w-full justify-center">Nova pagina</x-admin.button></a>
                <a href="{{ route('admin.banners.create') }}"><x-admin.button variant="secondary" class="w-full justify-center">Novo banner</x-admin.button></a>
                <a href="{{ route('admin.posts.create') }}"><x-admin.button variant="secondary" class="w-full justify-center">Novo post</x-admin.button></a>
                <a href="{{ route('admin.events.create') }}"><x-admin.button variant="secondary" class="w-full justify-center">Novo evento</x-admin.button></a>
                @endcan
                @can('cms.visualizar')
                <a href="{{ route('admin.media.index') }}"><x-admin.button variant="secondary" class="w-full justify-center">Biblioteca de midia</x-admin.button></a>
                @endcan
                @can('lojistas.visualizar')
                <a href="{{ route('admin.lojistas.solicitacoes') }}"><x-admin.button variant="primary" class="w-full justify-center">Ver lojistas</x-admin.button></a>
                @endcan
            </div>
        </x-admin.card>
    </div>
</div>
