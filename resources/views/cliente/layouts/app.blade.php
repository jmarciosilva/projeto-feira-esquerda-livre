<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Minha Conta' }} — Feira Esquerda Livre</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans antialiased">

<div class="flex h-full min-h-screen">

    {{-- Sidebar --}}
    <aside id="cliente-sidebar" class="fixed inset-y-0 left-0 z-50 w-64 text-white flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-300" style="background:#3D3000;">

        <div class="flex items-center gap-3 px-6 py-5 border-b" style="border-color:#5C4500;">
            <div class="w-9 h-9 rounded-lg overflow-hidden flex-shrink-0" style="background:#F4E294;">
                <div class="w-full h-full flex items-center justify-center">
                    <span class="text-[#3D3000] font-bold text-sm">FEL</span>
                </div>
            </div>
            <div class="overflow-hidden">
                <p class="text-sm font-bold text-white leading-tight truncate">Feira Esquerda Livre</p>
                <p class="text-xs truncate" style="color:#F4E294;">Minha Conta</p>
            </div>
        </div>

        <div class="px-4 py-3 border-b" style="border-color:#5C4500;">
            <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
            <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            <a href="{{ route('cliente.pedidos.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('cliente.pedidos.*') ? 'text-[#3D3000] font-semibold' : 'text-[#D4B800] hover:text-white hover:bg-[#5C4500]' }}"
               style="{{ request()->routeIs('cliente.pedidos.*') ? 'background:#F4E294;' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Meus Pedidos
            </a>

            <a href="{{ route('cliente.enderecos.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('cliente.enderecos.*') ? 'text-[#3D3000] font-semibold' : 'text-[#D4B800] hover:text-white hover:bg-[#5C4500]' }}"
               style="{{ request()->routeIs('cliente.enderecos.*') ? 'background:#F4E294;' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Meus Endereços
            </a>

            <a href="{{ route('cliente.ava.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('cliente.ava.*') ? 'text-[#3D3000] font-semibold' : 'text-[#D4B800] hover:text-white hover:bg-[#5C4500]' }}"
               style="{{ request()->routeIs('cliente.ava.*') ? 'background:#F4E294;' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                Meu Aprendizado
            </a>

            <a href="{{ url('/') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-[#D4B800] hover:text-white hover:bg-[#5C4500] transition-colors">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                Ver Site
            </a>
        </nav>

        <div class="px-4 py-4 border-t" style="border-color:#5C4500;">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center gap-2 text-sm text-gray-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Sair
                </button>
            </form>
        </div>
    </aside>

    <div id="cliente-overlay" class="fixed inset-0 z-40 bg-black/50 lg:hidden hidden" onclick="toggleClienteSidebar()"></div>

    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen">
        <header class="sticky top-0 z-30 bg-white border-b border-gray-200 px-4 lg:px-6 py-4 flex items-center gap-4">
            <button onclick="toggleClienteSidebar()" class="lg:hidden text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="flex-1">
                <h1 class="text-lg font-semibold text-gray-900">{{ $title ?? 'Minha Conta' }}</h1>
            </div>
        </header>

        <main class="flex-1 p-4 lg:p-6">
            {{ $slot }}
        </main>

        <footer class="text-center text-xs text-gray-400 py-4 border-t border-gray-100">
            Feira Esquerda Livre &copy; {{ date('Y') }} — Minha Conta
        </footer>
    </div>
</div>

@livewireScriptConfig

<script>
function toggleClienteSidebar() {
    const sidebar = document.getElementById('cliente-sidebar');
    const overlay = document.getElementById('cliente-overlay');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}
</script>

</body>
</html>
