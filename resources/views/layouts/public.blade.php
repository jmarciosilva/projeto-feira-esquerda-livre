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
                <a href="{{ route('feed.index') }}" class="hover:opacity-70 transition-opacity">Comunidade</a>
                <a href="{{ route('seja-um-expositor') }}" class="hover:opacity-70 transition-opacity">Seja Expositor</a>
            </div>
            <div class="flex items-center gap-2">
                @if(auth()->check())
                @php
                    $authUser      = auth()->user();
                    $authFirst     = explode(' ', $authUser->name)[0];
                    $authInitial   = strtoupper(mb_substr($authUser->name, 0, 1));
                    $authRoleLabel = match(true) {
                        $authUser->isAdmin() || $authUser->isEditor() => 'Administrador',
                        $authUser->isLojista()                        => 'Lojista',
                        default                                       => 'Cliente',
                    };
                    $authPanelUrl  = match(true) {
                        $authUser->isAdmin() || $authUser->isEditor() => route('admin.dashboard'),
                        $authUser->isLojista()                        => route('lojista.dashboard'),
                        default                                       => route('cliente.pedidos.index'),
                    };
                    $authPanelLabel = match(true) {
                        $authUser->isAdmin() || $authUser->isEditor() => 'Painel Admin',
                        $authUser->isLojista()                        => 'Minha Loja',
                        default                                       => 'Minha Conta',
                    };
                @endphp

                {{-- Avatar + nome + role (desktop) --}}
                <div class="hidden sm:flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0"
                         style="background:#3D3000;">
                        {{ $authInitial }}
                    </div>
                    <div class="hidden md:block leading-tight">
                        <p class="text-xs font-semibold leading-none" style="color:#3D3000;">{{ $authFirst }}</p>
                        <p class="text-[10px] mt-0.5" style="color:#5C3000;">{{ $authRoleLabel }}</p>
                    </div>
                </div>

                {{-- Botão do painel (desktop) --}}
                <a href="{{ $authPanelUrl }}"
                   class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition-opacity hover:opacity-80"
                   style="background:#3D3000;">
                    {{ $authPanelLabel }}
                </a>

                {{-- Sair (desktop) --}}
                <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                    @csrf
                    <button type="submit" title="Sair"
                            class="p-1.5 rounded-lg transition-opacity hover:opacity-60"
                            style="color:#3D3000;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>

                @else
                    <a href="{{ route('login') }}"
                       class="hidden sm:inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-opacity hover:opacity-80"
                       style="background:#3D3000; color:#F4E294;">
                        Entrar
                    </a>
                @endif

                {{-- Hamburguer --}}
                <button @click="open = !open" class="p-2 rounded-lg md:hidden" style="color:#3D3000;">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="open"  stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Menu mobile --}}
        <div x-show="open" x-transition class="md:hidden pb-3 space-y-1 border-t mt-1 pt-3" style="border-color:#D4B800;">
            <a href="{{ url('/') }}" class="block px-3 py-2 rounded-lg text-sm font-medium" style="color:#3D3000;">Home</a>
            <p class="px-3 pt-2 pb-1 text-xs font-bold uppercase tracking-wider" style="color:#9ca3af;">Catálogo</p>
            <a href="{{ url('/produtos') }}" class="block px-3 py-2 rounded-lg text-sm font-medium" style="color:#3D3000;">🛍️ Produtos</a>
            <a href="{{ url('/servicos') }}" class="block px-3 py-2 rounded-lg text-sm font-medium" style="color:#3D3000;">🎯 Serviços</a>
            <a href="{{ url('/cuidados') }}" class="block px-3 py-2 rounded-lg text-sm font-medium" style="color:#3D3000;">🌿 Cuidados & Bem Viver</a>
            <div class="border-t my-2" style="border-color:#D4B800;"></div>
            <a href="{{ route('agenda.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium" style="color:#3D3000;">Agenda de Feiras</a>
            <a href="{{ route('feed.index') }}" class="block px-3 py-2 rounded-lg text-sm font-medium" style="color:#3D3000;">Comunidade</a>
            <a href="{{ route('seja-um-expositor') }}" class="block px-3 py-2 rounded-lg text-sm font-medium" style="color:#3D3000;">Seja um Expositor</a>

            @if(auth()->check())
            <div class="border-t my-2" style="border-color:#D4B800;"></div>
            <div class="px-3 py-2 flex items-center gap-2">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                     style="background:#3D3000;">
                    {{ $authInitial }}
                </div>
                <div class="leading-tight">
                    <p class="text-xs font-semibold" style="color:#3D3000;">{{ $authFirst }}</p>
                    <p class="text-[10px]" style="color:#5C3000;">{{ $authRoleLabel }}</p>
                </div>
            </div>
            <a href="{{ $authPanelUrl }}" class="block px-3 py-2 rounded-lg text-sm font-semibold" style="color:#3D3000;">
                → {{ $authPanelLabel }}
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block w-full text-left px-3 py-2 rounded-lg text-sm font-medium text-red-600">
                    Sair da conta
                </button>
            </form>
            @else
            <div class="border-t my-2" style="border-color:#D4B800;"></div>
            <a href="{{ route('login') }}" class="block px-3 py-2 rounded-lg text-sm font-semibold" style="color:#3D3000;">
                Entrar na conta
            </a>
            @endif
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
                    <a href="{{ route('feed.index') }}" class="block hover:opacity-70">Comunidade</a>
                    <a href="{{ route('seja-um-expositor') }}" class="block hover:opacity-70">Seja um Expositor</a>
                </div>
            </div>
            <div>
                <p class="font-semibold text-sm mb-3" style="color:#3D3000;">Acesso</p>
                <div class="space-y-2 text-sm" style="color:#5C3000;">
                    @auth
                        @if(auth()->user()->isAdmin() || auth()->user()->isEditor())
                        <a href="{{ route('admin.dashboard') }}" class="block hover:opacity-70">Painel Admin</a>
                        @elseif(auth()->user()->isLojista())
                        <a href="{{ route('lojista.dashboard') }}" class="block hover:opacity-70">Minha Loja</a>
                        @else
                        <a href="{{ route('cliente.pedidos.index') }}" class="block hover:opacity-70">Minha Conta</a>
                        @endif
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
