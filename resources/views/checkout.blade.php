@extends('layouts.public')

@section('title', 'Finalizar Compra — Feira Esquerda Livre')

@section('content')
@php $settings = App\Models\SiteSetting::instance(); @endphp

<main class="max-w-4xl mx-auto px-4 sm:px-6 py-10">
    <h1 class="text-2xl sm:text-3xl font-extrabold mb-6" style="color:#3D3000;">Finalizar Compra</h1>

    <livewire:checkout />
</main>
@endsection
