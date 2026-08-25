<?php

namespace App\Livewire\Admin\CustomerIntelligence;

use Livewire\Component;

class DashboardShow extends Component
{
    public function mount(): void
    {
        $this->authorize('customer_intelligence.visualizar');
    }

    public function render()
    {
        return view('livewire.admin.customer-intelligence.dashboard-show')
            ->layout('admin.layouts.app', ['title' => 'Inteligência de Cliente']);
    }
}
