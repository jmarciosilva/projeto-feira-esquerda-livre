<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Feira Esquerda Livre')</title>
    <meta name="description" content="@yield('description', 'Feira Esquerda Livre — Marketplace solidário e progressista')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @include('partials.site-css')
</head>
<body class="font-sans antialiased" style="background:#fff;">

{{-- Navbar --}}
<nav x-data="{ open: false }" style="background-color: var(--amarelo, #F4E294);" class="sticky top-0 z-40 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="{{ url('/') }}" class="flex items-center gap-2 flex-shrink-0">
                @include('partials.site-logo')
                <span class="font-bold text-lg leading-tight hidden sm:block" style="color: var(--texto-escuro, #3D3000);">
                    {{ $settings->site_name ?? 'Feira Esquerda Livre' }}
                </span>
            </a>
            <div class="hidden md:flex items-center gap-5 text-sm font-medium" style="color:#3D3000;">
                <a href="{{ url('/') }}" class="hover:opacity-70 transition-opacity">Home</a>
                <a href="{{ url('/produtos') }}" class="hover:opacity-70 transition-opacity">🛍️ Produtos</a>
                <a href="{{ url('/servicos') }}" class="hover:opacity-70 transition-opacity">🎯 Serviços</a>
                <a href="{{ url('/cuidados') }}" class="hover:opacity-70 transition-opacity">🌿 Cuidados</a>
                <a href="{{ route('agenda.index') }}" class="hover:opacity-70 transition-opacity">Agenda</a>
                <a href="{{ route('seja-um-expositor') }}" class="hover:opacity-70 transition-opacity">Seja Expositor</a>
            </div>
            <div class="flex items-center gap-3">
                @if(auth()->check())
                    @if(auth()->user()->isLojista() || auth()->user()->isAdmin())
                    <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('lojista.dashboard') }}"
                       class="hidden sm:inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-white transition-colors"
                       style="background:#3D3000;">
                        Painel
                    </a>
                    @endif
                @else
                    <a href="{{ route('login') }}"
                       class="hidden sm:inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-colors hover:opacity-80"
                       style="background:#3D3000; color:#F4E294;">
                        Entrar
                    </a>
                @endif
                <button @click="open = !open" class="md:hidden p-2 rounded-lg" style="color:#3D3000;">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        <div x-show="open" x-transition class="md:hidden pb-3 space-y-1 border-t mt-1 pt-3" style="border-color:#D4B800;">
            <a href="{{ url('/') }}" class="block px-3 py-2 rounded-lg text-sm font-medium" style="color:#3D3000;">Home</a>
            <p class="px-3 pt-2 pb-1 text-xs font-bold uppercase tracking-wider" style="color:#9ca3af;">Catálogo</p>
            <a href="{{ url('/produtos') }}" class="block px-3 py-2 rounded-lg text-sm font-medium" style="color:#3D3000;">🛍️ Produtos</a>
            <a href="{{ url('/servicos') }}" class="block px-3 py-2 rounded-lg text-sm font-medium" style="color:#3D3000;">🎯 Serviços</a>
            <a href="{{ url('/cuidados') }}" class="block px-3 py-2 rounded-lg text-sm font-medium" style="color:#3D3000;">🌿 Cuidados & Bem Viver</a>
            <div class="border-t my-2" style="border-color:#D4B800;"></div>
            <a href="{{ route('agenda.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium" style="color:#3D3000;">Agenda de Feiras</a>
            <a href="{{ route('seja-um-expositor') }}" class="block px-3 py-2 rounded-lg text-sm font-medium" style="color:#3D3000;">Seja um Expositor</a>
        </div>
    </div>
</nav>

{{-- Page content --}}
@yield('content')

{{-- Footer --}}
<footer style="background-color: var(--amarelo, #F4E294);" class="mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
            <div>
                <p class="font-bold text-lg mb-2" style="color:#3D3000;">Feira Esquerda Livre</p>
                <p class="text-sm" style="color:#5C3000;">Marketplace solidário e progressista para lojistas e consumidores conscientes.</p>
            </div>
            <div>
                <p class="font-semibold text-sm mb-3" style="color:#3D3000;">Catálogo</p>
                <div class="space-y-2 text-sm" style="color:#5C3000;">
                    <a href="{{ url('/produtos') }}" class="block hover:opacity-70">🛍️ Produtos</a>
                    <a href="{{ url('/servicos') }}" class="block hover:opacity-70">🎯 Serviços</a>
                    <a href="{{ url('/cuidados') }}" class="block hover:opacity-70">🌿 Cuidados & Bem Viver</a>
                    <a href="{{ route('agenda.index') }}" class="block hover:opacity-70">Agenda de Feiras</a>
                    <a href="{{ route('seja-um-expositor') }}" class="block hover:opacity-70">Seja um Expositor</a>
                </div>
            </div>
            <div>
                <p class="font-semibold text-sm mb-3" style="color:#3D3000;">Acesso</p>
                <div class="space-y-2 text-sm" style="color:#5C3000;">
                    @auth
                    <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('lojista.dashboard') }}" class="block hover:opacity-70">Meu Painel</a>
                    @else
                    <a href="{{ route('login') }}" class="block hover:opacity-70">Entrar</a>
                    @endauth
                </div>
            </div>
        </div>
        <div class="mt-8 pt-6 text-center text-xs" style="border-top:1px solid #D4B800; color:#5C3000;">
            &copy; {{ date('Y') }} Feira Esquerda Livre. Todos os direitos reservados.
        </div>
    </div>
</footer>

@livewireScripts
</body>
</html>
