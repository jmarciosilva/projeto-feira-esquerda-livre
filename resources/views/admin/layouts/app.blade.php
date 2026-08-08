<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-[#FFF4CC]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Admin' }} — Feira Esquerda Livre</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans antialiased">

<div class="flex h-full min-h-screen">

    {{-- Sidebar --}}
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 text-white flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-300"
           style="background: linear-gradient(180deg, #3D3000 0%, #5C3000 58%, #3D3000 100%);">

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-6 py-5 border-b border-white/10">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm" style="background:#F4E294;">
                <svg class="w-5 h-5" style="color:#3D3000;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div class="overflow-hidden">
                <p class="text-sm font-extrabold text-white leading-tight truncate">Feira Esquerda Livre</p>
                <p class="text-xs truncate" style="color:#F4E294;">Painel Administrativo</p>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">

            <x-admin.nav-item href="{{ route('admin.dashboard') }}" icon="home" :active="request()->routeIs('admin.dashboard')">
                Dashboard
            </x-admin.nav-item>

            @canany(['configuracoes.visualizar', 'cms.visualizar'])
            <div class="pt-4 pb-1 px-3">
                <p class="text-xs font-bold uppercase tracking-wider" style="color:#F4E294;">CMS</p>
            </div>
            @endcanany

            @can('configuracoes.visualizar')
            <x-admin.nav-item href="{{ route('admin.settings.edit') }}" icon="cog" :active="request()->routeIs('admin.settings.edit')">
                Configurações
            </x-admin.nav-item>

            <x-admin.nav-item href="{{ route('admin.settings.mail') }}" icon="mail" :active="request()->routeIs('admin.settings.mail')">
                E-mail
            </x-admin.nav-item>

            <x-admin.nav-item href="{{ route('admin.settings.checkout') }}" icon="cog" :active="request()->routeIs('admin.settings.checkout')">
                Frete & Pagamento
            </x-admin.nav-item>

            @endcan

            @can('cms.visualizar')
            <x-admin.nav-item href="{{ route('admin.pages.index') }}" icon="document" :active="request()->routeIs('admin.pages.*')">
                Páginas
            </x-admin.nav-item>

            <x-admin.nav-item href="{{ route('admin.banners.index') }}" icon="photo" :active="request()->routeIs('admin.banners.*')">
                Banners
            </x-admin.nav-item>

            <x-admin.nav-item href="{{ route('admin.menus.index') }}" icon="menu" :active="request()->routeIs('admin.menus.*')">
                Menus
            </x-admin.nav-item>

            <x-admin.nav-item href="{{ route('admin.media.index') }}" icon="folder" :active="request()->routeIs('admin.media.*')">
                Mídias
            </x-admin.nav-item>

            <x-admin.nav-item href="{{ route('admin.posts.index') }}" icon="newspaper" :active="request()->routeIs('admin.posts.*')">
                Posts
            </x-admin.nav-item>

            <x-admin.nav-item href="{{ route('admin.events.index') }}" icon="calendar" :active="request()->routeIs('admin.events.*')">
                Eventos
            </x-admin.nav-item>

            <x-admin.nav-item href="{{ route('admin.expositores.index') }}" icon="store" :active="request()->routeIs('admin.expositores.index') || request()->routeIs('admin.expositores.edit')">
                Expositores
            </x-admin.nav-item>

            @can('expositores.visibilidade')
            <x-admin.nav-item href="{{ route('admin.expositores.visibilidade') }}" icon="photo" :active="request()->routeIs('admin.expositores.visibilidade')">
                Visibilidade na Home
            </x-admin.nav-item>
            @endcan

            <x-admin.nav-item href="{{ route('admin.categorias.index') }}" icon="tag" :active="request()->routeIs('admin.categorias.*')">
                Categorias
            </x-admin.nav-item>

            @endcan

            @can('lojistas.visualizar')
            <div class="pt-4 pb-1 px-3">
                <p class="text-xs font-bold uppercase tracking-wider" style="color:#F4E294;">Lojistas</p>
            </div>

            @php $pendentes = \App\Models\LojistasSolicitacao::pendentes()->count(); @endphp
            <x-admin.nav-item
                href="{{ route('admin.lojistas.solicitacoes') }}"
                icon="users"
                :active="request()->routeIs('admin.lojistas.*')"
                :badge="$pendentes > 0 ? $pendentes : null">
                Solicitações
            </x-admin.nav-item>

            @endcan

            @canany(['pedidos.visualizar', 'clientes.visualizar'])
            <div class="pt-4 pb-1 px-3">
                <p class="text-xs font-bold uppercase tracking-wider" style="color:#F4E294;">Marketplace</p>
            </div>

            @can('clientes.visualizar')
            <x-admin.nav-item href="{{ route('admin.clientes.index') }}" icon="users" :active="request()->routeIs('admin.clientes.*')">
                Clientes
            </x-admin.nav-item>
            @endcan

            @can('pedidos.visualizar')
            <x-admin.nav-item href="{{ route('admin.pedidos.index') }}" icon="store" :active="request()->routeIs('admin.pedidos.*')">
                Pedidos
            </x-admin.nav-item>
            @endcan

            @endcanany

            @can('email-marketing.gerenciar')
            <div class="pt-4 pb-1 px-3">
                <p class="text-xs font-bold uppercase tracking-wider" style="color:#F4E294;">Marketing</p>
            </div>
            <x-admin.nav-item href="{{ route('admin.email-marketing.index') }}" icon="mail" :active="request()->routeIs('admin.email-marketing.*')">
                Email Marketing
            </x-admin.nav-item>
            @endcan

            @can('customer_intelligence.visualizar')
            <div class="pt-4 pb-1 px-3">
                <p class="text-xs font-bold uppercase tracking-wider" style="color:#F4E294;">Inteligência</p>
            </div>
            <x-admin.nav-item href="{{ route('admin.customer-intelligence.dashboard') }}" icon="chart-bar" :active="request()->routeIs('admin.customer-intelligence.*')">
                Inteligência de Cliente
            </x-admin.nav-item>
            @endcan

            @can('feed.moderar')
            <div class="pt-4 pb-1 px-3">
                <p class="text-xs font-bold uppercase tracking-wider" style="color:#F4E294;">Comunidade</p>
            </div>

            @php
                try {
                    $feedReportes = \App\Models\FeedPost::whereHas('reports', fn ($q) => $q->where('status', 'pendente'))->count();
                } catch (\Throwable) {
                    $feedReportes = 0;
                }
            @endphp
            <x-admin.nav-item
                href="{{ route('admin.feed.reportes') }}"
                icon="users"
                :active="request()->routeIs('admin.feed.*')"
                :badge="$feedReportes > 0 ? $feedReportes : null">
                Moderação da Comunidade
            </x-admin.nav-item>
            @endcan

            @canany(['usuarios.visualizar', 'permissoes.gerenciar'])
            <div class="pt-4 pb-1 px-3">
                <p class="text-xs font-bold uppercase tracking-wider" style="color:#F4E294;">Governança</p>
            </div>

            @can('usuarios.visualizar')
            <x-admin.nav-item href="{{ route('admin.usuarios.index') }}" icon="users" :active="request()->routeIs('admin.usuarios.*')">
                Usuários Internos
            </x-admin.nav-item>
            @endcan

            @can('permissoes.gerenciar')
            <x-admin.nav-item href="{{ route('admin.permissoes.index') }}" icon="cog" :active="request()->routeIs('admin.permissoes.*')">
                Perfis de Acesso
            </x-admin.nav-item>
            @endcan
            @endcanany
        </nav>

        {{-- User --}}
        <div class="px-4 py-4 border-t border-white/10">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0" style="background:#F4E294;">
                    <span class="text-sm font-bold" style="color:#3D3000;">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs truncate" style="color:#F4E294;">{{ auth()->user()->role->label() }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="transition-colors hover:text-white" style="color:#F4E294;" title="Sair">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Overlay mobile --}}
    <div id="sidebar-overlay" class="fixed inset-0 z-40 bg-black/50 lg:hidden hidden" onclick="toggleSidebar()"></div>

    {{-- Main content --}}
    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen">

        {{-- Top bar --}}
        @php
            $helpRouteName = request()->route()?->getName();
            $help = $helpRouteName ? config("help.$helpRouteName") : null;
        @endphp
        <header class="sticky top-0 z-30 border-b px-4 lg:px-6 py-4 flex items-center gap-4"
                style="background:#FFFDF0; border-color:#E8DFA8;">
            <button onclick="toggleSidebar()" class="lg:hidden transition-colors" style="color:#5C3000;">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <h1 class="text-lg font-bold" style="color:#3D3000;">{{ $title ?? 'Dashboard' }}</h1>
                    @if($help)
                        <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('open-help', { detail: @js($help) }))"
                            class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 transition-opacity hover:opacity-80"
                            style="background:#F4E294; color:#3D3000;"
                            title="Ajuda desta tela" aria-label="Ajuda desta tela">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.5M12 17.5h.007v.008H12v-.008z"/>
                            </svg>
                        </button>
                    @endif
                </div>
                @isset($breadcrumbs)
                    <nav class="text-sm mt-0.5" style="color:#7A5C00;">{{ $breadcrumbs }}</nav>
                @endisset
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ url('/') }}" target="_blank" class="text-sm font-semibold flex items-center gap-1 transition-opacity hover:opacity-75" style="color:#5C3000;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Ver site
                </a>
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 p-4 lg:p-6" style="background:#FFF4CC;">
            {{ $slot }}
        </main>

        <footer class="min-h-12 flex items-center justify-center text-xs font-semibold border-t" style="color:#3D3000; border-color:#D6B85A; background:#F4E294;">
            Feira Esquerda Livre &copy; {{ date('Y') }} — CMS Administrativo
        </footer>
    </div>
</div>

<x-help-modal />

@livewireScriptConfig

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}
</script>

@stack('scripts')

</body>
</html>
