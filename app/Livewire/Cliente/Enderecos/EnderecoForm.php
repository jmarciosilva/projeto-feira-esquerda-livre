<?php

namespace App\Livewire\Cliente\Enderecos;

use App\Models\CustomerAddress;
use Illuminate\View\View;
use Livewire\Component;

class EnderecoForm extends Component
{
    public ?CustomerAddress $endereco = null;

    public string $label       = '';
    public string $cep         = '';
    public string $rua         = '';
    public string $numero      = '';
    public string $complemento = '';
    public string $bairro      = '';
    public string $cidade      = '';
    public string $estado      = '';
    public bool   $is_default  = false;

    public function mount(?CustomerAddress $endereco = null): void
    {
        if ($endereco && $endereco->exists) {
            abort_unless($endereco->user_id === auth()->id(), 403);

            $this->endereco    = $endereco;
            $this->label       = $endereco->label;
            $this->cep         = $endereco->cep;
            $this->rua         = $endereco->rua;
            $this->numero      = $endereco->numero;
            $this->complemento = $endereco->complemento ?? '';
            $this->bairro      = $endereco->bairro;
            $this->cidade      = $endereco->cidade;
            $this->estado      = $endereco->estado;
            $this->is_default  = $endereco->is_default;
        }
    }

    public function save(): void
    {
        $this->validate([
            'label'       => 'required|string|max:50',
            'cep'         => 'required|string|max:9',
            'rua'         => 'required|string|max:255',
            'numero'      => 'required|string|max:20',
            'complemento' => 'nullable|string|max:255',
            'bairro'      => 'required|string|max:255',
            'cidade'      => 'required|string|max:255',
            'estado'      => 'required|string|size:2',
        ]);

        $data = [
            'label'       => $this->label,
            'cep'         => $this->cep,
            'rua'         => $this->rua,
            'numero'      => $this->numero,
            'complemento' => $this->complemento ?: null,
            'bairro'      => $this->bairro,
            'cidade'      => $this->cidade,
            'estado'      => strtoupper($this->estado),
            'is_default'  => $this->is_default,
        ];

        if ($this->is_default) {
            auth()->user()->addresses()->update(['is_default' => false]);
        }

        if ($this->endereco && $this->endereco->exists) {
            $this->endereco->update($data);
            session()->flash('success', 'Endereço atualizado.');
        } else {
            auth()->user()->addresses()->create($data);
            session()->flash('success', 'Endereço cadastrado.');
        }

        $this->redirect(route('cliente.enderecos.index'), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.cliente.enderecos.endereco-form')
            ->layout('cliente.layouts.app', ['title' => $this->endereco ? 'Editar Endereço' : 'Novo Endereço']);
    }
}
