<?php

namespace App\Livewire\Lojista;

use App\Livewire\Concerns\ValidatesFileUploads;
use App\Models\Expositor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class LojaForm extends Component
{
    use ValidatesFileUploads, WithFileUploads;

    public string $description = '';

    public string $whatsapp = '';

    public string $instagram_url = '';

    public string $facebook_url = '';

    public string $website_url = '';

    public string $zipcode = '';

    public string $street = '';

    public string $number = '';

    public string $district = '';

    public string $city = '';

    public string $state = '';

    public string $slug = '';

    public ?string $logo_path = null;

    public ?string $image_path = null;

    public $logo_upload = null;

    public $banner_upload = null;

    public array $eixos = [];

    // Dados bancários
    public string $banco_nome = '';

    public string $banco_agencia = '';

    public string $banco_conta = '';

    public string $banco_tipo_conta = '';

    public string $pix_tipo = '';

    public string $pix_chave = '';

    public array $tiposPix = [
        '' => 'Selecione...',
        'CPF' => 'CPF',
        'CNPJ' => 'CNPJ',
        'email' => 'E-mail',
        'telefone' => 'Telefone',
        'aleatoria' => 'Chave Aleatória',
    ];

    public array $tiposContaBanco = [
        '' => 'Selecione...',
        'corrente' => 'Conta Corrente',
        'poupanca' => 'Conta Poupança',
        'pagamento' => 'Conta de Pagamento',
    ];

    public array $brazilStates = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
        'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
        'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];

    public function mount(): void
    {
        $expositor = auth()->user()->expositor;

        if ($expositor) {
            $this->description = $expositor->description ?? '';
            $this->whatsapp = $expositor->whatsapp ?? '';
            $this->instagram_url = $expositor->instagram_url ?? '';
            $this->facebook_url = $expositor->facebook_url ?? '';
            $this->website_url = $expositor->website_url ?? '';
            $this->zipcode = $expositor->zipcode ?? '';
            $this->street = $expositor->street ?? '';
            $this->number = $expositor->number ?? '';
            $this->district = $expositor->district ?? '';
            $this->city = $expositor->city ?? '';
            $this->state = $expositor->state ?? '';
            $this->slug = $expositor->slug ?? '';
            $this->logo_path = $expositor->logo_path;
            $this->image_path = $expositor->image_path;
            $this->eixos = $expositor->eixos ?? [];
            $this->banco_nome = $expositor->banco_nome ?? '';
            $this->banco_agencia = $expositor->banco_agencia ?? '';
            $this->banco_conta = $expositor->banco_conta ?? '';
            $this->banco_tipo_conta = $expositor->banco_tipo_conta ?? '';
            $this->pix_tipo = $expositor->pix_tipo ?? '';
            $this->pix_chave = $expositor->pix_chave ?? '';
        }
    }

    public function save(): void
    {
        if (! $this->checkUploadedFile($this->logo_upload, 2048, 'logo_upload')) {
            return;
        }
        if (! $this->checkUploadedFile($this->banner_upload, 4096, 'banner_upload')) {
            return;
        }

        $this->validate([
            'description' => 'nullable|string|max:2000',
            'whatsapp' => 'nullable|string|max:20',
            'instagram_url' => 'nullable|url|max:500',
            'facebook_url' => 'nullable|url|max:500',
            'website_url' => 'nullable|url|max:500',
            'zipcode' => 'nullable|string|max:9',
            'street' => 'nullable|string|max:255',
            'number' => 'nullable|string|max:20',
            'district' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
            'slug' => 'nullable|string|max:255',
            'logo_upload' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp,svg',
            'banner_upload' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp,svg',
            'banco_nome' => 'nullable|string|max:100',
            'banco_agencia' => 'nullable|string|max:20',
            'banco_conta' => 'nullable|string|max:30',
            'banco_tipo_conta' => 'nullable|string|max:20',
            'pix_tipo' => 'nullable|string|max:20',
            'pix_chave' => 'nullable|string|max:255',
        ]);

        $expositor = auth()->user()->expositor;

        if (! $expositor) {
            session()->flash('error', 'Nenhuma loja vinculada à sua conta.');

            return;
        }

        $data = [
            'description' => $this->description,
            'whatsapp' => $this->whatsapp,
            'instagram_url' => $this->instagram_url,
            'facebook_url' => $this->facebook_url,
            'website_url' => $this->website_url,
            'zipcode' => $this->zipcode ?: null,
            'street' => $this->street ?: null,
            'number' => $this->number ?: null,
            'district' => $this->district ?: null,
            'city' => $this->city,
            'state' => strtoupper($this->state),
            'eixos' => $this->eixos,
            'banco_nome' => $this->banco_nome ?: null,
            'banco_agencia' => $this->banco_agencia ?: null,
            'banco_conta' => $this->banco_conta ?: null,
            'banco_tipo_conta' => $this->banco_tipo_conta ?: null,
            'pix_tipo' => $this->pix_tipo ?: null,
            'pix_chave' => $this->pix_chave ?: null,
        ];

        if ($this->slug && $this->slug !== $expositor->slug) {
            $candidate = Str::slug($this->slug);
            if (! Expositor::where('slug', $candidate)->where('id', '!=', $expositor->id)->exists()) {
                $data['slug'] = $candidate;
            }
        }

        if ($this->logo_upload) {
            if ($this->logo_path) {
                Storage::disk('public')->delete($this->logo_path);
            }
            $data['logo_path'] = $this->logo_upload->store('expositores/logos', 'public');
        }

        if ($this->banner_upload) {
            if ($this->image_path) {
                Storage::disk('public')->delete($this->image_path);
            }
            $data['image_path'] = $this->banner_upload->store('expositores/banners', 'public');
        }

        $expositor->update($data);

        $this->logo_path = $expositor->fresh()->logo_path;
        $this->image_path = $expositor->fresh()->image_path;
        $this->slug = $expositor->fresh()->slug;

        session()->flash('success', 'Perfil da loja atualizado com sucesso!');
    }

    public function render(): View
    {
        return view('livewire.lojista.loja-form', [
            'expositor' => auth()->user()->expositor,
        ])->layout('lojista.layouts.app', ['title' => 'Minha Loja']);
    }
}
