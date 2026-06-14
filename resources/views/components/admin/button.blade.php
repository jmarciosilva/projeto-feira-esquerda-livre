@props(['variant' => 'primary', 'size' => 'md', 'type' => 'button'])

@php
$variants = [
    'primary'   => 'bg-[#1a472a] hover:bg-[#2d6a4f] text-white border-transparent',
    'secondary' => 'bg-white hover:bg-gray-50 text-gray-700 border-gray-300',
    'danger'    => 'bg-red-600 hover:bg-red-700 text-white border-transparent',
    'success'   => 'bg-[#52b788] hover:bg-[#2d6a4f] text-white border-transparent',
    'ghost'     => 'bg-transparent hover:bg-gray-100 text-gray-600 border-transparent',
];

$sizes = [
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-6 py-3 text-base',
];

$cls = ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "inline-flex items-center gap-2 font-medium border rounded-lg transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed $cls"]) }}
>
    {{ $slot }}
</button>
