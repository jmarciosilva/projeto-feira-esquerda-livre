@extends('layouts.public')

@section('title', 'Cancelar Inscrição — Feira Esquerda Livre')

@section('content')
<main class="max-w-lg mx-auto px-4 sm:px-6 py-16 text-center">

    @if(session('success'))
    <div class="text-5xl mb-6">✅</div>
    <h1 class="text-2xl font-extrabold mb-3" style="color:#1a472a;">Inscrição cancelada</h1>
    <p class="text-gray-600 mb-8">{{ session('success') }}</p>
    @elseif(session('error'))
    <div class="text-5xl mb-6">⚠️</div>
    <h1 class="text-2xl font-extrabold mb-3 text-gray-800">Link inválido</h1>
    <p class="text-gray-500 mb-8">{{ session('error') }}</p>
    @else
    <div class="text-5xl mb-6">📧</div>
    <h1 class="text-2xl font-extrabold mb-3" style="color:#1a472a;">Cancelar inscrição</h1>
    <p class="text-gray-600 mb-2">Tem certeza que deseja parar de receber e-mails da Feira Esquerda Livre?</p>
    <p class="text-sm text-gray-400 mb-8">E-mail: <strong>{{ $email ?? '' }}</strong></p>

    <form method="POST" action="{{ route('newsletter.unsubscribe.confirm') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">
        <input type="hidden" name="campaign" value="{{ $campaignId }}">
        <button type="submit"
                class="px-8 py-3 rounded-xl font-bold text-sm"
                style="background:#dc2626; color:#fff;">
            Sim, cancelar minha inscrição
        </button>
    </form>
    @endif

    <p class="mt-8 text-xs text-gray-400">
        <a href="{{ url('/') }}" style="color:#1a472a; font-weight:600;">← Voltar à Feira Esquerda Livre</a>
    </p>

</main>
@endsection
