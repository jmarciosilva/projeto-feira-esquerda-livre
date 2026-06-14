<?php

namespace App\Livewire\Admin\Expositores;

use App\Livewire\Concerns\ValidatesFileUploads;
use App\Models\Expositor;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class ExpositoresForm extends Component
{
    use WithFileUploads, ValidatesFileUploads;

    public Expositor $expositor;

    public bool    $is_featured = false;
    public bool    $is_active   = true;
    public int     $sort_order  = 0;
    public ?string $image_path  = null;
    public $image_upload        = null;

    // Dados bancários
    public string $banco_nome       = '';
    public string $banco_agencia    = '';
    public string $banco_conta      = '';
    public string $banco_tipo_conta = '';
    public string $pix_tipo         = '';
    public string $pix_chave        = '';

    public array $tiposContaBanco = [
        ''          => 'Selecione...',
        'corrente'  => 'Conta Corrente',
        'poupanca'  => 'Conta Poupança',
        'pagamento' => 'Conta de Pagamento',
    ];

    public array $tiposPix = [
        ''          => 'Selecione...',
        'CPF'       => 'CPF',
        'CNPJ'      => 'CNPJ',
        'email'     => 'E-mail',
        'telefone'  => 'Telefone',
        'aleatoria' => 'Chave Aleatória',
    ];

    public function mount(Expositor $expositor): void
    {
        $this->expositor        = $expositor;
        $this->is_featured      = $expositor->is_featured;
        $this->is_active        = $expositor->is_active;
        $this->sort_order       = $expositor->sort_order ?? 0;
        $this->image_path       = $expositor->image_path;
        $this->banco_nome       = $expositor->banco_nome       ?? '';
        $this->banco_agencia    = $expositor->banco_agencia    ?? '';
        $this->banco_conta      = $expositor->banco_conta      ?? '';
        $this->banco_tipo_conta = $expositor->banco_tipo_conta ?? '';
        $this->pix_tipo         = $expositor->pix_tipo         ?? '';
        $this->pix_chave        = $expositor->pix_chave        ?? '';
    }

    public function removeImage(): void
    {
        if ($this->image_path) {
            Storage::disk('public')->delete($this->image_path);
        }
        $this->expositor->update(['image_path' => null]);
        $this->image_path = null;
    }

    public function save(): void
    {
        if (! $this->checkUploadedFile($this->image_upload, 4096, 'image_upload')) return;

        $this->validate([
            'sort_order'       => 'integer|min:0|max:9999',
            'image_upload'     => 'nullable|image|mimes:jpg,jpeg,png,gif,webp,svg',
            'banco_nome'       => 'nullable|string|max:100',
            'banco_agencia'    => 'nullable|string|max:20',
            'banco_conta'      => 'nullable|string|max:30',
            'banco_tipo_conta' => 'nullable|string|max:20',
            'pix_tipo'         => 'nullable|string|max:20',
            'pix_chave'        => 'nullable|string|max:255',
        ]);

        $data = [
            'is_featured'      => $this->is_featured,
            'is_active'        => $this->is_active,
            'sort_order'       => $this->sort_order,
            'banco_nome'       => $this->banco_nome       ?: null,
            'banco_agencia'    => $this->banco_agencia    ?: null,
            'banco_conta'      => $this->banco_conta      ?: null,
            'banco_tipo_conta' => $this->banco_tipo_conta ?: null,
            'pix_tipo'         => $this->pix_tipo         ?: null,
            'pix_chave'        => $this->pix_chave        ?: null,
        ];

        if ($this->image_upload) {
            if ($this->image_path) {
                Storage::disk('public')->delete($this->image_path);
            }
            $data['image_path'] = $this->image_upload->store('expositores/banners', 'public');
            $this->image_path   = $data['image_path'];
        }

        $this->expositor->update($data);
        $this->image_upload = null;
        session()->flash('success', 'Expositor atualizado com sucesso.');
    }

    public function render(): View
    {
        return view('livewire.admin.expositores.expositores-form')
            ->layout('admin.layouts.app', ['title' => 'Editar Expositor — ' . $this->expositor->name]);
    }
}
