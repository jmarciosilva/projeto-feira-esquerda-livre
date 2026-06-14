@props(['title' => null, 'description' => null])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-gray-200 shadow-sm']) }}>
    @if($title)
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-base font-semibold text-gray-900">{{ $title }}</h3>
        @if($description)
        <p class="text-sm text-gray-500 mt-0.5">{{ $description }}</p>
        @endif
    </div>
    @endif
    <div class="p-6">
        {{ $slot }}
    </div>
</div>
