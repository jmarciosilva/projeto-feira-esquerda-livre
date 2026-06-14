@extends('layouts.public')

@section('title', 'Seja um Expositor — Feira Esquerda Livre')
@section('description', 'Preencha o formulário e faça parte da maior rede de feiras progressistas do Brasil.')

@section('content')
@php
    $settings = App\Models\SiteSetting::instance();
    $contrato  = $settings->contrato_expositor ?? '';
@endphp

<main>
    {{-- Hero --}}
    <div class="py-14 px-4 relative overflow-hidden" style="background:linear-gradient(135deg,#3D3000 0%,#5C4500 60%,#E8A000 100%);">
        <div class="max-w-3xl mx-auto text-center relative z-10">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-semibold mb-5"
                 style="background:rgba(244,226,148,0.15); color:#F4E294; border:1px solid rgba(244,226,148,0.3);">
                Oportunidade para Vendedores
            </div>
            <h1 class="text-3xl sm:text-5xl font-extrabold mb-4 leading-tight" style="color:#F4E294;">
                Seja um Expositor
            </h1>
            <p class="text-lg sm:text-xl max-w-xl mx-auto" style="color:rgba(255,255,255,0.85);">
                Cadastre sua loja e exponha nas feiras progressistas de todo o Brasil.
            </p>
        </div>
    </div>

    {{-- Benefícios --}}
    <div style="background:#FDF8DC; border-bottom:2px solid #F4E294;">
        <div class="max-w-4xl mx-auto px-4 py-8">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                @foreach([
                    ['icon'=>'🛍️', 'title'=>'Venda Online e Presencial', 'text'=>'Exponha seus produtos na plataforma e participe das feiras físicas em todo o Brasil'],
                    ['icon'=>'📅', 'title'=>'Agenda de Feiras',           'text'=>'Acesso prioritário às datas e localidades dos próximos eventos progressistas'],
                    ['icon'=>'📣', 'title'=>'Visibilidade Garantida',     'text'=>'Sua loja divulgada para milhares de consumidores conscientes'],
                ] as $b)
                <div class="flex items-start gap-4">
                    <div class="text-3xl flex-shrink-0 mt-0.5">{{ $b['icon'] }}</div>
                    <div>
                        <p class="font-bold text-sm mb-1" style="color:#3D3000;">{{ $b['title'] }}</p>
                        <p class="text-sm leading-relaxed" style="color:#5C4500;">{{ $b['text'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="max-w-2xl mx-auto px-4 py-12">

        @if(session('solicitacao_success'))
        {{-- Sucesso --}}
        <div class="rounded-2xl overflow-hidden shadow-sm border border-green-200">
            <div class="px-6 py-4" style="background:#16a34a;">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h2 class="text-lg font-bold text-white">Solicitação enviada com sucesso!</h2>
                </div>
            </div>
            <div class="px-6 py-8 text-center bg-white">
                <div class="text-6xl mb-4">🎉</div>
                <p class="text-gray-700 text-base mb-6">{{ session('solicitacao_success') }}</p>
                <a href="{{ url('/') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-white transition-opacity hover:opacity-90"
                   style="background:#3D3000;">
                    ← Voltar ao início
                </a>
            </div>
        </div>

        @else

        <div x-data="expositorForm()">

            {{-- Indicador de etapas --}}
            <div class="flex items-center gap-0 mb-10">
                <div class="flex items-center gap-2 flex-1">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                         :style="accepted ? 'background:#22c55e; color:#fff;' : 'background:#E8A000; color:#fff;'">
                        <span x-show="!accepted">1</span>
                        <svg x-show="accepted" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold" style="color:#3D3000;">Leia o contrato</p>
                        <p class="text-xs text-gray-400">Termos de participação</p>
                    </div>
                </div>
                <div class="flex-shrink-0 w-12 h-0.5 mx-1" style="background:#D4B800;"></div>
                <div class="flex items-center gap-2 flex-1 justify-end">
                    <div class="text-right">
                        <p class="text-xs font-bold" :style="accepted ? 'color:#3D3000;' : 'color:#9ca3af;'">Preencha o formulário</p>
                        <p class="text-xs text-gray-400">Dados da sua loja</p>
                    </div>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0 border-2"
                         :style="accepted ? 'background:#E8A000; color:#fff; border-color:#E8A000;' : 'background:#fff; color:#9ca3af; border-color:#d1d5db;'">
                        2
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- BLOCO 1: CONTRATO (sempre visível) --}}
            {{-- ============================================================ --}}
            @if($contrato)
            <div class="mb-8">

                {{-- Card do documento --}}
                <div class="rounded-2xl overflow-hidden shadow-md border border-gray-200 bg-white">

                    {{-- Cabeçalho do documento --}}
                    <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100" style="background:linear-gradient(135deg,#3D3000,#5C4500);">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#E8A000;">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="white" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white">Contrato de Exposição</p>
                                <p class="text-xs" style="color:#F4E294; opacity:0.8;">Feira Esquerda Livre</p>
                            </div>
                        </div>
                        <div class="text-right hidden sm:block">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold" style="background:rgba(244,226,148,0.2); color:#F4E294;">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Documento Oficial
                            </span>
                        </div>
                    </div>

                    {{-- Corpo do contrato (scrollável) --}}
                    <div class="relative">
                        <div id="contract-body"
                             class="px-7 py-6 overflow-y-auto text-sm leading-7"
                             style="max-height:300px; color:#374151; font-family:'Georgia',serif; scroll-behavior:smooth;"
                             @scroll="checkScroll($event)">
                            {!! nl2br(e($contrato)) !!}
                        </div>
                        {{-- Gradiente "rolar para ver mais" --}}
                        <div x-show="!scrolledToBottom"
                             class="absolute bottom-0 left-0 right-0 h-16 pointer-events-none"
                             style="background:linear-gradient(to top,rgba(255,255,255,1),rgba(255,255,255,0));">
                        </div>
                    </div>

                    {{-- Rodapé do documento --}}
                    <div class="px-6 py-3 border-t border-gray-100 flex items-center justify-between" style="background:#f9fafb;">
                        <div class="flex items-center gap-1.5 text-xs text-gray-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <span x-show="!scrolledToBottom">Role para ler o contrato completo</span>
                            <span x-show="scrolledToBottom" style="color:#16a34a; font-weight:600;">Contrato lido até o final ✓</span>
                        </div>
                        <button type="button" @click="scrollContract()"
                                x-show="!scrolledToBottom"
                                class="text-xs font-semibold px-3 py-1 rounded-lg transition-colors"
                                style="background:#F4E294; color:#3D3000;">
                            Rolar ↓
                        </button>
                    </div>
                </div>

                {{-- Checkbox de aceite --}}
                <label class="flex items-start gap-4 mt-5 p-5 rounded-xl cursor-pointer border-2 transition-all"
                       :style="accepted ? 'border-color:#22c55e; background:#f0fdf4;' : 'border-color:#F4E294; background:#FDF8DC;'">
                    <div class="relative flex-shrink-0 mt-0.5">
                        <input type="checkbox" x-model="accepted" class="sr-only">
                        <div class="w-6 h-6 rounded-md border-2 flex items-center justify-center transition-all"
                             :style="accepted ? 'background:#22c55e; border-color:#22c55e;' : 'background:#fff; border-color:#D4B800;'">
                            <svg x-show="accepted" class="w-4 h-4 text-white" fill="none" stroke="white" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-bold" :style="accepted ? 'color:#15803d;' : 'color:#3D3000;'">
                            Li e concordo com todos os termos do contrato acima *
                        </p>
                        <p class="text-xs mt-1" style="color:#6b7280;">
                            Ao marcar esta opção, você declara que leu e aceita os termos de participação como expositor na Feira Esquerda Livre.
                        </p>
                    </div>
                </label>

                {{-- Aviso quando não aceito --}}
                <div x-show="!accepted" x-transition
                     class="mt-3 flex items-center gap-2 px-4 py-3 rounded-xl text-sm border"
                     style="background:#FFFBEB; border-color:#F59E0B; color:#92400E;">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Leia e aceite os termos acima para continuar e preencher o formulário de solicitação.
                </div>
            </div>
            @endif

            {{-- ============================================================ --}}
            {{-- BLOCO 2: FORMULÁRIO --}}
            {{-- ============================================================ --}}

            @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                <p class="text-red-800 font-medium text-sm mb-2">Por favor, corrija os campos abaixo:</p>
                <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div x-show="accepted" x-transition:enter="transition ease-out duration-400"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0">

                <div class="rounded-2xl overflow-hidden border border-gray-200 shadow-sm bg-white">

                    {{-- Header do formulário --}}
                    <div class="px-6 py-4 border-b border-gray-100" style="background:#F9F7F0;">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm font-bold" style="background:#E8A000;">2</div>
                            <div>
                                <p class="font-bold text-gray-900 text-sm">Dados da Solicitação</p>
                                <p class="text-xs text-gray-500">Preencha todos os campos obrigatórios (*)</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('seja-um-expositor.post') }}" class="px-6 py-6 space-y-5">
                        @csrf

                        {{-- Nome da Loja + Responsável --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="nome_loja" class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">Nome da Loja *</label>
                                <input id="nome_loja" name="nome_loja" type="text" value="{{ old('nome_loja') }}" required
                                       class="w-full px-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors @error('nome_loja') border-red-400 @else border-gray-200 focus:border-yellow-400 @enderror"
                                       placeholder="Ex.: Ateliê das Mãos">
                                @error('nome_loja')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="responsavel" class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">Responsável *</label>
                                <input id="responsavel" name="responsavel" type="text" value="{{ old('responsavel') }}" required
                                       class="w-full px-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors @error('responsavel') border-red-400 @else border-gray-200 focus:border-yellow-400 @enderror"
                                       placeholder="Nome completo">
                                @error('responsavel')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- CPF / CNPJ --}}
                        <div>
                            <label class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">Documento *</label>
                            <div class="flex items-center gap-3 mb-3">
                                <span class="text-xs text-gray-500 font-medium">Tipo:</span>
                                <div class="inline-flex rounded-lg overflow-hidden border border-gray-200">
                                    <button type="button"
                                            @click="docType = 'cpf'; document.getElementById('cpf_cnpj').value = ''"
                                            :class="docType === 'cpf' ? 'text-white' : 'text-gray-600 bg-white hover:bg-gray-50'"
                                            :style="docType === 'cpf' ? 'background:#E8A000;' : ''"
                                            class="px-5 py-1.5 text-xs font-bold transition-colors">CPF</button>
                                    <button type="button"
                                            @click="docType = 'cnpj'; document.getElementById('cpf_cnpj').value = ''"
                                            :class="docType === 'cnpj' ? 'text-white' : 'text-gray-600 bg-white hover:bg-gray-50'"
                                            :style="docType === 'cnpj' ? 'background:#E8A000;' : ''"
                                            class="px-5 py-1.5 text-xs font-bold transition-colors border-l border-gray-200">CNPJ</button>
                                </div>
                            </div>
                            <input id="cpf_cnpj" name="cpf_cnpj" type="text" value="{{ old('cpf_cnpj') }}" required
                                   @input="maskDoc($event)"
                                   :placeholder="docType === 'cpf' ? '000.000.000-00' : '00.000.000/0000-00'"
                                   :maxlength="docType === 'cpf' ? '14' : '18'"
                                   class="w-full px-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors @error('cpf_cnpj') border-red-400 @else border-gray-200 focus:border-yellow-400 @enderror">
                            @error('cpf_cnpj')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        {{-- WhatsApp + E-mail --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="whatsapp" class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">WhatsApp *</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-300">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    </span>
                                    <input id="whatsapp" name="whatsapp" type="tel" value="{{ old('whatsapp') }}" required
                                           @input="maskWhatsapp($event)"
                                           maxlength="16"
                                           class="w-full pl-10 pr-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors @error('whatsapp') border-red-400 @else border-gray-200 focus:border-yellow-400 @enderror"
                                           placeholder="(11) 99999-9999">
                                </div>
                                @error('whatsapp')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">E-mail *</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-300">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    </span>
                                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                                           class="w-full pl-10 pr-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors @error('email') border-red-400 @else border-gray-200 focus:border-yellow-400 @enderror"
                                           placeholder="seu@email.com">
                                </div>
                                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Instagram + Facebook --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="instagram_url" class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">
                                    Instagram *
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2" style="color:#e1306c;">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                    </span>
                                    <input id="instagram_url" name="instagram_url" type="url" value="{{ old('instagram_url') }}" required
                                           class="w-full pl-10 pr-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors @error('instagram_url') border-red-400 @else border-gray-200 focus:border-yellow-400 @enderror"
                                           placeholder="https://instagram.com/sujaloja">
                                </div>
                                @error('instagram_url')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="facebook_url" class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">
                                    Facebook <span class="font-normal text-gray-400">(opcional)</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2" style="color:#1877f2;">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                    </span>
                                    <input id="facebook_url" name="facebook_url" type="url" value="{{ old('facebook_url') }}"
                                           class="w-full pl-10 pr-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors @error('facebook_url') border-red-400 @else border-gray-200 focus:border-yellow-400 @enderror"
                                           placeholder="https://facebook.com/sujaloja">
                                </div>
                                @error('facebook_url')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Dados Bancários e PIX --}}
                        <div class="border-t-2 border-dashed border-gray-100 pt-5">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                                     style="background:#E8A000;">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold" style="color:#3D3000;">Dados para Pagamento</p>
                                    <p class="text-xs text-gray-400">Necessários para o processamento do split de pagamentos</p>
                                </div>
                            </div>

                            {{-- PIX (obrigatório) --}}
                            <div class="p-4 rounded-xl border-2 mb-4" style="border-color:#F4E294; background:#FEFCE8;">
                                <p class="text-xs font-bold mb-3 flex items-center gap-1.5" style="color:#92400E;">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                    </svg>
                                    Chave PIX *
                                </p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="pix_tipo" class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">Tipo da Chave *</label>
                                        <select id="pix_tipo" name="pix_tipo" required
                                                class="w-full px-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors @error('pix_tipo') border-red-400 @else border-gray-200 focus:border-yellow-400 @enderror">
                                            <option value="">Selecione...</option>
                                            <option value="CPF"       {{ old('pix_tipo') === 'CPF'       ? 'selected' : '' }}>CPF</option>
                                            <option value="CNPJ"      {{ old('pix_tipo') === 'CNPJ'      ? 'selected' : '' }}>CNPJ</option>
                                            <option value="email"     {{ old('pix_tipo') === 'email'     ? 'selected' : '' }}>E-mail</option>
                                            <option value="telefone"  {{ old('pix_tipo') === 'telefone'  ? 'selected' : '' }}>Telefone</option>
                                            <option value="aleatoria" {{ old('pix_tipo') === 'aleatoria' ? 'selected' : '' }}>Chave Aleatória</option>
                                        </select>
                                        @error('pix_tipo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="pix_chave" class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">Chave PIX *</label>
                                        <input id="pix_chave" name="pix_chave" type="text" value="{{ old('pix_chave') }}" required
                                               class="w-full px-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors @error('pix_chave') border-red-400 @else border-gray-200 focus:border-yellow-400 @enderror"
                                               placeholder="Ex.: 11999887766 / email@exemplo.com">
                                        @error('pix_chave')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Dados bancários (expansível, opcional) --}}
                            <div x-data="{ showBank: {{ old('banco_nome') ? 'true' : 'false' }} }">
                                <button type="button" @click="showBank = !showBank"
                                        class="flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors mb-3">
                                    <svg class="w-4 h-4 transition-transform duration-200" :class="showBank ? 'rotate-90' : ''"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    <span x-text="showBank ? 'Ocultar dados bancários' : '+ Informar conta bancária (opcional)'"></span>
                                </button>

                                <div x-show="showBank" x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 -translate-y-2"
                                     x-transition:enter-end="opacity-100 translate-y-0"
                                     class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl bg-gray-50 border border-gray-200">
                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">Nome do Banco</label>
                                        <input name="banco_nome" type="text" value="{{ old('banco_nome') }}"
                                               class="w-full px-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors border-gray-200 focus:border-yellow-400"
                                               placeholder="Ex.: Nubank, Itaú, Bradesco, Caixa Econômica...">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">Agência</label>
                                        <input name="banco_agencia" type="text" value="{{ old('banco_agencia') }}"
                                               class="w-full px-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors border-gray-200 focus:border-yellow-400"
                                               placeholder="0001">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">Número da Conta</label>
                                        <input name="banco_conta" type="text" value="{{ old('banco_conta') }}"
                                               class="w-full px-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors border-gray-200 focus:border-yellow-400"
                                               placeholder="12345-6">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">Tipo de Conta</label>
                                        <select name="banco_tipo_conta"
                                                class="w-full px-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors border-gray-200 focus:border-yellow-400">
                                            <option value="">Selecione...</option>
                                            <option value="corrente"  {{ old('banco_tipo_conta') === 'corrente'  ? 'selected' : '' }}>Conta Corrente</option>
                                            <option value="poupanca"  {{ old('banco_tipo_conta') === 'poupanca'  ? 'selected' : '' }}>Conta Poupança</option>
                                            <option value="pagamento" {{ old('banco_tipo_conta') === 'pagamento' ? 'selected' : '' }}>Conta de Pagamento</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Eixos de atuação --}}
                        <div>
                            <label class="block text-sm font-semibold mb-2" style="color:#3D3000;">
                                Em quais eixos você atua? <span class="font-normal text-gray-400">(marque todos que se aplicam)</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                @foreach([
                                    ['value'=>'produto', 'emoji'=>'🛍️', 'label'=>'Produtos',            'sub'=>'Artesanato, livros, alimentação, moda...'],
                                    ['value'=>'servico', 'emoji'=>'🎯', 'label'=>'Serviços',            'sub'=>'Design, aulas, consultorias, comunicação...'],
                                    ['value'=>'cuidado', 'emoji'=>'🌿', 'label'=>'Cuidados & Bem Viver','sub'=>'Massagens, terapias, práticas integrativas...'],
                                ] as $eixo)
                                @php $checked = in_array($eixo['value'], old('eixos', [])); @endphp
                                <label class="flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                                       style="{{ $checked ? 'border-color:#E8A000; background:#FFFBEB;' : 'border-color:#e5e7eb; background:#fff;' }}">
                                    <div class="pt-0.5 flex-shrink-0">
                                        <input type="checkbox" name="eixos[]" value="{{ $eixo['value'] }}"
                                               {{ $checked ? 'checked' : '' }}
                                               class="w-5 h-5 rounded border-gray-300" style="accent-color:#E8A000;">
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold leading-tight" style="color:#3D3000;">
                                            {{ $eixo['emoji'] }} {{ $eixo['label'] }}
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $eixo['sub'] }}</p>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Descrição --}}
                        <div>
                            <label for="descricao" class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">
                                Conte mais sobre sua loja <span class="font-normal text-gray-400">(opcional)</span>
                            </label>
                            <textarea id="descricao" name="descricao" rows="4"
                                      class="w-full px-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors border-gray-200 resize-none"
                                      placeholder="Descreva brevemente sua proposta, produtos/serviços e diferenciais da sua loja...">{{ old('descricao') }}</textarea>
                        </div>

                        {{-- Submit --}}
                        <div class="pt-2">
                            <button type="submit"
                                    class="w-full py-4 rounded-xl font-bold text-base transition-all hover:shadow-lg hover:-translate-y-0.5"
                                    style="background:linear-gradient(135deg,#E8A000,#C47A00); color:#fff; min-height:56px;">
                                Enviar Solicitação de Expositor →
                            </button>
                            <p class="text-center text-xs mt-3 text-gray-400">
                                Nossa equipe avaliará sua solicitação em até 3 dias úteis e entrará em contato pelo WhatsApp ou e-mail informado.
                            </p>
                        </div>
                    </form>
                </div>
            </div>

        </div>
        @endif
    </div>
</main>

<script>
function expositorForm() {
    return {
        accepted: @json($errors->any()),
        docType: 'cpf',
        scrolledToBottom: false,

        checkScroll(e) {
            const el = e.target;
            this.scrolledToBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 10;
        },

        scrollContract() {
            const el = document.getElementById('contract-body');
            if (el) el.scrollTop += 200;
        },

        maskDoc(event) {
            let v = event.target.value.replace(/\D/g, '');
            if (this.docType === 'cpf') {
                v = v.slice(0, 11);
                v = v.replace(/(\d{3})(\d)/, '$1.$2');
                v = v.replace(/(\d{3})(\d)/, '$1.$2');
                v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            } else {
                v = v.slice(0, 14);
                v = v.replace(/^(\d{2})(\d)/, '$1.$2');
                v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
                v = v.replace(/(\d{4})(\d)/, '$1-$2');
            }
            event.target.value = v;
        },

        maskWhatsapp(event) {
            let v = event.target.value.replace(/\D/g, '').slice(0, 11);
            if (v.length > 7) {
                v = '(' + v.slice(0,2) + ') ' + v.slice(2,7) + '-' + v.slice(7);
            } else if (v.length > 2) {
                v = '(' + v.slice(0,2) + ') ' + v.slice(2);
            } else if (v.length > 0) {
                v = '(' + v;
            }
            event.target.value = v;
        }
    }
}
</script>
@endsection
