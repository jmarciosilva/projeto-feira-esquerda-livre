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
[x-cloak] { display: none !important; }
.nav-link {
    color: var(--texto-escuro);
    font-weight: 600;
    font-size: 0.9375rem;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    transition: background-color 0.2s, color 0.2s;
}
.nav-link:hover {
    background-color: rgba(61,48,0,0.1);
    color: #000;
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
.section-title {
    font-size: clamp(1.75rem, 3vw, 2.5rem);
    line-height: 1.15;
    font-weight: 900;
    color: {{ $cd }};
    letter-spacing: 0;
}
.section-subtitle {
    max-width: 42rem;
    margin-left: auto;
    margin-right: auto;
    color: #5C4000;
    font-size: 1rem;
    line-height: 1.7;
}
</style>
