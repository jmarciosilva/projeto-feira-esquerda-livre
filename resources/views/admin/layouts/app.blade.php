<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Admin' }} — Feira Esquerda Livre</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans antialiased">

<div class="flex h-full min-h-screen">

    {{-- Sidebar --}}
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-[#1a472a] text-white flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-300">

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-6 py-5 border-b border-[#2d6a4f]">
            <div class="w-9 h-9 bg-[#52b788] rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div class="overflow-hidden">
                <p class="text-sm font-bold text-white leading-tight truncate">Feira Esquerda Livre</p>
                <p class="text-xs text-[#b7e4c7] truncate">Painel Administrativo</p>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">

            <x-admin.nav-item href="{{ route('admin.dashboard') }}" icon="home" :active="request()->routeIs('admin.dashboard')">
                Dashboard
            </x-admin.nav-item>

            <div class="pt-4 pb-1 px-3">
                <p class="text-xs font-semibold text-[#52b788] uppercase tracking-wider">CMS</p>
            </div>

            <x-admin.nav-item href="{{ route('admin.settings.edit') }}" icon="cog" :active="request()->routeIs('admin.settings.edit')">
                Configurações
            </x-admin.nav-item>

            <x-admin.nav-item href="{{ route('admin.settings.mail') }}" icon="mail" :active="request()->routeIs('admin.settings.mail')">
                E-mail
            </x-admin.nav-item>

            <x-admin.nav-item href="{{ route('admin.settings.checkout') }}" icon="cog" :active="request()->routeIs('admin.settings.checkout')">
                Frete & Pagamento
            </x-admin.nav-item>

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

            <x-admin.nav-item href="{{ route('admin.expositores.index') }}" icon="store" :active="request()->routeIs('admin.expositores.*')">
                Expositores
            </x-admin.nav-item>

            <x-admin.nav-item href="{{ route('admin.categorias.index') }}" icon="tag" :active="request()->routeIs('admin.categorias.*')">
                Categorias
            </x-admin.nav-item>

            <div class="pt-4 pb-1 px-3">
                <p class="text-xs font-semibold text-[#52b788] uppercase tracking-wider">Lojistas</p>
            </div>

            @php $pendentes = \App\Models\LojistasSolicitacao::pendentes()->count(); @endphp
            <x-admin.nav-item
                href="{{ route('admin.lojistas.solicitacoes') }}"
                icon="users"
                :active="request()->routeIs('admin.lojistas.*')"
                :badge="$pendentes > 0 ? $pendentes : null">
                Solicitações
            </x-admin.nav-item>

            <div class="pt-4 pb-1 px-3">
                <p class="text-xs font-semibold text-[#52b788] uppercase tracking-wider">Marketplace</p>
            </div>

            <x-admin.nav-item href="{{ route('admin.pedidos.index') }}" icon="store" :active="request()->routeIs('admin.pedidos.*')">
                Pedidos
            </x-admin.nav-item>

            <div class="pt-4 pb-1 px-3">
                <p class="text-xs font-semibold text-[#52b788] uppercase tracking-wider">Comunidade</p>
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
            @canany(['usuarios.visualizar', 'permissoes.gerenciar'])
            <div class="pt-4 pb-1 px-3">
                <p class="text-xs font-semibold text-[#52b788] uppercase tracking-wider">Governança</p>
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
        <div class="px-4 py-4 border-t border-[#2d6a4f]">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-[#52b788] flex items-center justify-center flex-shrink-0">
                    <span class="text-white text-sm font-semibold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-[#b7e4c7] truncate">{{ auth()->user()->role->label() }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-[#b7e4c7] hover:text-white transition-colors" title="Sair">
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
        <header class="sticky top-0 z-30 bg-white border-b border-gray-200 px-4 lg:px-6 py-4 flex items-center gap-4">
            <button onclick="toggleSidebar()" class="lg:hidden text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="flex-1">
                <h1 class="text-lg font-semibold text-gray-900">{{ $title ?? 'Dashboard' }}</h1>
                @isset($breadcrumbs)
                    <nav class="text-sm text-gray-500 mt-0.5">{{ $breadcrumbs }}</nav>
                @endisset
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ url('/') }}" target="_blank" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Ver site
                </a>
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 p-4 lg:p-6">
            {{ $slot }}
        </main>

        <footer class="text-center text-xs text-gray-400 py-4 border-t border-gray-100">
            Feira Esquerda Livre &copy; {{ date('Y') }} — CMS Administrativo
        </footer>
    </div>
</div>

@livewireScripts

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}
</script>

</body>
</html>
