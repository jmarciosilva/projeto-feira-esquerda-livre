<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->site_name ?? config('app.name') }}</title>
    @if($settings->site_description)
        <meta name="description" content="{{ $settings->site_description }}">
    @endif
    @if($settings->favicon_path)
        <link rel="icon" href="{{ Storage::url($settings->favicon_path) }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @include('partials.site-css')
    <style>
        [x-cloak] { display: none !important; }

        html { scroll-behavior: smooth; }

        .btn-primary {
            background-color: var(--amarelo-escuro);
            color: #fff;
            font-weight: 700;
            padding: 0.75rem 1.75rem;
            border-radius: 0.5rem;
            transition: background-color 0.2s, transform 0.1s;
            display: inline-block;
            min-height: 48px;
            font-size: 1rem;
            line-height: 1.5;
        }
        .btn-primary:hover { background-color: var(--amarelo-hover); transform: translateY(-1px); }

        .btn-secondary {
            background-color: var(--verde);
            color: #fff;
            font-weight: 700;
            padding: 0.75rem 1.75rem;
            border-radius: 0.5rem;
            transition: background-color 0.2s, transform 0.1s;
            display: inline-block;
            min-height: 48px;
            font-size: 1rem;
            line-height: 1.5;
        }
        .btn-secondary:hover { background-color: var(--verde-hover); transform: translateY(-1px); }

        .btn-outline {
            border: 2px solid var(--amarelo-escuro);
            color: var(--amarelo-escuro);
            font-weight: 700;
            padding: 0.625rem 1.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
            display: inline-block;
            min-height: 48px;
            font-size: 1rem;
            line-height: 1.5;
        }
        .btn-outline:hover { background-color: var(--amarelo-escuro); color: #fff; }

        .section-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--texto-principal);
            margin-bottom: 0.5rem;
        }
        .section-subtitle {
            color: #6b7280;
            font-size: 1.0625rem;
            margin-bottom: 2.5rem;
        }

        .card-hover {
            transition: box-shadow 0.25s, transform 0.2s;
        }
        .card-hover:hover {
            box-shadow: 0 8px 32px rgba(0,0,0,0.13);
            transform: translateY(-3px);
        }

        /* Placeholder sem imagem */
        .img-placeholder {
            background: linear-gradient(135deg, #F4E294 0%, #E8A000 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3D3000;
            font-weight: 700;
            font-size: 0.875rem;
            text-align: center;
            padding: 1rem;
        }

        /* Navbar links */
        .nav-link {
            color: var(--texto-escuro);
            font-weight: 600;
            font-size: 0.9375rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            transition: background-color 0.15s, color 0.15s;
        }
        .nav-link:hover {
            background-color: rgba(61,48,0,0.1);
            color: #000;
        }

        .hero-carousel {
            min-height: clamp(420px, calc(100svh - 4rem), 620px);
        }

        .hero-content {
            padding-bottom: clamp(2.75rem, 8svh, 5rem);
        }

        @media (max-height: 760px) and (min-width: 768px) {
            .hero-carousel {
                min-height: min(520px, calc(100svh - 4rem));
            }

            .hero-content {
                padding-bottom: 2.5rem;
            }
        }
    </style>
</head>
<body class="bg-white text-gray-900 antialiased" style="font-size: 16px;">

{{-- ====================== NAVBAR ====================== --}}
<header x-data="{ mobileMenuOpen: false }" style="background-color: var(--amarelo, #F4E294);" class="sticky top-0 z-50 shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 md:h-18">

            {{-- Logo --}}
            <a href="{{ url('/') }}" class="flex items-center gap-2 shrink-0">
                @if($settings->logo_path)
                    <img src="{{ Storage::url($settings->logo_path) }}"
                         alt="{{ $settings->site_name }}"
                         class="h-10 w-auto object-contain">
                @else
                    <span class="font-black text-xl leading-tight" style="color: #3D3000;">
                        Feira<br><span style="color: #C47A00;">Esquerda Livre</span>
                    </span>
                @endif
            </a>

            {{-- Navegação desktop --}}
            @if($headerMenu && $headerMenu->items->isNotEmpty())
                <nav class="hidden md:flex items-center gap-1">
                    @foreach($headerMenu->items as $item)
                        @if($item->children->isNotEmpty())
                            <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                <button class="nav-link flex items-center gap-1">
                                    {{ $item->title }}
                                    <svg class="w-3.5 h-3.5 mt-px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="open" x-transition
                                     class="absolute left-0 top-full mt-1 w-52 rounded-lg shadow-xl py-1 z-50"
                                     style="background-color: #FDF8DC; border: 1px solid #E8A000;">
                                    @foreach($item->children as $child)
                                        <a href="{{ $child->url }}" target="{{ $child->target }}"
                                           class="block px-4 py-2.5 text-sm font-medium transition-colors"
                                           style="color: #3D3000;"
                                           onmouseover="this.style.backgroundColor='#F4E294'"
                                           onmouseout="this.style.backgroundColor=''">
                                            {{ $child->title }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <a href="{{ $item->url }}" target="{{ $item->target }}" class="nav-link">
                                {{ $item->title }}
                            </a>
                        @endif
                    @endforeach
                    <a href="{{ route('feed.index') }}" class="nav-link">Comunidade</a>
                </nav>
            @else
                <nav class="hidden md:flex items-center gap-1">
                    <a href="#" class="nav-link">Início</a>
                    <a href="#agenda" class="nav-link">Agenda</a>
                    <a href="#expositores" class="nav-link">Expositores</a>
                    <a href="#marketplace" class="nav-link">Marketplace</a>
                    <a href="{{ route('feed.index') }}" class="nav-link">Comunidade</a>
                    <a href="#noticias" class="nav-link">Notícias</a>
                    <a href="#sobre" class="nav-link">Sobre</a>
                    <a href="{{ route('contato') }}" class="nav-link">Contato</a>
                </nav>
            @endif

            {{-- Botão WhatsApp desktop + hambúrguer mobile --}}
            <div class="flex items-center gap-2 lg:gap-3 shrink-0">
                @auth
                @php
                    $wUser = auth()->user();
                    [$wPanelUrl, $wPanelLabel] = match(true) {
                        $wUser->isAdmin() || $wUser->isEditor() => [route('admin.dashboard'),         'Painel Admin'],
                        $wUser->isLojista()                     => [route('lojista.dashboard'),        'Minha Loja'],
                        default                                 => [route('cliente.pedidos.index'),    'Minha Conta'],
                    };
                @endphp
                    <a href="{{ $wPanelUrl }}"
                       class="hidden md:inline-flex items-center justify-center gap-2 rounded-full px-3.5 text-sm font-semibold transition-colors"
                       style="background:#E7F0EF; color:#245C5A; border:1px solid #B8D2CE; height:40px;"
                       onmouseover="this.style.backgroundColor='#D6E6E4'"
                       onmouseout="this.style.backgroundColor='#E7F0EF'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        {{ $wPanelLabel }}
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="hidden md:inline-flex items-center justify-center gap-2 rounded-full px-3.5 text-sm font-semibold transition-colors"
                       style="background:#E7F0EF; color:#245C5A; border:1px solid #B8D2CE; height:40px;"
                       onmouseover="this.style.backgroundColor='#D6E6E4'"
                       onmouseout="this.style.backgroundColor='#E7F0EF'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Entrar
                    </a>
                @endauth
                <div class="hidden md:block">
                    <livewire:cart-drawer />
                </div>
                <a href="{{ route('seja-um-expositor') }}"
                   class="hidden md:inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-full px-4 text-sm font-bold leading-none transition-all duration-150"
                   style="background-color: var(--verde); color: #fff; min-width: 132px; height: 42px; box-shadow: 0 2px 8px rgba(61,48,0,0.16);"
                   onmouseover="this.style.backgroundColor='var(--verde-hover)'" onmouseout="this.style.backgroundColor='var(--verde)'">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.3" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75c1.6-1.2 3.84-1.04 5.26.38l.61.61a4.05 4.05 0 010 5.73l-5.17 5.16a1 1 0 01-1.4 0l-5.17-5.16a4.05 4.05 0 010-5.73l.61-.61A4.06 4.06 0 0112 6.75z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.5 10.5h5"/>
                    </svg>
                    <span>Seja Expositor</span>
                </a>
                @if($settings->whatsapp)
                    <a href="https://wa.me/55{{ preg_replace('/\D/', '', $settings->whatsapp) }}"
                       target="_blank"
                       aria-label="Falar no WhatsApp"
                       title="Falar no WhatsApp"
                       class="hidden md:flex items-center justify-center rounded-full transition-all duration-150"
                       style="background-color: var(--verde); width: 34px; height: 34px; box-shadow: 0 1px 3px rgba(0,0,0,0.15);"
                       onmouseover="this.style.backgroundColor='var(--verde-hover)'" onmouseout="this.style.backgroundColor='var(--verde)'">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                        </svg>
                    </a>
                @endif

                {{-- Hambúrguer mobile --}}
                <button class="md:hidden w-12 h-12 rounded-lg flex items-center justify-center"
                        style="color: #3D3000;"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        :aria-expanded="mobileMenuOpen.toString()"
                        aria-label="Abrir menu">
                    <svg x-show="!mobileMenuOpen" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-cloak x-show="mobileMenuOpen" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

        </div>
    </div>

    {{-- Menu mobile --}}
    <div x-cloak
         x-show="mobileMenuOpen"
         x-transition
         class="md:hidden border-t shadow-xl max-h-[calc(100vh-64px)] overflow-y-auto"
         style="background-color: #FDF8DC; border-color: #E8A000;">

        @if($headerMenu && $headerMenu->items->isNotEmpty())
            @foreach($headerMenu->items as $item)
                <a href="{{ $item->url }}" target="{{ $item->target }}"
                   class="flex items-center min-h-14 px-5 py-3.5 text-base font-semibold border-b transition-colors"
                   style="color: #3D3000; border-color: #F4E294;"
                   onmouseover="this.style.backgroundColor='#F4E294'"
                   onmouseout="this.style.backgroundColor=''">
                    {{ $item->title }}
                </a>
                @foreach($item->children as $child)
                    <a href="{{ $child->url }}" target="{{ $child->target }}"
                       class="flex items-center min-h-12 pl-10 pr-5 py-3 text-sm font-medium border-b transition-colors"
                       style="color: #5c6b00; border-color: #F4E294;"
                       onmouseover="this.style.backgroundColor='#F4E294'"
                       onmouseout="this.style.backgroundColor=''">
                        {{ $child->title }}
                    </a>
                @endforeach
            @endforeach

            <a href="{{ route('feed.index') }}"
               class="flex items-center min-h-14 px-5 py-3.5 text-base font-semibold border-b transition-colors"
               style="color: #3D3000; border-color: #F4E294;"
               onmouseover="this.style.backgroundColor='#F4E294'"
               onmouseout="this.style.backgroundColor=''">
                Comunidade
            </a>
        @else
            @foreach(['Início' => '#', 'Agenda' => '#agenda', 'Expositores' => '#expositores', 'Marketplace' => '#marketplace', 'Comunidade' => route('feed.index'), 'Notícias' => '#noticias', 'Sobre' => '#sobre', 'Contato' => route('contato')] as $label => $href)
                <a href="{{ $href }}"
                   class="flex items-center min-h-14 px-5 py-3.5 text-base font-semibold border-b transition-colors"
                   style="color: #3D3000; border-color: #F4E294;"
                   onmouseover="this.style.backgroundColor='#F4E294'"
                   onmouseout="this.style.backgroundColor=''">
                    {{ $label }}
                </a>
            @endforeach
        @endif

        @auth
            <a href="{{ $wPanelUrl }}"
               class="flex items-center gap-3 min-h-14 px-5 py-3.5 text-base font-semibold border-b transition-colors"
               style="color: #3D3000; border-color: #F4E294;"
               onmouseover="this.style.backgroundColor='#F4E294'"
               onmouseout="this.style.backgroundColor=''">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                {{ $wPanelLabel }}
            </a>
        @else
            <a href="{{ route('login') }}"
               class="flex items-center gap-3 min-h-14 px-5 py-3.5 text-base font-semibold border-b transition-colors"
               style="color: #3D3000; border-color: #F4E294;"
               onmouseover="this.style.backgroundColor='#F4E294'"
               onmouseout="this.style.backgroundColor=''">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Entrar
            </a>
        @endauth
        <div class="px-5 py-4 border-b" style="border-color: #F4E294;">
            <a href="{{ route('seja-um-expositor') }}"
               class="inline-flex w-full min-h-12 items-center justify-center gap-2 rounded-lg px-4 text-base font-bold text-white transition-colors"
               style="background-color: var(--verde); box-shadow: 0 2px 8px rgba(61,48,0,0.14);">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.3" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75c1.6-1.2 3.84-1.04 5.26.38l.61.61a4.05 4.05 0 010 5.73l-5.17 5.16a1 1 0 01-1.4 0l-5.17-5.16a4.05 4.05 0 010-5.73l.61-.61A4.06 4.06 0 0112 6.75z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.5 10.5h5"/>
                </svg>
                <span>Seja Expositor</span>
            </a>
        </div>

        @if($settings->whatsapp)
            <div class="px-5 py-4">
                <a href="https://wa.me/55{{ preg_replace('/\D/', '', $settings->whatsapp) }}"
                   target="_blank"
                   class="inline-flex w-full min-h-12 items-center justify-center gap-2 rounded-lg px-4 text-base font-bold text-white transition-colors"
                   style="background-color: var(--verde); box-shadow: 0 2px 8px rgba(61,48,0,0.14);">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    Falar no WhatsApp
                </a>
            </div>
        @endif
    </div>
</header>

{{-- ====================== SEÇÃO 1: CAROUSEL ====================== --}}
@if($banners->isNotEmpty())
    <section
        x-data="{
            current: 0,
            total: {{ $banners->count() }},
            timer: null,
            start() { this.timer = setInterval(() => this.next(), 5000); },
            next() { this.current = (this.current + 1) % this.total; },
            prev() { this.current = (this.current - 1 + this.total) % this.total; },
            go(i)  { this.current = i; clearInterval(this.timer); this.start(); }
        }"
        x-init="start()"
        class="hero-carousel relative overflow-hidden bg-gray-900">

        @foreach($banners as $i => $banner)
            <div x-show="current === {{ $i }}"
                 x-transition:enter="transition-opacity duration-700"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="absolute inset-0">

                @if($banner->image_path)
                    <img src="{{ Storage::url($banner->image_path) }}"
                         alt="{{ $banner->title }}"
                         class="w-full h-full object-cover {{ $banner->mobile_image_path ? 'hidden sm:block' : '' }}"
                         loading="{{ $i === 0 ? 'eager' : 'lazy' }}">
                    @if($banner->mobile_image_path)
                        <img src="{{ Storage::url($banner->mobile_image_path) }}"
                             alt="{{ $banner->title }}"
                             class="w-full h-full object-cover sm:hidden"
                             loading="{{ $i === 0 ? 'eager' : 'lazy' }}">
                    @endif
                @else
                    {{-- Placeholder visual com informação para o admin --}}
                    <div class="w-full h-full flex items-center justify-center"
                         style="background: linear-gradient(135deg, #3D3000 0%, #7A5C00 50%, #C47A00 100%);">
                        <div class="text-center text-white px-6">
                            <div class="text-5xl mb-4">🎪</div>
                            <p class="text-sm font-medium opacity-75 mb-2">
                                Banner {{ $i + 1 }} — Sem imagem cadastrada
                            </p>
                            <p class="text-xs opacity-50">
                                Desktop recomendado: 1920×700px &nbsp;|&nbsp; Mobile: 1080×1350px
                            </p>
                        </div>
                    </div>
                @endif

                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>
                <div class="absolute inset-0 flex items-end">
                    <div class="hero-content max-w-7xl mx-auto px-6 lg:px-8 w-full">
                        <h2 class="text-3xl sm:text-4xl lg:text-5xl xl:text-[3.5rem] font-black text-white leading-[1.04] max-w-2xl drop-shadow-lg">
                            {{ $banner->title }}
                        </h2>
                        @if($banner->subtitle)
                            <p class="mt-3 text-base sm:text-lg lg:text-xl text-gray-100 max-w-xl drop-shadow leading-relaxed">
                                {{ $banner->subtitle }}
                            </p>
                        @endif
                        @if($banner->button_text && $banner->button_link)
                            <a href="{{ $banner->button_link }}" class="mt-5 btn-primary inline-flex items-center justify-center whitespace-nowrap text-base lg:text-lg">
                                {{ $banner->button_text }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        @if($banners->count() > 1)
            <button @click="prev()"
                    class="absolute left-3 sm:left-5 top-1/2 -translate-y-1/2 w-11 h-11 sm:w-12 sm:h-12 rounded-full flex items-center justify-center transition-colors z-10"
                    style="background: rgba(244,226,148,0.85); color: #3D3000;"
                    aria-label="Banner anterior">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <button @click="next()"
                    class="absolute right-3 sm:right-5 top-1/2 -translate-y-1/2 w-11 h-11 sm:w-12 sm:h-12 rounded-full flex items-center justify-center transition-colors z-10"
                    style="background: rgba(244,226,148,0.85); color: #3D3000;"
                    aria-label="Próximo banner">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            <div class="absolute bottom-5 left-1/2 -translate-x-1/2 flex gap-2 z-10">
                @foreach($banners as $i => $banner)
                    <button @click="go({{ $i }})"
                            :class="current === {{ $i }} ? 'w-7' : 'w-3 bg-white/60'"
                            class="h-3 rounded-full transition-all duration-300"
                            :style="current === {{ $i }} ? 'background-color: #F4E294;' : ''"
                            aria-label="Ir para banner {{ $i + 1 }}">
                    </button>
                @endforeach
            </div>
        @endif
    </section>

@else
    <section class="flex items-center" style="min-height: 480px; background: linear-gradient(135deg, #3D3000 0%, #7A5C00 50%, #C47A00 100%);">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 w-full text-center">
            <div class="text-6xl mb-6">🎪</div>
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black leading-tight text-white">
                {{ $settings->site_name ?? 'Feira Esquerda Livre' }}
            </h1>
            @if($settings->site_description)
                <p class="mt-5 text-xl text-yellow-100 max-w-2xl mx-auto">{{ $settings->site_description }}</p>
            @endif
            <a href="#agenda" class="mt-8 btn-primary inline-block text-lg">Ver Próximas Feiras</a>
        </div>
    </section>
@endif

<main>

{{-- ====================== SEÇÃO 2: QUEM SOMOS ====================== --}}
<section id="sobre" class="py-16 md:py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row items-center gap-10 lg:gap-16">

            {{-- Imagem --}}
            <div class="w-full md:w-2/5 shrink-0">
                <div class="rounded-2xl overflow-hidden shadow-xl aspect-square md:aspect-[4/3]">
                    @if($settings->sobre_imagem_path)
                        <img src="{{ Storage::url($settings->sobre_imagem_path) }}"
                             alt="{{ $settings->sobre_titulo ?? 'Quem Somos' }}"
                             class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex flex-col items-center justify-center p-8 text-center"
                             style="background: linear-gradient(135deg, #F4E294 0%, #E8A000 100%);">
                            <div class="text-8xl mb-4">✊</div>
                            <p class="font-black text-2xl" style="color: #3D3000;">Feira Esquerda Livre</p>
                            <p class="mt-2 text-sm font-medium" style="color: #7A5C00;">Economia Solidária &amp; Cultura Popular</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Texto --}}
            <div class="w-full md:w-3/5">
                <div class="w-12 h-1.5 rounded-full mb-4" style="background-color: #E8A000;"></div>
                <h2 class="text-3xl sm:text-4xl font-black leading-tight mb-5" style="color: #1A1A1A;">
                    {{ $settings->sobre_titulo ?: 'Quem Somos' }}
                </h2>
                <div class="space-y-4 text-gray-600 text-lg leading-relaxed">
                    @if($settings->sobre_texto)
                        @foreach(array_filter(array_map('trim', explode("\n\n", $settings->sobre_texto))) as $paragrafo)
                            <p>{{ $paragrafo }}</p>
                        @endforeach
                    @else
                        <p>
                            A <strong style="color: #3D3000;">Feira Esquerda Livre</strong> é um movimento cultural e de economia solidária que reúne artesãos, artistas, produtores agroecológicos e empreendedores populares de todo o Brasil.
                        </p>
                        <p>
                            Acreditamos que o comércio pode ser um ato político e transformador. Aqui, cada compra fortalece uma família, valoriza um saber-fazer ancestral e contribui para uma economia mais justa e igualitária.
                        </p>
                        <p>
                            Com edições em diversas cidades, a feira celebra a diversidade cultural brasileira, promovendo inclusão, comunidade e resistência popular.
                        </p>
                    @endif
                </div>
                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="#agenda" class="btn-primary">Ver Próximas Feiras</a>
                    <a href="#expositores-cta" class="btn-outline">Conheça o Projeto</a>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ====================== SEÇÃO 2.5: TRÊS EIXOS ====================== --}}
<section id="eixos" class="py-16 md:py-24" style="background-color: #FDF8DC;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-10">
            <div class="w-12 h-1.5 rounded-full mx-auto mb-4" style="background-color: #E8A000;"></div>
            <h2 class="section-title inline-flex items-center justify-center gap-3">
                @if($settings->logo_path)
                    <img src="{{ Storage::url($settings->logo_path) }}"
                         alt=""
                         class="h-9 w-auto object-contain"
                         loading="lazy">
                @else
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-black"
                          style="background:#F4E294; color:#3D3000;">F</span>
                @endif
                <span>Explore a Feira por Categorias</span>
            </h2>
            <p class="section-subtitle">Tudo que a feira oferece, organizado para você</p>
        </div>

        @php
            $eixosDescricao = [
                'produto' => 'Artesanato, alimentos, livros e arte direto de quem produz com as próprias mãos.',
                'servico' => 'Design, comunicação, aulas e consultorias para fortalecer outros coletivos e negócios.',
                'cuidado' => 'Massagens, terapias e práticas integrativas para o bem viver de corpo e mente.',
            ];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 lg:gap-8">
            @foreach(\App\Enums\ItemType::cases() as $eixo)
            <a href="{{ route('catalogo.' . $eixo->value) }}"
               class="bg-white rounded-2xl p-8 shadow-md card-hover border text-center flex flex-col items-center"
               style="border-color: #F0D060;">
                <span class="text-5xl mb-4">{{ $eixo->emoji() }}</span>
                <h3 class="text-xl font-black mb-2" style="color: #1A1A1A;">{{ $eixo->label() }}</h3>
                <p class="text-sm text-gray-500 mb-6 leading-relaxed">{{ $eixosDescricao[$eixo->value] }}</p>
                <span class="btn-outline text-sm py-2.5 px-5 mt-auto">Explorar {{ $eixo->label() }}</span>
            </a>
            @endforeach
        </div>

    </div>
</section>

{{-- ====================== SEÇÃO 3: PRÓXIMAS FEIRAS ====================== --}}
<section id="agenda" class="py-12 md:py-16 xl:py-20" style="background-color: #FDF8DC;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-8">
            <div class="w-12 h-1.5 rounded-full mx-auto mb-4" style="background-color: #E8A000;"></div>
            <h2 class="section-title inline-flex items-center justify-center gap-3">
                @if($settings->logo_path)
                    <img src="{{ Storage::url($settings->logo_path) }}"
                         alt=""
                         class="h-9 w-auto object-contain"
                         loading="lazy">
                @else
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-black"
                          style="background:#F4E294; color:#3D3000;">F</span>
                @endif
                <span>Próximas Feiras</span>
            </h2>
            <p class="section-subtitle">Encontre uma edição perto de você</p>
        </div>

        @if($upcomingEvents->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 lg:gap-6">
                @foreach($upcomingEvents as $event)
                    <article class="bg-white rounded-2xl overflow-hidden shadow-md card-hover border flex flex-col"
                             style="border-color: #F4E294;">

                        {{-- Imagem --}}
                        <div class="relative h-40 md:h-44 xl:h-48 overflow-hidden"
                             style="background: linear-gradient(135deg, #FFF8DC 0%, #F4E294 100%);">
                            @if($event->image_path)
                                <img src="{{ Storage::url($event->image_path) }}"
                                     alt="{{ $event->title }}"
                                     class="w-full h-full object-contain p-2"
                                     loading="lazy">
                            @else
                                <div class="w-full h-full img-placeholder flex-col gap-2">
                                    <span class="text-4xl">🎪</span>
                                    <span>{{ $event->city }}, {{ $event->state }}</span>
                                </div>
                            @endif

                            {{-- Badge data --}}
                            <div class="absolute top-3 left-3 px-3 py-1.5 rounded-lg text-sm font-bold shadow"
                                 style="background-color: #F4E294; color: #3D3000;">
                                📅 {{ $event->start_date->format('d/m/Y') }}
                            </div>
                        </div>

                        <div class="p-5 flex flex-1 flex-col">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-sm font-semibold px-2.5 py-1 rounded-full"
                                      style="background-color: #FDF8DC; color: #7A5C00;">
                                    📍 {{ $event->city }}@if($event->state), {{ $event->state }}@endif
                                </span>
                            </div>
                            <h3 class="text-base lg:text-lg font-bold leading-snug mb-2 line-clamp-2" style="color: #1A1A1A;">
                                {{ $event->title }}
                            </h3>
                            @if($event->address)
                                <p class="text-sm text-gray-500 mb-4 flex items-start gap-1 line-clamp-2">
                                    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    {{ $event->address }}
                                </p>
                            @endif
                            <a href="{{ route('agenda.show', $event->slug) }}"
                               class="btn-primary w-full text-center text-sm py-3 mt-auto">
                                Saiba Mais
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="text-center py-16">
                <div class="text-6xl mb-4">📅</div>
                <p class="text-xl font-semibold" style="color: #7A5C00;">Em breve novos eventos!</p>
                <p class="text-gray-500 mt-2">Fique de olho na nossa agenda.</p>
            </div>
        @endif

    </div>
</section>

{{-- ====================== SEÇÃO 4: EXPOSITORES ====================== --}}
<section id="expositores" class="py-12 md:py-16 xl:py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-8">
            <div class="w-12 h-1.5 rounded-full mx-auto mb-4" style="background-color: #E8A000;"></div>
            <h2 class="section-title inline-flex items-center justify-center gap-3">
                @if($settings->logo_path)
                    <img src="{{ Storage::url($settings->logo_path) }}"
                         alt=""
                         class="h-9 w-auto object-contain"
                         loading="lazy">
                @else
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-black"
                          style="background:#F4E294; color:#3D3000;">F</span>
                @endif
                <span>Expositores em Destaque</span>
            </h2>
            <p class="section-subtitle">Conheça quem faz a feira acontecer</p>
        </div>

        @if($featuredExpositores->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 lg:gap-6">
                @foreach($featuredExpositores as $expositor)
                    <a href="{{ route('loja.show', $expositor->slug) }}"
                       class="bg-white rounded-2xl overflow-hidden shadow-md card-hover border flex flex-col"
                       style="border-color: #F0D060;">

                        {{-- Imagem de capa --}}
                        <div class="relative h-36 md:h-40 xl:h-44 overflow-hidden">
                            @if($expositor->image_path)
                                <img src="{{ Storage::url($expositor->image_path) }}"
                                     alt="{{ $expositor->name }}"
                                     class="w-full h-full object-cover transition-transform duration-300 hover:scale-105"
                                     loading="lazy">
                            @else
                                <div class="w-full h-full img-placeholder flex-col gap-2">
                                    <span class="text-4xl">🏪</span>
                                    <span class="text-sm font-medium">{{ $expositor->name }}</span>
                                </div>
                            @endif

                            {{-- Logo sobreposto --}}
                            @if($expositor->logo_path)
                                <div class="absolute bottom-3 left-4 w-12 h-12 rounded-full overflow-hidden border-2 shadow-md"
                                     style="border-color: #F4E294; background: white;">
                                    <img src="{{ Storage::url($expositor->logo_path) }}"
                                         alt="{{ $expositor->name }}"
                                         class="w-full h-full object-cover"
                                         loading="lazy">
                                </div>
                            @endif
                        </div>

                        <div class="p-4 flex flex-col flex-1">
                            <h3 class="text-base font-bold leading-snug mb-1 line-clamp-2" style="color: #1A1A1A;">
                                {{ $expositor->name }}
                            </h3>
                            @if($expositor->city || $expositor->state)
                                <p class="text-xs text-gray-500 mb-3 flex items-center gap-1 line-clamp-1">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    {{ $expositor->city }}@if($expositor->city && $expositor->state), @endif{{ $expositor->state }}
                                </p>
                            @endif
                            <span class="btn-outline text-xs py-2 px-3 w-full mt-auto text-center" style="min-height: 36px; font-size: 0.8rem;">
                                Visitar Loja
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                @if($settings->logo_path)
                    <img src="{{ Storage::url($settings->logo_path) }}"
                         alt=""
                         class="h-12 w-auto object-contain mx-auto mb-4 opacity-80"
                         loading="lazy">
                @else
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-full text-base font-black mb-4"
                         style="background:#F4E294; color:#3D3000;">F</div>
                @endif
                <p class="text-xl font-semibold" style="color: #7A5C00;">Em breve, expositores em destaque!</p>
            </div>
        @endif

    </div>
</section>

{{-- ====================== SEÇÃO 5: PRODUTOS ====================== --}}
<section id="marketplace" class="py-12 md:py-16 xl:py-20" style="background-color: #FDF8DC;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-8">
            <div class="w-12 h-1.5 rounded-full mx-auto mb-4" style="background-color: #E8A000;"></div>
            <h2 class="section-title inline-flex items-center justify-center gap-3">
                @if($settings->logo_path)
                    <img src="{{ Storage::url($settings->logo_path) }}"
                         alt=""
                         class="h-9 w-auto object-contain"
                         loading="lazy">
                @else
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-black"
                          style="background:#F4E294; color:#3D3000;">F</span>
                @endif
                <span>Produtos em Destaque</span>
            </h2>
            <p class="section-subtitle">Artesanato, alimentos, livros e arte direto de quem produz com as próprias mãos</p>
        </div>

        @if($featuredProducts->isNotEmpty())
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 lg:gap-5">
                @foreach($featuredProducts as $product)
                    @if($product->expositor)
                    <a href="{{ route('loja.produto', [$product->expositor->slug, $product->slug, 'return_to' => url()->full()]) }}"
                       class="bg-white rounded-2xl overflow-hidden shadow-sm card-hover border flex flex-col"
                       style="border-color: #F0D060;">
                    @else
                    <article class="bg-white rounded-2xl overflow-hidden shadow-sm card-hover border flex flex-col"
                             style="border-color: #F0D060;">
                    @endif

                        {{-- Imagem quadrada --}}
                        <div class="aspect-square overflow-hidden">
                            @if($product->image_path)
                                <img src="{{ Storage::url($product->image_path) }}"
                                     alt="{{ $product->name }}"
                                     class="w-full h-full object-cover transition-transform duration-300 hover:scale-105"
                                     loading="lazy">
                            @else
                                <div class="w-full h-full img-placeholder flex-col gap-1">
                                    <span class="text-3xl">🛍</span>
                                    <span class="text-xs">Sem imagem</span>
                                </div>
                            @endif
                        </div>

                        <div class="p-3 sm:p-4 flex flex-col flex-1">
                            <h3 class="text-sm sm:text-base font-bold leading-snug mb-1 flex-1 line-clamp-2" style="color: #1A1A1A;">
                                {{ $product->name }}
                            </h3>
                            @if($product->price)
                                <p class="text-base sm:text-lg font-black my-2" style="color: #C47A00;">
                                    R$ {{ number_format($product->price, 2, ',', '.') }}
                                </p>
                            @endif
                            @if($product->expositor)
                                <p class="text-xs text-gray-500 mb-3 flex items-center gap-1 line-clamp-1">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    {{ $product->expositor->name }}
                                </p>
                            @endif
                            <span class="btn-primary text-xs py-2 px-3 text-center w-full" style="min-height: 36px; font-size: 0.8125rem;">
                                Ver Produto
                            </span>
                        </div>

                    @if($product->expositor)
                    </a>
                    @else
                    </article>
                    @endif
                @endforeach
            </div>
            <div class="text-center mt-8">
                <a href="{{ route('catalogo.produto') }}" class="btn-outline px-8 py-3">
                    Ver todos os Produtos
                </a>
            </div>
        @else
            <div class="text-center py-12">
                @if($settings->logo_path)
                    <img src="{{ Storage::url($settings->logo_path) }}"
                         alt=""
                         class="h-12 w-auto object-contain mx-auto mb-4 opacity-80"
                         loading="lazy">
                @else
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-full text-base font-black mb-4"
                         style="background:#F4E294; color:#3D3000;">F</div>
                @endif
                <p class="text-xl font-semibold" style="color: #7A5C00;">Produtos chegando em breve!</p>
            </div>
        @endif

    </div>
</section>

{{-- ====================== SEÇÃO 5b: SERVIÇOS ====================== --}}
<section id="servicos" class="py-12 md:py-16 xl:py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-8">
            <div class="w-12 h-1.5 rounded-full mx-auto mb-4" style="background-color: #E8A000;"></div>
            <h2 class="section-title inline-flex items-center justify-center gap-3">
                @if($settings->logo_path)
                    <img src="{{ Storage::url($settings->logo_path) }}"
                         alt=""
                         class="h-9 w-auto object-contain"
                         loading="lazy">
                @else
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-black"
                          style="background:#F4E294; color:#3D3000;">F</span>
                @endif
                <span>Serviços em Destaque</span>
            </h2>
            <p class="section-subtitle">Design, comunicação, aulas e consultorias para fortalecer coletivos e negócios</p>
        </div>

        @if($featuredServicos->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                @foreach($featuredServicos as $item)
                    @if($item->expositor)
                    <a href="{{ route('loja.produto', [$item->expositor->slug, $item->slug, 'return_to' => url()->full()]) }}"
                       class="bg-white rounded-2xl overflow-hidden shadow-sm card-hover border flex flex-col"
                       style="border-color: #F0D060;">
                    @else
                    <article class="bg-white rounded-2xl overflow-hidden shadow-sm card-hover border flex flex-col"
                             style="border-color: #F0D060;">
                    @endif

                        {{-- Cabeçalho colorido com emoji --}}
                        <div class="h-36 overflow-hidden">
                            @if($item->image_path)
                                <img src="{{ Storage::url($item->image_path) }}"
                                     alt="{{ $item->name }}"
                                     class="w-full h-full object-cover transition-transform duration-300 hover:scale-105"
                                     loading="lazy">
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center gap-1"
                                     style="background: linear-gradient(135deg, #E8F4EA 0%, #B8DDB9 100%);">
                                    <span class="text-4xl">🎯</span>
                                    <span class="text-xs font-semibold" style="color: #2D6A30;">Serviço</span>
                                </div>
                            @endif
                        </div>

                        <div class="p-4 flex flex-col flex-1">
                            {{-- Badges: modalidade --}}
                            @if($item->modality)
                            <div class="flex items-center gap-1.5 mb-2 flex-wrap">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                                      style="background-color: #E8F4EA; color: #2D6A30;">
                                    {{ $item->modality instanceof \App\Enums\Modality ? $item->modality->label() : $item->modality }}
                                </span>
                                @if($item->duration_min)
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                                      style="background-color: #FDF8DC; color: #7A5C00;">
                                    ⏱ {{ $item->duration_min >= 60 ? floor($item->duration_min/60).'h'.($item->duration_min%60 ? ($item->duration_min%60).'min' : '') : $item->duration_min.'min' }}
                                </span>
                                @endif
                            </div>
                            @endif

                            <h3 class="text-sm sm:text-base font-bold leading-snug mb-1 flex-1 line-clamp-2" style="color: #1A1A1A;">
                                {{ $item->name }}
                            </h3>

                            {{-- Preço com contexto --}}
                            <div class="my-2">
                                @if($item->price_type?->value === 'sob_consulta' || (! $item->price && $item->price_type?->value !== 'fixo'))
                                    <p class="text-sm font-bold" style="color: #5C8A3C;">A combinar</p>
                                @elseif($item->price)
                                    <p class="text-base font-black" style="color: #C47A00;">
                                        R$ {{ number_format($item->price, 2, ',', '.') }}
                                        @if($item->price_type?->value === 'por_hora')
                                            <span class="text-xs font-normal text-gray-500">/hora</span>
                                        @elseif($item->price_type?->value === 'por_sessao')
                                            <span class="text-xs font-normal text-gray-500">/sessão</span>
                                        @endif
                                    </p>
                                @endif
                            </div>

                            @if($item->expositor)
                                <p class="text-xs text-gray-500 mb-3 flex items-center gap-1 line-clamp-1">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    {{ $item->expositor->name }}
                                </p>
                            @endif
                            <span class="btn-primary text-xs py-2 px-3 text-center w-full" style="min-height: 36px; font-size: 0.8125rem;">
                                Ver Serviço
                            </span>
                        </div>

                    @if($item->expositor)
                    </a>
                    @else
                    </article>
                    @endif
                @endforeach
            </div>

            <div class="text-center mt-8">
                <a href="{{ route('catalogo.servico') }}" class="btn-outline px-8 py-3">
                    Ver todos os Serviços
                </a>
            </div>
        @else
            <div class="text-center py-12">
                @if($settings->logo_path)
                    <img src="{{ Storage::url($settings->logo_path) }}"
                         alt=""
                         class="h-12 w-auto object-contain mx-auto mb-4 opacity-80"
                         loading="lazy">
                @else
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-full text-base font-black mb-4"
                         style="background:#F4E294; color:#3D3000;">F</div>
                @endif
                <p class="text-xl font-semibold" style="color: #7A5C00;">Serviços chegando em breve!</p>
            </div>
        @endif

    </div>
</section>

{{-- ====================== SEÇÃO 5c: CUIDADO & BEM VIVER ====================== --}}
<section id="cuidado" class="py-12 md:py-16 xl:py-20" style="background-color: #FDF8DC;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-8">
            <div class="w-12 h-1.5 rounded-full mx-auto mb-4" style="background-color: #E8A000;"></div>
            <h2 class="section-title inline-flex items-center justify-center gap-3">
                @if($settings->logo_path)
                    <img src="{{ Storage::url($settings->logo_path) }}"
                         alt=""
                         class="h-9 w-auto object-contain"
                         loading="lazy">
                @else
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-black"
                          style="background:#F4E294; color:#3D3000;">F</span>
                @endif
                <span>Cuidado & Bem Viver</span>
            </h2>
            <p class="section-subtitle">Massagens, terapias e práticas integrativas para o bem viver de corpo e mente</p>
        </div>

        @if($featuredCuidados->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                @foreach($featuredCuidados as $item)
                    @if($item->expositor)
                    <a href="{{ route('loja.produto', [$item->expositor->slug, $item->slug, 'return_to' => url()->full()]) }}"
                       class="bg-white rounded-2xl overflow-hidden shadow-sm card-hover border flex flex-col"
                       style="border-color: #F0D060;">
                    @else
                    <article class="bg-white rounded-2xl overflow-hidden shadow-sm card-hover border flex flex-col"
                             style="border-color: #F0D060;">
                    @endif

                        <div class="h-36 overflow-hidden">
                            @if($item->image_path)
                                <img src="{{ Storage::url($item->image_path) }}"
                                     alt="{{ $item->name }}"
                                     class="w-full h-full object-cover transition-transform duration-300 hover:scale-105"
                                     loading="lazy">
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center gap-1"
                                     style="background: linear-gradient(135deg, #EAF4E8 0%, #B8D9B5 100%);">
                                    <span class="text-4xl">🌿</span>
                                    <span class="text-xs font-semibold" style="color: #2D6A2A;">Cuidado & Bem Viver</span>
                                </div>
                            @endif
                        </div>

                        <div class="p-4 flex flex-col flex-1">
                            @if($item->modality)
                            <div class="flex items-center gap-1.5 mb-2 flex-wrap">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                                      style="background-color: #EAF4E8; color: #2D6A2A;">
                                    {{ $item->modality instanceof \App\Enums\Modality ? $item->modality->label() : $item->modality }}
                                </span>
                                @if($item->duration_min)
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                                      style="background-color: #FDF8DC; color: #7A5C00;">
                                    ⏱ {{ $item->duration_min >= 60 ? floor($item->duration_min/60).'h'.($item->duration_min%60 ? ($item->duration_min%60).'min' : '') : $item->duration_min.'min' }}
                                </span>
                                @endif
                            </div>
                            @endif

                            <h3 class="text-sm sm:text-base font-bold leading-snug mb-1 flex-1 line-clamp-2" style="color: #1A1A1A;">
                                {{ $item->name }}
                            </h3>

                            <div class="my-2">
                                @if($item->price_type?->value === 'sob_consulta' || (! $item->price && $item->price_type?->value !== 'fixo'))
                                    <p class="text-sm font-bold" style="color: #5C8A3C;">A combinar</p>
                                @elseif($item->price)
                                    <p class="text-base font-black" style="color: #C47A00;">
                                        R$ {{ number_format($item->price, 2, ',', '.') }}
                                        @if($item->price_type?->value === 'por_hora')
                                            <span class="text-xs font-normal text-gray-500">/hora</span>
                                        @elseif($item->price_type?->value === 'por_sessao')
                                            <span class="text-xs font-normal text-gray-500">/sessão</span>
                                        @endif
                                    </p>
                                @endif
                            </div>

                            @if($item->expositor)
                                <p class="text-xs text-gray-500 mb-3 flex items-center gap-1 line-clamp-1">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    {{ $item->expositor->name }}
                                </p>
                            @endif
                            <span class="btn-primary text-xs py-2 px-3 text-center w-full" style="min-height: 36px; font-size: 0.8125rem;">
                                Ver Detalhes
                            </span>
                        </div>

                    @if($item->expositor)
                    </a>
                    @else
                    </article>
                    @endif
                @endforeach
            </div>

            <div class="text-center mt-8">
                <a href="{{ route('catalogo.cuidado') }}" class="btn-outline px-8 py-3">
                    Ver todo Cuidado & Bem Viver
                </a>
            </div>
        @else
            <div class="text-center py-12">
                @if($settings->logo_path)
                    <img src="{{ Storage::url($settings->logo_path) }}"
                         alt=""
                         class="h-12 w-auto object-contain mx-auto mb-4 opacity-80"
                         loading="lazy">
                @else
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-full text-base font-black mb-4"
                         style="background:#F4E294; color:#3D3000;">F</div>
                @endif
                <p class="text-xl font-semibold" style="color: #7A5C00;">Práticas de cuidado chegando em breve!</p>
            </div>
        @endif

    </div>
</section>

{{-- ====================== SEÇÃO 6: NOTÍCIAS ====================== --}}
<section id="noticias" class="py-12 md:py-16 xl:py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-8">
            <div class="w-12 h-1.5 rounded-full mx-auto mb-4" style="background-color: #E8A000;"></div>
            <h2 class="section-title inline-flex items-center justify-center gap-3">
                @if($settings->logo_path)
                    <img src="{{ Storage::url($settings->logo_path) }}"
                         alt=""
                         class="h-9 w-auto object-contain"
                         loading="lazy">
                @else
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-black"
                          style="background:#F4E294; color:#3D3000;">F</span>
                @endif
                <span>Notícias e Blog</span>
            </h2>
            <p class="section-subtitle">Atualizações, histórias e conteúdos para acompanhar o movimento da feira</p>
        </div>

        @if($latestPosts->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 lg:gap-6">

                {{-- Post destaque --}}
                @php $featured = $latestPosts->first(); @endphp
                <article class="md:col-span-2 lg:col-span-1 lg:row-span-2 bg-white rounded-2xl overflow-hidden shadow-md card-hover border flex flex-col"
                         style="border-color: #F0D060;">
                    <div class="h-44 md:h-48 xl:h-56 overflow-hidden">
                        @if($featured->image_path)
                            <img src="{{ Storage::url($featured->image_path) }}"
                                 alt="{{ $featured->title }}"
                                 class="w-full h-full object-cover transition-transform duration-300 hover:scale-105"
                                 loading="lazy">
                        @else
                            <div class="w-full h-full img-placeholder flex-col gap-2">
                                @if($settings->logo_path)
                                    <img src="{{ Storage::url($settings->logo_path) }}"
                                         alt=""
                                         class="h-12 w-auto object-contain opacity-80"
                                         loading="lazy">
                                @else
                                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-full text-base font-black"
                                          style="background:#F4E294; color:#3D3000;">F</span>
                                @endif
                                <span>Notícia em destaque</span>
                            </div>
                        @endif
                    </div>
                    <div class="p-5 flex flex-col flex-1">
                        <span class="text-xs font-bold uppercase tracking-widest mb-3 px-2.5 py-1 rounded-full w-fit"
                              style="background-color: #F4E294; color: #3D3000;">
                            {{ $featured->type?->value === 'news' ? 'Notícia' : 'Blog' }}
                        </span>
                        <h3 class="text-lg lg:text-xl font-black leading-snug mb-3 flex-1 line-clamp-3" style="color: #1A1A1A;">
                            {{ $featured->title }}
                        </h3>
                        @if($featured->excerpt)
                            <p class="text-gray-600 text-sm leading-relaxed mb-4 line-clamp-3">
                                {{ Str::limit($featured->excerpt, 140) }}
                            </p>
                        @endif
                        <div class="flex items-center justify-between mt-auto pt-3 border-t" style="border-color: #F4E294;">
                            <span class="text-xs text-gray-400">{{ $featured->published_at?->format('d/m/Y') }}</span>
                            <a href="{{ route('blog.show', $featured->slug) }}" class="btn-primary text-sm py-2 px-4 whitespace-nowrap" style="min-height: 36px;">
                                Ler mais
                            </a>
                        </div>
                    </div>
                </article>

                {{-- Posts secundários --}}
                @foreach($latestPosts->skip(1) as $post)
                    <article class="bg-white rounded-2xl overflow-hidden shadow-sm card-hover border flex flex-col sm:flex-row md:flex-col"
                             style="border-color: #F0D060;">
                        <div class="h-36 sm:w-32 sm:h-auto sm:shrink-0 md:w-auto md:h-32 overflow-hidden">
                            @if($post->image_path)
                                <img src="{{ Storage::url($post->image_path) }}"
                                     alt="{{ $post->title }}"
                                     class="w-full h-full object-cover"
                                     loading="lazy">
                            @else
                                <div class="w-full h-full img-placeholder flex-col gap-1">
                                    @if($settings->logo_path)
                                        <img src="{{ Storage::url($settings->logo_path) }}"
                                             alt=""
                                             class="h-8 w-auto object-contain opacity-80"
                                             loading="lazy">
                                    @else
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full text-xs font-black"
                                              style="background:#F4E294; color:#3D3000;">F</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="p-4 flex flex-col flex-1">
                            <span class="text-xs font-bold uppercase tracking-widest mb-2 px-2 py-0.5 rounded-full w-fit"
                                  style="background-color: #FDF8DC; color: #7A5C00;">
                                {{ $post->type?->value === 'news' ? 'Notícia' : 'Blog' }}
                            </span>
                            <h3 class="text-sm sm:text-base font-bold leading-snug mb-2 flex-1 line-clamp-2" style="color: #1A1A1A;">
                                {{ Str::limit($post->title, 70) }}
                            </h3>
                            <div class="flex items-center justify-between mt-auto">
                                <span class="text-xs text-gray-400">{{ $post->published_at?->format('d/m/Y') }}</span>
                                <a href="{{ route('blog.show', $post->slug) }}" class="text-sm font-semibold transition-colors" style="color: #C47A00;"
                                   onmouseover="this.style.color='#7A5C00'" onmouseout="this.style.color='#C47A00'">
                                    Ler mais
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach

            </div>
        @else
            <div class="text-center py-12">
                @if($settings->logo_path)
                    <img src="{{ Storage::url($settings->logo_path) }}"
                         alt=""
                         class="h-12 w-auto object-contain mx-auto mb-4 opacity-80"
                         loading="lazy">
                @else
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-full text-base font-black mb-4"
                         style="background:#F4E294; color:#3D3000;">F</div>
                @endif
                <p class="text-xl font-semibold" style="color: #7A5C00;">Publicações em breve!</p>
            </div>
        @endif

    </div>
</section>

{{-- ====================== SEÇÃO 7: CTA EXPOSITORES ====================== --}}
<section id="expositores-cta" class="py-12 md:py-16 xl:py-20" style="background-color: #FDF8DC;">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">

        <div class="w-12 h-1.5 rounded-full mx-auto mb-4" style="background-color: #E8A000;"></div>
        <h2 class="section-title inline-flex items-center justify-center gap-3">
            @if($settings->logo_path)
                <img src="{{ Storage::url($settings->logo_path) }}"
                     alt=""
                     class="h-9 w-auto object-contain"
                     loading="lazy">
            @else
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-black"
                      style="background:#F4E294; color:#3D3000;">F</span>
            @endif
            <span>Quer vender seus produtos em nossa feira?</span>
        </h2>
        <p class="section-subtitle mb-8">
            Faça parte de um movimento de economia solidária e conecte-se com pessoas que valorizam produção local, artesanato e cultura popular.
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('seja-um-expositor') }}"
               class="btn-primary px-8 py-3 text-base whitespace-nowrap"
               style="min-height: 48px;">
                Quero ser Expositor
            </a>
            <a href="{{ route('contato') }}"
               class="btn-outline px-8 py-3 text-base whitespace-nowrap"
               style="min-height: 48px; background-color: #fff;">
                Enviar mensagem por e-mail
            </a>
            @if($settings->whatsapp)
                <a href="https://wa.me/55{{ preg_replace('/\D/', '', $settings->whatsapp) }}"
                   target="_blank"
                   class="btn-secondary px-8 py-3 text-base whitespace-nowrap"
                   style="min-height: 48px;">
                    Falar no WhatsApp
                </a>
            @endif
        </div>
    </div>
</section>

{{-- ====================== SEÇÃO 8: NEWSLETTER ====================== --}}
<section id="newsletter" class="py-12 md:py-16 xl:py-20" style="background-color: #F4E294;">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">

        <div class="w-12 h-1.5 rounded-full mx-auto mb-4" style="background-color: #E8A000;"></div>
        <h2 class="section-title inline-flex items-center justify-center gap-3">
            @if($settings->logo_path)
                <img src="{{ Storage::url($settings->logo_path) }}"
                     alt=""
                     class="h-9 w-auto object-contain"
                     loading="lazy">
            @else
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-black"
                      style="background:#FDF8DC; color:#3D3000;">F</span>
            @endif
            <span>Fique por Dentro das Novidades</span>
        </h2>
        <p class="section-subtitle mb-8" style="color: #5C4000;">
            Receba em primeira mão informações sobre as próximas feiras, novos expositores e conteúdos exclusivos.
        </p>

        @if(session('newsletter_success'))
            <div class="mb-6 px-6 py-4 rounded-xl font-semibold"
                 style="background-color: #5C8A3C; color: white;">
                ✅ {{ session('newsletter_success') }}
            </div>
        @endif

        <form action="{{ route('newsletter.subscribe') }}" method="POST">
            @csrf
            <div class="flex flex-col sm:flex-row gap-3 max-w-3xl mx-auto">
                <input type="text"
                       name="name"
                       placeholder="Seu nome"
                       required
                       value="{{ old('name') }}"
                       class="flex-1 px-4 py-3 rounded-xl text-base font-medium border-2 focus:outline-none focus:ring-0 transition-colors"
                       style="border-color: #E8A000; background: white; color: #1A1A1A; min-height: 48px;"
                       onfocus="this.style.borderColor='#C47A00'"
                       onblur="this.style.borderColor='#E8A000'">
                <input type="email"
                       name="email"
                       placeholder="Seu melhor e-mail"
                       required
                       value="{{ old('email') }}"
                       class="flex-1 px-4 py-3 rounded-xl text-base font-medium border-2 focus:outline-none focus:ring-0 transition-colors"
                       style="border-color: #E8A000; background: white; color: #1A1A1A; min-height: 48px;"
                       onfocus="this.style.borderColor='#C47A00'"
                       onblur="this.style.borderColor='#E8A000'">
                <button type="submit"
                        class="btn-secondary px-6 py-3 text-base whitespace-nowrap"
                        style="min-height: 48px;">
                    Receber Novidades
                </button>
            </div>
            @error('email')
                <p class="mt-3 text-sm font-medium" style="color: #7A0000;">{{ $message }}</p>
            @enderror
            @error('name')
                <p class="mt-3 text-sm font-medium" style="color: #7A0000;">{{ $message }}</p>
            @enderror
        </form>

    </div>
</section>

</main>

{{-- ====================== FOOTER ====================== --}}
<footer id="contato" style="background-color: #F4E294;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">

            {{-- Coluna 1: Logo + Descrição --}}
            <div class="sm:col-span-2 lg:col-span-1">
                @if($settings->logo_path)
                    <img src="{{ Storage::url($settings->logo_path) }}"
                         alt="{{ $settings->site_name }}"
                         class="h-12 w-auto object-contain mb-4">
                @else
                    <p class="font-black text-2xl mb-3" style="color: #3D3000;">
                        Feira<br><span style="color: #C47A00;">Esquerda Livre</span>
                    </p>
                @endif
                @if($settings->site_description)
                    <p class="text-sm leading-relaxed" style="color: #5C4000;">
                        {{ $settings->site_description }}
                    </p>
                @else
                    <p class="text-sm leading-relaxed" style="color: #5C4000;">
                        O maior movimento de economia solidária e cultura popular do Brasil.
                    </p>
                @endif
            </div>

            {{-- Coluna 2: Links Rápidos --}}
            <div>
                <h3 class="text-sm font-black uppercase tracking-widest mb-4" style="color: #3D3000;">
                    Links Rápidos
                </h3>
                <ul class="space-y-2.5">
                    @foreach(['Início' => '#', 'Agenda' => '#agenda', 'Marketplace' => '#marketplace', 'Notícias' => '#noticias', 'Sobre' => '#sobre', 'Contato' => route('contato'), 'Política de Privacidade' => route('politica-privacidade'), 'Termos de Uso' => route('termos-uso')] as $label => $href)
                        <li>
                            <a href="{{ $href }}"
                               class="text-sm font-medium transition-colors"
                               style="color: #5C4000;"
                               onmouseover="this.style.color='#3D3000'"
                               onmouseout="this.style.color='#5C4000'">
                                → {{ $label }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Coluna 3: Contato --}}
            <div>
                <h3 class="text-sm font-black uppercase tracking-widest mb-4" style="color: #3D3000;">
                    Contato
                </h3>
                <ul class="space-y-3">
                    @if($settings->whatsapp)
                        <li>
                            <a href="https://wa.me/55{{ preg_replace('/\D/', '', $settings->whatsapp) }}"
                               target="_blank"
                               class="flex items-center gap-2 text-sm font-medium transition-colors"
                               style="color: #5C4000;"
                               onmouseover="this.style.color='#3D3000'"
                               onmouseout="this.style.color='#5C4000'">
                                <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                </svg>
                                {{ $settings->whatsapp }}
                            </a>
                        </li>
                    @endif
                    @if($settings->email)
                        <li>
                            <a href="mailto:{{ $settings->email }}"
                               class="flex items-center gap-2 text-sm font-medium transition-colors"
                               style="color: #5C4000;"
                               onmouseover="this.style.color='#3D3000'"
                               onmouseout="this.style.color='#5C4000'">
                                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                {{ $settings->email }}
                            </a>
                        </li>
                    @endif
                    @if($settings->address)
                        <li class="flex items-start gap-2 text-sm" style="color: #7A5C00;">
                            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $settings->address }}
                        </li>
                    @endif
                </ul>
            </div>

            {{-- Coluna 4: Redes Sociais --}}
            <div>
                <h3 class="text-sm font-black uppercase tracking-widest mb-4" style="color: #3D3000;">
                    Redes Sociais
                </h3>
                <div class="flex flex-col gap-3">
                    @if($settings->instagram_url)
                        <a href="{{ $settings->instagram_url }}" target="_blank"
                           class="flex items-center gap-3 text-sm font-medium transition-colors"
                           style="color: #5C4000;"
                           onmouseover="this.style.color='#3D3000'"
                           onmouseout="this.style.color='#5C4000'">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                            Instagram
                        </a>
                    @endif
                    @if($settings->facebook_url)
                        <a href="{{ $settings->facebook_url }}" target="_blank"
                           class="flex items-center gap-3 text-sm font-medium transition-colors"
                           style="color: #5C4000;"
                           onmouseover="this.style.color='#3D3000'"
                           onmouseout="this.style.color='#5C4000'">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            Facebook
                        </a>
                    @endif
                    @if($settings->youtube_url)
                        <a href="{{ $settings->youtube_url }}" target="_blank"
                           class="flex items-center gap-3 text-sm font-medium transition-colors"
                           style="color: #5C4000;"
                           onmouseover="this.style.color='#3D3000'"
                           onmouseout="this.style.color='#5C4000'">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                            YouTube
                        </a>
                    @endif
                </div>
            </div>

        </div>

        {{-- Rodapé inferior --}}
        <div class="mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 border-t"
             style="border-color: #E8A000;">
            <span class="text-sm font-medium" style="color: #5C4000;">
                @if($settings->footer_text)
                    {!! $settings->footer_text !!}
                @else
                    © {{ date('Y') }} {{ $settings->site_name ?? 'Feira Esquerda Livre' }}. Todos os direitos reservados.
                @endif
            </span>
            <a href="{{ route('admin.dashboard') }}"
               class="text-xs font-medium transition-colors"
               style="color: #7A5C00;"
               onmouseover="this.style.color='#3D3000'"
               onmouseout="this.style.color='#7A5C00'">
                ⚙ Painel Admin
            </a>
        </div>
    </div>
</footer>

@include('partials.back-to-top')
@livewireScriptConfig
@include('partials.consent-banner')
</body>
</html>
