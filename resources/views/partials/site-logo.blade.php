@php
    if (!isset($settings) || !$settings) {
        $settings = App\Models\SiteSetting::instance();
    }
@endphp
@if($settings->logo_path)
    <img src="{{ Storage::url($settings->logo_path) }}"
         alt="{{ $settings->site_name ?? 'Feira Esquerda Livre' }}"
         class="h-9 w-auto object-contain">
@else
    <div class="w-9 h-9 rounded-lg flex items-center justify-center font-bold text-sm flex-shrink-0"
         style="background-color: var(--amarelo-escuro, #E8A000); color: #fff;">
        FEL
    </div>
@endif
