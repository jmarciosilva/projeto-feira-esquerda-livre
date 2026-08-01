<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar conta - Feira Esquerda Livre</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased flex items-center justify-center px-4 py-8"
      style="background: linear-gradient(135deg, #F4E294 0%, #FDF8DC 46%, #E8A000 100%);">

<div class="w-full max-w-md">
    <a href="{{ url('/') }}" class="mb-5 inline-flex items-center gap-2 text-sm font-bold transition-opacity hover:opacity-80" style="color:#3D3000;">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Voltar para a home
    </a>

    <div class="rounded-2xl overflow-hidden shadow-2xl border" style="background:#fff; border-color:#E8C766;">
        <div class="px-8 py-8 text-center" style="background:#3D3000;">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg"
                 style="background:#F4E294; color:#3D3000;">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-black text-white">Feira Esquerda Livre</h1>
            <p class="text-sm mt-1" style="color:#F4E294;">Criar minha conta</p>
        </div>

        <div class="px-8 py-8">
            <h2 class="text-xl font-bold mb-2" style="color:#3D3000;">Cadastro rapido</h2>
            <p class="text-sm mb-6" style="color:#5C3000;">Leva menos de um minuto. Depois voce escolhe se quer retirar o pedido ou receber em casa.</p>

            @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">Nome completo</label>
                    <input type="text" name="name" value="{{ old('name') }}" required autofocus
                           class="w-full px-4 py-3 border rounded-xl text-base focus:outline-none focus:ring-2"
                           style="border-color:#D6B85A; --tw-ring-color:#E8A000;"
                           placeholder="Seu nome">
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">E-mail</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full px-4 py-3 border rounded-xl text-base focus:outline-none focus:ring-2"
                           style="border-color:#D6B85A; --tw-ring-color:#E8A000;"
                           placeholder="voce@email.com">
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">WhatsApp</label>
                    <input type="text" name="whatsapp" value="{{ old('whatsapp') }}" required
                           class="w-full px-4 py-3 border rounded-xl text-base focus:outline-none focus:ring-2"
                           style="border-color:#D6B85A; --tw-ring-color:#E8A000;"
                           placeholder="(11) 91234-5678">
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">Senha</label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-3 border rounded-xl text-base focus:outline-none focus:ring-2"
                           style="border-color:#D6B85A; --tw-ring-color:#E8A000;"
                           placeholder="Minimo 8 caracteres">
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:#3D3000;">Confirmar senha</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full px-4 py-3 border rounded-xl text-base focus:outline-none focus:ring-2"
                           style="border-color:#D6B85A; --tw-ring-color:#E8A000;"
                           placeholder="Repita a senha">
                </div>

                <button type="submit"
                        class="w-full min-h-12 px-4 rounded-xl text-base font-bold text-white transition-colors duration-150"
                        style="background:#C47A00;"
                        onmouseover="this.style.backgroundColor='#A86400'"
                        onmouseout="this.style.backgroundColor='#C47A00'">
                    Criar minha conta
                </button>
            </form>

            <div class="mt-6 p-4 rounded-xl text-center" style="background:#FDF8DC; border:1px solid #F4E294;">
                <p class="text-sm" style="color:#5C3000;">
                    Ja tem conta?
                    <a href="{{ route('login') }}" class="font-bold hover:underline" style="color:#3D3000;">Entrar</a>
                </p>
            </div>
        </div>
    </div>

    <p class="text-center text-xs mt-6 font-medium" style="color:#5C3000;">&copy; {{ date('Y') }} Feira Esquerda Livre</p>
</div>

</body>
</html>
