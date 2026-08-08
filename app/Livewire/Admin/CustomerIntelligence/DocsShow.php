<?php

namespace App\Livewire\Admin\CustomerIntelligence;

use Illuminate\Support\Facades\File;
use League\CommonMark\CommonMarkConverter;
use Livewire\Component;

class DocsShow extends Component
{
    public function mount(): void
    {
        $this->authorize('customer_intelligence.visualizar');
    }

    public function render()
    {
        $path = base_path('docs/JMF_CI_INTEGRATION.md');

        $html = File::exists($path)
            ? (new CommonMarkConverter())->convert(File::get($path))->getContent()
            : '<p>Documentação não encontrada.</p>';

        return view('livewire.admin.customer-intelligence.docs-show', ['html' => $html])
            ->layout('admin.layouts.app', ['title' => 'Documentação — Inteligência de Cliente']);
    }
}
