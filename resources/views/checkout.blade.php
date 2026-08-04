@extends('layouts.public')

@section('title', 'Finalizar Compra — Feira Esquerda Livre')

@section('content')
@php $settings = App\Models\SiteSetting::instance(); @endphp

<main class="px-4 sm:px-6 py-8 sm:py-12" style="background:#FAFAF7; min-height:calc(100vh - 72px);">
    <livewire:checkout />
</main>
@endsection
