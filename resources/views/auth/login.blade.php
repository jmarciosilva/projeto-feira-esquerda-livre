<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Feira Esquerda Livre</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="min-h-screen font-sans antialiased flex items-center justify-center px-4 py-4"
      style="background: linear-gradient(135deg, #F4E294 0%, #FDF8DC 46%, #E8A000 100%);">

<div class="w-full max-w-md">
    <a href="{{ url('/') }}" class="mb-3 inline-flex items-center gap-2 text-sm font-bold transition-opacity hover:opacity-80" style="color:#3D3000;">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Voltar para a home
    </a>

    <div class="rounded-2xl overflow-hidden shadow-2xl border" style="background:#fff; border-color:#E8C766;">
        <div class="px-8 py-6 text-center" style="background:#3D3000;">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-lg"
                 style="background:#F4E294; color:#3D3000;">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <h1 class="text-xl font-black text-white">Feira Esquerda Livre</h1>
            <p class="text-sm mt-1" style="color:#F4E294;">Entrar na plataforma</p>
        </div>

        <div class="px-8 py-6">
            <h2 class="text-lg font-bold mb-5" style="color:#3D3000;">Entrar na conta</h2>

            @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-3.5" x-data="{ showPassword: false }">
                @csrf

                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">E-mail</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="w-full min-h-11 px-3.5 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 @error('email') border-red-400 bg-red-50 @enderror"
                        style="border-color:#D6B85A; --tw-ring-color:#E8A000;"
                        placeholder="admin@feiraesquerdalivre.com.br"
                    >
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">Senha</label>
                    <div class="relative">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            name="password"
                            required
                            class="w-full min-h-11 pl-3.5 pr-12 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2"
                            style="border-color:#D6B85A; --tw-ring-color:#E8A000;"
                            placeholder="********"
                        >
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 w-11 flex items-center justify-center rounded-r-lg transition-colors"
                            style="color:#5C3000;"
                            :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                            :title="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                        >
                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.25a2.75 2.75 0 100 5.5 2.75 2.75 0 000-5.5z"/>
                            </svg>
                            <svg x-cloak x-show="showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.58 10.58A2 2 0 0012 14a2 2 0 001.42-.58"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 4.62A9.12 9.12 0 0112 4.36c6 0 9.75 7.64 9.75 7.64a17.46 17.46 0 01-2.23 3.15M6.36 6.36C3.76 8.13 2.25 12 2.25 12s3.75 7.64 9.75 7.64a9.4 9.4 0 004.22-1"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded" style="color:#C47A00; border-color:#D6B85A;">
                    <span class="text-sm" style="color:#5C3000;">Lembrar-me</span>
                </label>

                <button
                    type="submit"
                    class="w-full min-h-11 px-4 rounded-lg text-sm font-bold text-white transition-colors duration-150"
                    style="background:#C47A00;"
                    onmouseover="this.style.backgroundColor='#A86400'"
                    onmouseout="this.style.backgroundColor='#C47A00'"
                >
                    Entrar no Painel
                </button>
            </form>

            <div class="mt-5 p-3.5 rounded-xl text-center" style="background:#FDF8DC; border:1px solid #F4E294;">
                <p class="text-sm" style="color:#5C3000;">
                    Nao tem conta?
                    <a href="{{ route('register') }}" class="font-bold hover:underline" style="color:#3D3000;">Cadastre-se</a>
                </p>
            </div>
        </div>
    </div>

    <p class="text-center text-xs mt-4 font-medium" style="color:#5C3000;">&copy; {{ date('Y') }} Feira Esquerda Livre</p>
</div>

</body>
</html>
