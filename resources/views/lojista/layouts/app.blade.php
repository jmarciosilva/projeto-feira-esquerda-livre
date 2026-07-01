<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Minha Loja' }} — Feira Esquerda Livre</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans antialiased">

<div class="flex h-full min-h-screen">

    {{-- Sidebar --}}
    <aside id="lojista-sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-[#3D3000] text-white flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-300">

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-6 py-5 border-b border-[#5C4500]">
            <div class="w-9 h-9 rounded-lg overflow-hidden flex-shrink-0" style="background:#F4E294;">
                <div class="w-full h-full flex items-center justify-center">
                    <span class="text-[#3D3000] font-bold text-sm">FEL</span>
                </div>
            </div>
            <div class="overflow-hidden">
                <p class="text-sm font-bold text-white leading-tight truncate">Feira Esquerda Livre</p>
                <p class="text-xs truncate" style="color:#F4E294;">Painel do Lojista</p>
            </div>
        </div>

        {{-- Loja info --}}
        @if(auth()->user()->expositor)
        <div class="px-4 py-3 border-b border-[#5C4500]">
            <div class="flex items-center gap-3">
                @if(auth()->user()->expositor->logo_path)
                <img src="{{ Storage::url(auth()->user()->expositor->logo_path) }}"
                     alt="{{ auth()->user()->expositor->name }}"
                     class="w-9 h-9 rounded-full object-cover flex-shrink-0 border-2 border-[#F4E294]">
                @else
                <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 border-2 border-[#F4E294]" style="background:#5C4500;">
                    <span class="font-bold text-sm" style="color:#F4E294;">{{ strtoupper(substr(auth()->user()->expositor->name, 0, 1)) }}</span>
                </div>
                @endif
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->expositor->name }}</p>
                    <p class="text-xs text-gray-400">{{ auth()->user()->expositor->city }}@if(auth()->user()->expositor->state) / {{ auth()->user()->expositor->state }}@endif</p>
                </div>
            </div>
        </div>
        @endif

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">

            <a href="{{ route('lojista.dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('lojista.dashboard') ? 'text-[#3D3000] font-semibold' : 'text-[#D4B800] hover:text-white hover:bg-[#5C4500]' }}"
               style="{{ request()->routeIs('lojista.dashboard') ? 'background:#F4E294;' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Painel
            </a>

            <a href="{{ route('lojista.loja') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('lojista.loja') ? 'text-[#3D3000] font-semibold' : 'text-[#D4B800] hover:text-white hover:bg-[#5C4500]' }}"
               style="{{ request()->routeIs('lojista.loja') ? 'background:#F4E294;' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                Minha Loja
            </a>

            <a href="{{ route('lojista.produtos.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('lojista.produtos.*') ? 'text-[#3D3000] font-semibold' : 'text-[#D4B800] hover:text-white hover:bg-[#5C4500]' }}"
               style="{{ request()->routeIs('lojista.produtos.*') ? 'background:#F4E294;' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Meus Produtos
            </a>

            <a href="{{ route('lojista.feed.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('lojista.feed.*') ? 'text-[#3D3000] font-semibold' : 'text-[#D4B800] hover:text-white hover:bg-[#5C4500]' }}"
               style="{{ request()->routeIs('lojista.feed.*') ? 'background:#F4E294;' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h6m-8 8l4-4h8a3 3 0 003-3V7a3 3 0 00-3-3H7a3 3 0 00-3 3v6a3 3 0 003 3h1l-3 4z"/>
                </svg>
                Comunidade
            </a>

            <a href="{{ route('lojista.pedidos.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('lojista.pedidos.*') ? 'text-[#3D3000] font-semibold' : 'text-[#D4B800] hover:text-white hover:bg-[#5C4500]' }}"
               style="{{ request()->routeIs('lojista.pedidos.*') ? 'background:#F4E294;' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                Meus Pedidos
            </a>

            <a href="{{ route('lojista.exposicao.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('lojista.exposicao.*') ? 'text-[#3D3000] font-semibold' : 'text-[#D4B800] hover:text-white hover:bg-[#5C4500]' }}"
               style="{{ request()->routeIs('lojista.exposicao.*') ? 'background:#F4E294;' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Exposição na Home
            </a>

            <a href="{{ route('agenda.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-[#D4B800] hover:text-white hover:bg-[#5C4500] transition-colors">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Agenda de Feiras
            </a>

            <a href="{{ url('/') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-[#D4B800] hover:text-white hover:bg-[#5C4500] transition-colors">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                Ver Site
            </a>
        </nav>

        {{-- User --}}
        <div class="px-4 py-4 border-t border-[#5C4500]">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0" style="background:#F4E294;">
                    <span class="text-[#3D3000] text-sm font-bold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-400">Lojista</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-400 hover:text-white transition-colors" title="Sair">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Overlay mobile --}}
    <div id="lojista-overlay" class="fixed inset-0 z-40 bg-black/50 lg:hidden hidden" onclick="toggleLojistaSidebar()"></div>

    {{-- Main content --}}
    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen">

        {{-- Top bar --}}
        <header class="sticky top-0 z-30 bg-white border-b border-gray-200 px-4 lg:px-6 py-4 flex items-center gap-4">
            <button onclick="toggleLojistaSidebar()" class="lg:hidden text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="flex-1">
                <h1 class="text-lg font-semibold text-gray-900">{{ $title ?? 'Painel do Lojista' }}</h1>
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 p-4 lg:p-6">
            {{ $slot }}
        </main>

        <footer class="text-center text-xs text-gray-400 py-4 border-t border-gray-100">
            Feira Esquerda Livre &copy; {{ date('Y') }} — Área do Lojista
        </footer>
    </div>
</div>

@livewireScripts

<script>
function toggleLojistaSidebar() {
    const sidebar = document.getElementById('lojista-sidebar');
    const overlay = document.getElementById('lojista-overlay');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}
</script>

</body>
</html>
