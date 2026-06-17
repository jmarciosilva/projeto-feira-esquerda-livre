<?php

namespace App\Livewire\Cliente\Enderecos;

use App\Models\CustomerAddress;
use Illuminate\View\View;
use Livewire\Component;

class EnderecoIndex extends Component
{
    private function enderecos()
    {
        return CustomerAddress::where('user_id', auth()->id());
    }

    public function setDefault(int $id): void
    {
        $this->enderecos()->update(['is_default' => false]);
        $this->enderecos()->where('id', $id)->update(['is_default' => true]);
    }

    public function delete(int $id): void
    {
        $this->enderecos()->where('id', $id)->delete();
        session()->flash('success', 'Endereço removido.');
    }

    public function render(): View
    {
        $enderecos = $this->enderecos()->orderByDesc('is_default')->orderByDesc('created_at')->get();

        return view('livewire.cliente.enderecos.endereco-index', compact('enderecos'))
            ->layout('cliente.layouts.app', ['title' => 'Meus Endereços']);
    }
}
