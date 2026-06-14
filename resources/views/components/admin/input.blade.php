@props(['label' => null, 'error' => null, 'hint' => null])

<div {{ $attributes->only('class') }}>
    @if($label)
    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    @endif

    <input
        {{ $attributes->except('class')->merge(['class' => 'w-full px-3 py-2 border rounded-lg text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#52b788] focus:border-[#52b788] transition-colors ' . ($error ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-white')]) }}
    >

    @if($hint && !$error)
    <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    @endif

    @if($error)
    <p class="mt-1 text-xs text-red-600">{{ $error }}</p>
    @endif
</div>
