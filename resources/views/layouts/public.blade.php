<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Feira Esquerda Livre')</title>
    <meta name="description" content="@yield('description', 'Feira Esquerda Livre - Marketplace solidário e progressista')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @include('partials.site-css')
</head>
<body class="font-sans antialiased" style="background:#fff;">
@php
    $settings = $settings ?? App\Models\SiteSetting::instance();
@endphp

{{-- Navbar --}}
<nav x-data="{ mobileMenuOpen: false }" class="sticky top-0 z-50 shadow-md" style="background-color: var(--amarelo, #F4E294);">
    @php
        $navUser = auth()->user();
        [$panelUrl, $panelLabel] = $navUser
            ? match(true) {
                $navUser->isAdmin() || $navUser->isEditor() => [route('admin.dashboard'), 'Painel Admin'],
                $navUser->isLojista() => [route('lojista.dashboard'), 'Minha Loja'],
                default => [route('cliente.pedidos.index'), 'Minha Conta'],
            }
            : [route('login'), 'Entrar'];

        $homeLinks = [
            'Início' => url('/'),
            'Agenda' => url('/#agenda'),
            'Expositores' => url('/#expositores'),
            'Marketplace' => url('/#marketplace'),
            'Comunidade' => route('feed.index'),
            'Notícias' => url('/#noticias'),
            'Sobre' => url('/#sobre'),
            'Contato' => route('contato'),
        ];
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-[72px]">
            <a href="{{ url('/') }}" class="flex items-center shrink-0" aria-label="Feira Esquerda Livre">
                @include('partials.site-logo')
            </a>

            <nav class="hidden md:flex items-center gap-1">
                @foreach($homeLinks as $label => $href)
                    <a href="{{ $href }}" class="nav-link">{{ $label }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2 lg:gap-3 shrink-0">
                <a href="{{ $panelUrl }}"
                   class="hidden md:inline-flex items-center justify-center gap-2 rounded-full px-3.5 text-sm font-semibold transition-colors"
                   style="background:#E7F0EF; color:#245C5A; border:1px solid #B8D2CE; height:40px;"
                   onmouseover="this.style.backgroundColor='#D6E6E4'"
                   onmouseout="this.style.backgroundColor='#E7F0EF'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    {{ $panelLabel }}
                </a>

                <div class="hidden md:block">
                    <livewire:cart-drawer />
                </div>

                <a href="{{ route('seja-um-expositor') }}"
                   class="hidden md:inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-full px-4 text-sm font-bold leading-none transition-all duration-150"
                   style="background-color: var(--verde); color: #fff; min-width: 132px; height: 42px; box-shadow: 0 2px 8px rgba(61,48,0,0.16);"
                   onmouseover="this.style.backgroundColor='var(--verde-hover)'"
                   onmouseout="this.style.backgroundColor='var(--verde)'">
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
                       onmouseover="this.style.backgroundColor='var(--verde-hover)'"
                       onmouseout="this.style.backgroundColor='var(--verde)'">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                        </svg>
                    </a>
                @endif

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

    <div x-cloak
         x-show="mobileMenuOpen"
         x-transition
         class="md:hidden border-t shadow-xl max-h-[calc(100vh-72px)] overflow-y-auto"
         style="background-color: #FDF8DC; border-color: #E8A000;">
        @foreach($homeLinks as $label => $href)
            <a href="{{ $href }}"
               class="flex items-center min-h-14 px-5 py-3.5 text-base font-semibold border-b transition-colors"
               style="color: #3D3000; border-color: #F4E294;"
               onmouseover="this.style.backgroundColor='#F4E294'"
               onmouseout="this.style.backgroundColor=''">
                {{ $label }}
            </a>
        @endforeach

        <a href="{{ $panelUrl }}"
           class="flex items-center gap-3 min-h-14 px-5 py-3.5 text-base font-semibold border-b transition-colors"
           style="color: #3D3000; border-color: #F4E294;"
           onmouseover="this.style.backgroundColor='#F4E294'"
           onmouseout="this.style.backgroundColor=''">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            {{ $panelLabel }}
        </a>

        <a href="{{ route('seja-um-expositor') }}"
           class="mx-5 my-4 flex items-center justify-center gap-2 min-h-12 rounded-lg text-base font-bold"
           style="background-color: var(--verde); color: #fff;">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.3" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75c1.6-1.2 3.84-1.04 5.26.38l.61.61a4.05 4.05 0 010 5.73l-5.17 5.16a1 1 0 01-1.4 0l-5.17-5.16a4.05 4.05 0 010-5.73l.61-.61A4.06 4.06 0 0112 6.75z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.5 10.5h5"/>
            </svg>
            Seja Expositor
        </a>

        @if($settings->whatsapp)
            <a href="https://wa.me/55{{ preg_replace('/\D/', '', $settings->whatsapp) }}"
               target="_blank"
               class="mx-5 mb-4 flex items-center justify-center gap-3 min-h-12 rounded-lg text-base font-bold"
               style="background-color: var(--verde); color: #fff;">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                </svg>
                Falar no WhatsApp
            </a>
        @endif
    </div>
</nav>

{{-- Page content --}}
@yield('content')

{{-- Footer --}}
<footer id="contato" style="background-color: #F4E294;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">

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
                <p class="text-sm leading-relaxed" style="color: #5C4000;">
                    {{ $settings->site_description ?: 'O maior movimento de economia solidária e cultura popular do Brasil.' }}
                </p>
            </div>

            <div>
                <h3 class="text-sm font-black uppercase tracking-widest mb-4" style="color: #3D3000;">
                    Links Rápidos
                </h3>
                <ul class="space-y-2.5">
                    @foreach(['Início' => url('/'), 'Agenda' => url('/#agenda'), 'Marketplace' => url('/#marketplace'), 'Notícias' => url('/#noticias'), 'Sobre' => url('/#sobre'), 'Contato' => route('contato'), 'Política de Privacidade' => route('politica-privacidade'), 'Termos de Uso' => route('termos-uso')] as $label => $href)
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
                            Instagram
                        </a>
                    @endif
                    @if($settings->facebook_url)
                        <a href="{{ $settings->facebook_url }}" target="_blank"
                           class="flex items-center gap-3 text-sm font-medium transition-colors"
                           style="color: #5C4000;"
                           onmouseover="this.style.color='#3D3000'"
                           onmouseout="this.style.color='#5C4000'">
                            Facebook
                        </a>
                    @endif
                    @if($settings->youtube_url)
                        <a href="{{ $settings->youtube_url }}" target="_blank"
                           class="flex items-center gap-3 text-sm font-medium transition-colors"
                           style="color: #5C4000;"
                           onmouseover="this.style.color='#3D3000'"
                           onmouseout="this.style.color='#5C4000'">
                            YouTube
                        </a>
                    @endif
                </div>
            </div>
        </div>

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
@livewireScripts
</body>
</html>
