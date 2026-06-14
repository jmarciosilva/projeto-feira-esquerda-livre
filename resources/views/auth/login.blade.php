<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Feira Esquerda Livre</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased flex items-center justify-center min-h-screen bg-gradient-to-br from-[#0f2d1a] via-[#1a472a] to-[#2d6a4f]">

<div class="w-full max-w-md px-4">
    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
        {{-- Header --}}
        <div class="bg-[#1a472a] px-8 py-8 text-center">
            <div class="w-16 h-16 bg-[#52b788] rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-white">Feira Esquerda Livre</h1>
            <p class="text-sm text-[#b7e4c7] mt-1">Painel Administrativo</p>
        </div>

        {{-- Form --}}
        <div class="px-8 py-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">Entrar na conta</h2>

            @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788] focus:border-[#52b788] @error('email') border-red-400 bg-red-50 @enderror"
                        placeholder="admin@feiraesquerdalivre.com.br"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <input
                        type="password"
                        name="password"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788] focus:border-[#52b788]"
                        placeholder="••••••••"
                    >
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-[#1a472a] rounded border-gray-300 focus:ring-[#52b788]">
                        <span class="text-sm text-gray-600">Lembrar-me</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="w-full bg-[#1a472a] hover:bg-[#2d6a4f] text-white font-medium py-2.5 px-4 rounded-lg transition-colors duration-150 text-sm"
                >
                    Entrar no Painel
                </button>
            </form>

            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 text-center font-medium">Acesso restrito a administradores</p>
            </div>
        </div>
    </div>

    <p class="text-center text-xs text-white/50 mt-6">© {{ date('Y') }} Feira Esquerda Livre</p>
</div>

</body>
</html>
