@php
    if (!isset($settings) || !$settings) {
        $settings = App\Models\SiteSetting::instance();
    }
    $cp  = $settings->color_primary         ?? '#E8A000';
    $cpd = $settings->color_primary_dark    ?? '#C47A00';
    $cs  = $settings->color_secondary       ?? '#F4E294';
    $csl = $settings->color_secondary_light ?? '#FDF8DC';
    $cd  = $settings->color_dark            ?? '#3D3000';
@endphp
<style>
:root {
    --amarelo:        {{ $cs }};
    --amarelo-claro:  {{ $csl }};
    --amarelo-escuro: {{ $cp }};
    --amarelo-hover:  {{ $cpd }};
    --verde:          #5C8A3C;
    --verde-hover:    #4A7030;
    --texto-escuro:   {{ $cd }};
    --texto-principal:#1A1A1A;
}
.btn-primary {
    background-color: {{ $cp }};
    color: #fff;
    font-weight: 700;
    border-radius: 0.75rem;
    transition: background-color 0.2s, opacity 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}
.btn-primary:hover { background-color: {{ $cpd }}; }
.btn-secondary {
    background-color: {{ $cs }};
    color: {{ $cd }};
    font-weight: 700;
    border-radius: 0.75rem;
    transition: background-color 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}
.btn-secondary:hover { background-color: {{ $cpd }}; color: #fff; }
.btn-outline {
    border: 2px solid {{ $cp }};
    color: {{ $cpd }};
    font-weight: 700;
    border-radius: 0.75rem;
    transition: all 0.2s;
    background: transparent;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}
.btn-outline:hover { background-color: {{ $cp }}; color: #fff; }
</style>
