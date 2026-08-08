<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('admin.customer-intelligence.dashboard') }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium hover:underline"
           style="color:#7A5C00;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Voltar para Inteligência de Cliente
        </a>
    </div>

    <x-admin.card>
        <div class="jmf-docs">
            {!! $html !!}
        </div>
    </x-admin.card>
</div>

<style>
    .jmf-docs {
        color: #4A3B00;
        font-size: 0.9375rem;
        line-height: 1.7;
    }
    .jmf-docs h1 {
        color: #3D3000;
        font-size: 1.75rem;
        font-weight: 800;
        margin: 0 0 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #F1E6AE;
    }
    .jmf-docs h2 {
        color: #3D3000;
        font-size: 1.375rem;
        font-weight: 700;
        margin: 2rem 0 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #F1E6AE;
    }
    .jmf-docs h3 {
        color: #3D3000;
        font-size: 1.125rem;
        font-weight: 700;
        margin: 1.5rem 0 0.75rem;
    }
    .jmf-docs h4 {
        color: #5C3000;
        font-size: 1rem;
        font-weight: 700;
        margin: 1.25rem 0 0.5rem;
    }
    .jmf-docs p, .jmf-docs ul, .jmf-docs ol {
        margin: 0 0 1rem;
    }
    .jmf-docs ul, .jmf-docs ol {
        padding-left: 1.5rem;
    }
    .jmf-docs li {
        margin: 0.25rem 0;
    }
    .jmf-docs li > ul, .jmf-docs li > ol {
        margin: 0.25rem 0;
    }
    .jmf-docs a {
        color: #B8860B;
        text-decoration: underline;
        text-underline-offset: 2px;
    }
    .jmf-docs a:hover {
        color: #7A5C00;
    }
    .jmf-docs strong {
        color: #3D3000;
        font-weight: 700;
    }
    .jmf-docs code {
        background: #FFF4CC;
        color: #5C3000;
        padding: 0.125rem 0.375rem;
        border-radius: 0.25rem;
        font-size: 0.8125rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    }
    .jmf-docs pre {
        background: #3D3000;
        color: #F4E294;
        padding: 1rem 1.25rem;
        border-radius: 0.75rem;
        overflow-x: auto;
        margin: 0 0 1.25rem;
    }
    .jmf-docs pre code {
        background: transparent;
        color: inherit;
        padding: 0;
        font-size: 0.8125rem;
    }
    .jmf-docs blockquote {
        border-left: 4px solid #F4E294;
        background: #FFFBEB;
        padding: 0.75rem 1rem;
        margin: 0 0 1.25rem;
        color: #7A5C00;
    }
    .jmf-docs table {
        width: 100%;
        border-collapse: collapse;
        margin: 0 0 1.25rem;
        font-size: 0.875rem;
    }
    .jmf-docs th, .jmf-docs td {
        border: 1px solid #E8DFA8;
        padding: 0.5rem 0.75rem;
        text-align: left;
    }
    .jmf-docs th {
        background: #FFF4CC;
        color: #3D3000;
        font-weight: 700;
    }
    .jmf-docs hr {
        border: none;
        border-top: 1px solid #F1E6AE;
        margin: 2rem 0;
    }
</style>
