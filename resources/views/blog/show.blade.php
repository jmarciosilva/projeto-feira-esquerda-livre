@extends('layouts.public')

@section('title', $post->meta_title ?? $post->title . ' — Feira Esquerda Livre')
@section('description', $post->meta_description ?? $post->excerpt ?? Str::limit(strip_tags($post->content), 160))

@section('content')
@php use Illuminate\Support\Str; @endphp

<main>

    {{-- Hero --}}
    @if($post->image_path)
    <div class="w-full overflow-hidden" style="max-height: 420px;">
        <img src="{{ Storage::url($post->image_path) }}" alt="{{ $post->title }}"
             class="w-full object-cover" style="max-height: 420px;">
    </div>
    @else
    <div class="w-full py-20 flex items-center justify-center"
         style="background: linear-gradient(135deg, #3D3000 0%, #5C4500 60%, #E8A000 100%);">
        <div class="text-center px-4">
            <span class="inline-block text-xs font-bold uppercase tracking-widest px-3 py-1.5 rounded-full mb-5"
                  style="background: #F4E294; color: #3D3000;">
                {{ $post->type?->value === 'news' ? '📰 Notícia' : '✍️ Blog' }}
            </span>
            <h1 class="text-2xl sm:text-4xl font-extrabold text-white max-w-3xl mx-auto leading-tight">
                {{ $post->title }}
            </h1>
        </div>
    </div>
    @endif

    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-10">

        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-400 mb-6 flex items-center gap-2">
            <a href="{{ url('/') }}" class="hover:text-gray-600 transition-colors">Início</a>
            <span>›</span>
            <a href="{{ url('/#noticias') }}" class="hover:text-gray-600 transition-colors">
                {{ $post->type?->value === 'news' ? 'Notícias' : 'Blog' }}
            </a>
            <span>›</span>
            <span class="text-gray-700 line-clamp-1">{{ $post->title }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

            {{-- Conteúdo principal --}}
            <article class="lg:col-span-2">

                @if($post->image_path)
                <span class="inline-block text-xs font-bold uppercase tracking-widest px-3 py-1.5 rounded-full mb-4"
                      style="background: #F4E294; color: #3D3000;">
                    {{ $post->type?->value === 'news' ? '📰 Notícia' : '✍️ Blog' }}
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mb-4">
                    {{ $post->title }}
                </h1>
                @endif

                {{-- Meta --}}
                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 mb-8 pb-6 border-b border-gray-100">
                    @if($post->author)
                    <span class="flex items-center gap-1.5">
                        <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                              style="background: #E8A000;">
                            {{ strtoupper(substr($post->author->name, 0, 1)) }}
                        </span>
                        {{ $post->author->name }}
                    </span>
                    @endif
                    @if($post->published_at)
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ $post->published_at->isoFormat('DD [de] MMMM [de] YYYY') }}
                    </span>
                    @endif
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ max(1, (int) ceil(str_word_count(strip_tags($post->content)) / 200)) }} min de leitura
                    </span>
                </div>

                {{-- Excerpt destaque --}}
                @if($post->excerpt)
                <div class="text-lg text-gray-600 leading-relaxed font-medium mb-8 pl-4 border-l-4 italic"
                     style="border-color: #E8A000;">
                    {{ $post->excerpt }}
                </div>
                @endif

                {{-- Conteúdo --}}
                <div class="prose prose-lg max-w-none text-gray-700 leading-relaxed
                            prose-headings:text-gray-900 prose-headings:font-bold
                            prose-h2:text-xl prose-h2:mt-8 prose-h2:mb-4
                            prose-h3:text-lg prose-h3:mt-6 prose-h3:mb-3
                            prose-p:mb-5 prose-p:leading-relaxed
                            prose-ul:my-4 prose-ul:pl-6 prose-li:mb-2
                            prose-strong:text-gray-900
                            prose-a:text-[#C47A00] prose-a:font-medium prose-a:no-underline hover:prose-a:underline">
                    {!! $post->content !!}
                </div>

                {{-- Compartilhar --}}
                <div class="mt-10 pt-8 border-t border-gray-100">
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Compartilhar</p>
                    <div class="flex flex-wrap gap-3">
                        <a href="https://wa.me/?text={{ urlencode($post->title . ' — ' . url()->current()) }}"
                           target="_blank" rel="noopener"
                           class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition-opacity hover:opacity-90"
                           style="background: #25D366;">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            WhatsApp
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}"
                           target="_blank" rel="noopener"
                           class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition-opacity hover:opacity-90"
                           style="background: #1877F2;">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            Facebook
                        </a>
                        <button onclick="navigator.clipboard.writeText('{{ url()->current() }}').then(() => this.textContent = '✓ Copiado!')"
                                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border-2 transition-colors"
                                style="border-color: #E8A000; color: #C47A00;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Copiar link
                        </button>
                    </div>
                </div>

            </article>

            {{-- Sidebar --}}
            <aside class="space-y-6">

                {{-- CTA Seja Expositor --}}
                <div class="rounded-2xl p-6 text-center" style="background: linear-gradient(135deg, #3D3000, #5C4500);">
                    <p class="text-3xl mb-3">🤝</p>
                    <h3 class="font-bold text-white text-base mb-2">Quer expor na Feira?</h3>
                    <p class="text-sm mb-4" style="color: #F4E294;">Junte-se a centenas de expositores em todo o Brasil.</p>
                    <a href="{{ route('seja-um-expositor') }}"
                       class="block w-full py-3 rounded-xl font-bold text-sm transition-opacity hover:opacity-90"
                       style="background: #F4E294; color: #3D3000;">
                        Quero ser Expositor
                    </a>
                </div>

                {{-- Próxima Feira --}}
                @php
                    $proximaFeira = \App\Models\Event::upcoming()->first();
                @endphp
                @if($proximaFeira)
                <div class="bg-white rounded-2xl border-2 p-5" style="border-color: #F4E294;">
                    <p class="text-xs font-bold uppercase tracking-wider mb-3" style="color: #E8A000;">📅 Próxima Feira</p>
                    <p class="font-bold text-gray-900 text-sm leading-tight mb-1">{{ $proximaFeira->title }}</p>
                    <p class="text-xs text-gray-500 mb-1">{{ $proximaFeira->city }}@if($proximaFeira->state) — {{ $proximaFeira->state }}@endif</p>
                    <p class="text-xs font-semibold mb-4" style="color: #C47A00;">
                        {{ $proximaFeira->start_date->isoFormat('DD [de] MMMM') }}
                    </p>
                    <a href="{{ route('agenda.show', $proximaFeira->slug) }}"
                       class="block w-full py-2.5 rounded-xl font-semibold text-sm text-center transition-colors"
                       style="background: #F4E294; color: #3D3000;">
                        Ver detalhes
                    </a>
                </div>
                @endif

                {{-- Mais artigos --}}
                @if($related->isNotEmpty())
                <div class="bg-white rounded-2xl border border-gray-100 p-5">
                    <p class="text-xs font-bold uppercase tracking-wider mb-4 text-gray-500">Leia também</p>
                    <div class="space-y-4">
                        @foreach($related as $rel)
                        <a href="{{ route('blog.show', $rel->slug) }}"
                           class="flex gap-3 group">
                            <div class="w-16 h-16 rounded-xl overflow-hidden flex-shrink-0 bg-gray-50">
                                @if($rel->image_path)
                                <img src="{{ Storage::url($rel->image_path) }}" alt="{{ $rel->title }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                @else
                                <div class="w-full h-full flex items-center justify-center text-2xl"
                                     style="background: linear-gradient(135deg, #F4E294, #E8A000);">📰</div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 leading-snug line-clamp-2 group-hover:text-[#C47A00] transition-colors">
                                    {{ $rel->title }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1">{{ $rel->published_at?->format('d/m/Y') }}</p>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

            </aside>

        </div>
    </div>

</main>
@endsection
