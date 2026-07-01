@extends('layouts.public')

@section('title', 'Comunidade Esquerda Livre')
@section('description', 'Atualizações de expositores, produtos, feiras e comunicados da comunidade Feira Esquerda Livre.')

@section('content')
<main class="bg-gray-50 min-h-screen">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-gray-400">Comunidade</p>
                <h1 class="text-3xl font-black mt-1" style="color:#3D3000;">Comunidade Esquerda Livre</h1>
            </div>

            @auth
                @if(auth()->user()->isLojista())
                <a href="{{ route('lojista.feed.index') }}"
                   class="inline-flex items-center justify-center px-5 py-3 rounded-xl text-white text-base font-bold"
                   style="background:#E8A000; min-height:52px;">
                    Criar publicação
                </a>
                @endif
            @else
            <a href="{{ route('login') }}"
               class="inline-flex items-center justify-center px-5 py-3 rounded-xl text-base font-bold"
               style="background:#3D3000; color:#F4E294; min-height:52px;">
                Entrar para interagir
            </a>
            @endauth
        </div>

        <livewire:feed.feed-index />
    </div>
</main>
@endsection
