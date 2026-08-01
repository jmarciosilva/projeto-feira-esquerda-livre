@props(['title' => null, 'description' => null])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-[#E8DFA8] shadow-sm']) }}>
    @if($title)
    <div class="px-6 py-4 border-b border-[#F1E6AE]">
        <h3 class="text-base font-bold" style="color:#3D3000;">{{ $title }}</h3>
        @if($description)
        <p class="text-sm mt-0.5" style="color:#7A5C00;">{{ $description }}</p>
        @endif
    </div>
    @endif
    <div class="p-6">
        {{ $slot }}
    </div>
</div>
