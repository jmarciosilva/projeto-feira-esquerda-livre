@props(['color' => 'gray'])

@php
$colors = [
    'green'  => 'bg-green-100 text-green-800',
    'red'    => 'bg-red-100 text-red-800',
    'yellow' => 'bg-yellow-100 text-yellow-800',
    'blue'   => 'bg-blue-100 text-blue-800',
    'gray'   => 'bg-gray-100 text-gray-700',
    'brand'  => 'bg-[#b7e4c7] text-[#1a472a]',
];
$cls = $colors[$color] ?? $colors['gray'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium $cls"]) }}>
    {{ $slot }}
</span>
