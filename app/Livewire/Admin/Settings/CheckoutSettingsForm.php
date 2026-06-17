<?php

namespace App\Livewire\Admin\Settings;

use App\Models\SiteSetting;
use App\Services\SiteSettingService;
use Livewire\Component;

class CheckoutSettingsForm extends Component
{
    // Frete
    public string  $frete_modo             = 'manual';
    public string  $frete_mensagem_manual  = '';
    public string  $frete_valor_padrao     = '';
    public bool    $melhor_envio_ativo     = false;
    public string  $melhor_envio_client_id     = '';
    public string  $melhor_envio_client_secret = '';
    public string  $melhor_envio_token         = '';
    public bool    $melhor_envio_sandbox       = true;

    // Pagamento
    public string  $pagamento_modo          = 'manual';
    public string  $comissao_percentual     = '0';
    public bool    $mercado_pago_ativo      = false;
    public string  $mercado_pago_public_key    = '';
    public string  $mercado_pago_access_token  = '';
    public bool    $mercado_pago_sandbox       = true;

    public bool $saved = false;

    public function mount(): void
    {
        $s = SiteSetting::instance();

        $this->frete_modo            = $s->frete_modo ?? 'manual';
        $this->frete_mensagem_manual = $s->frete_mensagem_manual ?? '';
        $this->frete_valor_padrao    = $s->frete_valor_padrao !== null ? (string) $s->frete_valor_padrao : '';
        $this->melhor_envio_ativo    = (bool) $s->melhor_envio_ativo;
        $this->melhor_envio_client_id     = $s->melhor_envio_client_id ?? '';
        $this->melhor_envio_client_secret = $s->melhor_envio_client_secret ?? '';
        $this->melhor_envio_token         = $s->melhor_envio_token ?? '';
        $this->melhor_envio_sandbox       = (bool) ($s->melhor_envio_sandbox ?? true);

        $this->pagamento_modo        = $s->pagamento_modo ?? 'manual';
        $this->comissao_percentual   = (string) ($s->comissao_percentual ?? '0');
        $this->mercado_pago_ativo    = (bool) $s->mercado_pago_ativo;
        $this->mercado_pago_public_key   = $s->mercado_pago_public_key ?? '';
        $this->mercado_pago_access_token = $s->mercado_pago_access_token ?? '';
        $this->mercado_pago_sandbox      = (bool) ($s->mercado_pago_sandbox ?? true);
    }

    public function save(SiteSettingService $service): void
    {
        $this->validate([
            'frete_mensagem_manual'      => 'nullable|string|max:1000',
            'frete_valor_padrao'         => 'nullable|numeric|min:0',
            'melhor_envio_client_id'     => 'nullable|string|max:255',
            'melhor_envio_client_secret' => 'nullable|string|max:2000',
            'melhor_envio_token'         => 'nullable|string|max:2000',
            'comissao_percentual'        => 'required|numeric|min:0|max:100',
            'mercado_pago_public_key'    => 'nullable|string|max:255',
            'mercado_pago_access_token'  => 'nullable|string|max:2000',
        ]);

        // MVP: integrações reais permanecem desativadas — os campos abaixo
        // só ficam disponíveis para ativação manual em uma fase futura.
        $service->save([
            'frete_modo'                 => 'manual',
            'frete_mensagem_manual'      => $this->frete_mensagem_manual ?: null,
            'frete_valor_padrao'         => $this->frete_valor_padrao !== '' ? $this->frete_valor_padrao : null,
            'melhor_envio_ativo'         => false,
            'melhor_envio_client_id'     => $this->melhor_envio_client_id ?: null,
            'melhor_envio_client_secret' => $this->melhor_envio_client_secret ?: null,
            'melhor_envio_token'         => $this->melhor_envio_token ?: null,
            'melhor_envio_sandbox'       => $this->melhor_envio_sandbox,

            'pagamento_modo'             => 'manual',
            'comissao_percentual'        => $this->comissao_percentual,
            'mercado_pago_ativo'         => false,
            'mercado_pago_public_key'    => $this->mercado_pago_public_key ?: null,
            'mercado_pago_access_token'  => $this->mercado_pago_access_token ?: null,
            'mercado_pago_sandbox'       => $this->mercado_pago_sandbox,
        ]);

        $this->saved = true;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.settings.checkout-settings-form')
            ->layout('admin.layouts.app', ['title' => 'Frete & Pagamento']);
    }
}
