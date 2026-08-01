@props(['variant' => 'primary', 'size' => 'md', 'type' => 'button'])

@php
$variants = [
    'primary'   => 'bg-[#C47A00] hover:bg-[#A86400] text-white border-transparent',
    'secondary' => 'bg-[#FDF8DC] hover:bg-[#F4E294] text-[#3D3000] border-[#E8DFA8]',
    'danger'    => 'bg-red-600 hover:bg-red-700 text-white border-transparent',
    'success'   => 'bg-[#5C8A3C] hover:bg-[#4A7030] text-white border-transparent',
    'ghost'     => 'bg-transparent hover:bg-[#FDF8DC] text-[#5C3000] border-transparent',
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
