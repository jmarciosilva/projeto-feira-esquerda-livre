@extends('layouts.public')

@section('title', 'Contato - Feira Esquerda Livre')
@section('description', 'Fale com a Feira Esquerda Livre. Telefones, e-mail, endereço e formulário de contato.')

@section('content')
@php
    $settings = $settings ?? App\Models\SiteSetting::instance();
    $whatsappDigits = preg_replace('/\D/', '', (string) $settings->whatsapp);
@endphp

<main style="background:#FDF8DC;">
    <section class="py-12 md:py-16 xl:py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <div class="w-12 h-1.5 rounded-full mx-auto mb-4" style="background-color:#E8A000;"></div>
                <h1 class="section-title inline-flex items-center justify-center gap-3">
                    @if($settings->logo_path)
                        <img src="{{ Storage::url($settings->logo_path) }}"
                             alt=""
                             class="h-9 w-auto object-contain"
                             loading="lazy">
                    @else
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-black"
                              style="background:#F4E294; color:#3D3000;">F</span>
                    @endif
                    <span>Fale com a Feira</span>
                </h1>
                <p class="section-subtitle">
                    Tire dúvidas, envie sugestões ou fale com nossa equipe sobre feiras, pedidos, expositores e parcerias.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
                <aside class="lg:col-span-1 space-y-4">
                    <div class="bg-white rounded-2xl border shadow-sm p-6" style="border-color:#F0D060;">
                        <h2 class="text-xl font-black mb-5" style="color:#3D3000;">Canais de atendimento</h2>

                        <div class="space-y-5">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-widest mb-1" style="color:#7A5C00;">Telefone / WhatsApp</p>
                                @if($settings->whatsapp)
                                    <a href="https://wa.me/55{{ $whatsappDigits }}"
                                       target="_blank"
                                       class="font-semibold hover:opacity-80"
                                       style="color:#1A1A1A;">
                                        {{ $settings->whatsapp }}
                                    </a>
                                @else
                                    <p class="text-sm text-gray-500">Telefone ainda não cadastrado.</p>
                                @endif
                            </div>

                            <div>
                                <p class="text-xs font-bold uppercase tracking-widest mb-1" style="color:#7A5C00;">E-mail</p>
                                @if($settings->email)
                                    <a href="mailto:{{ $settings->email }}"
                                       class="font-semibold break-all hover:opacity-80"
                                       style="color:#1A1A1A;">
                                        {{ $settings->email }}
                                    </a>
                                @else
                                    <p class="text-sm text-gray-500">E-mail ainda não cadastrado.</p>
                                @endif
                            </div>

                            <div>
                                <p class="text-xs font-bold uppercase tracking-widest mb-1" style="color:#7A5C00;">Endereço</p>
                                @if($settings->address)
                                    <p class="text-sm leading-relaxed" style="color:#1A1A1A;">{{ $settings->address }}</p>
                                @else
                                    <p class="text-sm text-gray-500">Endereço ainda não cadastrado.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl p-6" style="background:#3D3000;">
                        <p class="text-sm font-bold mb-2" style="color:#F4E294;">Atendimento da feira</p>
                        <p class="text-sm leading-relaxed" style="color:#fff7cc;">
                            Sua mensagem será encaminhada para a equipe responsável. Responderemos pelo e-mail ou telefone informado no formulário.
                        </p>
                    </div>
                </aside>

                <section class="lg:col-span-2 bg-white rounded-2xl border shadow-sm overflow-hidden" style="border-color:#F0D060;">
                    <div class="px-6 py-5 border-b" style="border-color:#F4E294;">
                        <h2 class="text-xl font-black" style="color:#3D3000;">Envie uma mensagem</h2>
                        <p class="text-sm mt-1" style="color:#5C4000;">Preencha os dados abaixo para falar com a Feira Esquerda Livre.</p>
                    </div>

                    <div class="p-6">
                        @if(session('contato_success'))
                            <div class="mb-5 rounded-xl px-4 py-3 text-sm font-semibold" style="background:#E8F4EA; color:#2D6A30;">
                                {{ session('contato_success') }}
                            </div>
                        @endif

                        @if(session('contato_error'))
                            <div class="mb-5 rounded-xl px-4 py-3 text-sm font-semibold" style="background:#FEE2E2; color:#991B1B;">
                                {{ session('contato_error') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('contato.enviar') }}" class="space-y-4">
                            @csrf
                            <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="name" class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">Nome *</label>
                                    <input id="name" name="name" type="text" value="{{ old('name') }}" required
                                           class="w-full min-h-12 px-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors @error('name') border-red-400 @else border-gray-200 focus:border-yellow-400 @enderror"
                                           placeholder="Seu nome completo">
                                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">E-mail *</label>
                                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                                           class="w-full min-h-12 px-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors @error('email') border-red-400 @else border-gray-200 focus:border-yellow-400 @enderror"
                                           placeholder="voce@email.com">
                                    @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="phone" class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">Telefone / WhatsApp</label>
                                    <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                                           class="w-full min-h-12 px-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors @error('phone') border-red-400 @else border-gray-200 focus:border-yellow-400 @enderror"
                                           placeholder="(11) 99999-9999">
                                    @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="subject" class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">Assunto *</label>
                                    <input id="subject" name="subject" type="text" value="{{ old('subject') }}" required
                                           class="w-full min-h-12 px-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors @error('subject') border-red-400 @else border-gray-200 focus:border-yellow-400 @enderror"
                                           placeholder="Como podemos ajudar?">
                                    @error('subject')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div>
                                <label for="message" class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">Mensagem *</label>
                                <textarea id="message" name="message" rows="6" required
                                          class="w-full px-4 py-3 border-2 rounded-xl text-sm focus:outline-none transition-colors resize-y @error('message') border-red-400 @else border-gray-200 focus:border-yellow-400 @enderror"
                                          placeholder="Escreva sua mensagem com o máximo de detalhes possível.">{{ old('message') }}</textarea>
                                @error('message')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
                                <p class="text-xs text-gray-500">Campos marcados com * são obrigatórios.</p>
                                <button type="submit" class="btn-primary px-8 py-3 text-base whitespace-nowrap" style="min-height:48px;">
                                    Enviar Mensagem
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </section>
</main>
@endsection
