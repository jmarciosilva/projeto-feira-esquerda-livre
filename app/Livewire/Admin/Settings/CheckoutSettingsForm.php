<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\AuthorizesAdminActions;
use App\Models\SiteSetting;
use App\Services\SiteSettingService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Frete & Pagamento.
 *
 * ## Credenciais — CAT-10A.1
 *
 * `melhor_envio_client_secret`, `melhor_envio_token`, `frenet_token` e
 * `mercado_pago_access_token` guardam só o valor NOVO, enquanto é digitado. O gravado
 * nunca é carregado: toda propriedade pública vai no snapshot do Livewire, também para
 * quem só pode ver esta tela. A tela recebe só se cada credencial está configurada, e
 * cada ação esvazia esses campos antes de autorizar, validar ou gravar.
 *
 * Em branco, a credencial gravada é mantida. Remover é ação explícita, que também
 * desativa a integração que depende dela; para o Access Token do Melhor Envio, é o
 * "Desconectar" que já existia.
 */
class CheckoutSettingsForm extends Component
{
    use AuthorizesAdminActions;

    /** Os campos desta tela que recebem credencial. */
    private const CREDENCIAIS = [
        'melhor_envio_client_secret',
        'melhor_envio_token',
        'frenet_token',
        'mercado_pago_access_token',
    ];

    // Frete
    public string  $frete_modo             = 'manual';
    public string  $frete_mensagem_manual  = '';
    public string  $frete_valor_padrao     = '';
    public bool    $melhor_envio_ativo     = false;
    public string  $melhor_envio_client_id     = '';
    public string  $melhor_envio_client_secret = '';
    #[Locked]
    public bool    $melhor_envio_client_secret_configurado = false;
    public string  $melhor_envio_token         = '';
    public bool    $melhor_envio_sandbox       = true;
    #[Locked]
    public bool    $melhor_envio_connected     = false;
    public ?string $melhor_envio_expires_at    = null;
    public bool    $frenet_ativo               = false;
    public string  $frenet_token               = '';
    #[Locked]
    public bool    $frenet_token_configurado   = false;
    public string  $frete_provedor             = 'melhor_envio';

    // Pagamento
    public string  $pagamento_modo          = 'manual';
    public string  $comissao_percentual     = '0';
    public bool    $mercado_pago_ativo      = false;
    public string  $mercado_pago_public_key    = '';
    public string  $mercado_pago_access_token  = '';
    #[Locked]
    public bool    $mercado_pago_access_token_configurado = false;
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
        $this->melhor_envio_sandbox       = (bool) ($s->melhor_envio_sandbox ?? true);
        $this->frenet_ativo               = (bool) $s->frenet_ativo;
        $this->frete_provedor             = $s->frete_provedor ?? 'melhor_envio';

        $this->pagamento_modo        = $s->pagamento_modo ?? 'manual';
        $this->comissao_percentual   = (string) ($s->comissao_percentual ?? '0');
        $this->mercado_pago_ativo    = (bool) $s->mercado_pago_ativo;
        $this->mercado_pago_public_key   = $s->mercado_pago_public_key ?? '';
        $this->mercado_pago_sandbox      = (bool) ($s->mercado_pago_sandbox ?? true);

        $this->lerEstadoDasCredenciais($s);
    }

    public function save(SiteSettingService $service): void
    {
        $novas = $this->recolherCredenciaisNovas();

        $this->authorizeAdminAction('configuracoes.editar');

        Validator::make([
            'frete_mensagem_manual'   => $this->frete_mensagem_manual,
            'frete_valor_padrao'      => $this->frete_valor_padrao,
            'melhor_envio_client_id'  => $this->melhor_envio_client_id,
            'frete_provedor'          => $this->frete_provedor,
            'comissao_percentual'     => $this->comissao_percentual,
            'mercado_pago_public_key' => $this->mercado_pago_public_key,
            ...$novas,
        ], [
            'frete_mensagem_manual'      => 'nullable|string|max:1000',
            'frete_valor_padrao'         => 'nullable|numeric|min:0',
            'melhor_envio_client_id'     => 'nullable|string|max:255',
            'melhor_envio_client_secret' => 'nullable|string|max:2000',
            'melhor_envio_token'         => 'nullable|string|max:2000',
            'frenet_token'               => 'nullable|string|max:2000',
            'frete_provedor'             => 'required|in:melhor_envio,frenet',
            'comissao_percentual'        => 'required|numeric|min:0|max:100',
            'mercado_pago_public_key'    => 'nullable|string|max:255',
            'mercado_pago_access_token'  => 'nullable|string|max:2000',
        ])->validate();

        // Uma credencial conta quando foi digitada agora ou já está gravada.
        $gravadas = SiteSetting::instance();
        $tem = fn (string $campo): bool => filled($novas[$campo]) || $gravadas->segredoConfigurado($campo);

        if ($this->melhor_envio_ativo && (blank($this->melhor_envio_client_id) || ! $tem('melhor_envio_client_secret'))) {
            $this->addError('melhor_envio_client_id', 'Informe o Client ID e o Client Secret para ativar o Melhor Envio.');
            return;
        }

        if ($this->melhor_envio_ativo && ! $tem('melhor_envio_token')) {
            $this->addError('melhor_envio_token', 'Conecte com o Melhor Envio ou informe um Access Token para ativar a integração.');
            return;
        }

        if ($this->frenet_ativo && ! $tem('frenet_token')) {
            $this->addError('frenet_token', 'Informe o Token para ativar a Frenet.');
            return;
        }

        if ($this->frete_provedor === 'frenet' && ! $this->frenet_ativo) {
            $this->addError('frete_provedor', 'Ative a Frenet antes de selecioná-la como provedor de frete.');
            return;
        }

        if ($this->frete_provedor === 'melhor_envio' && ! $this->melhor_envio_ativo) {
            $this->addError('frete_provedor', 'Ative o Melhor Envio antes de selecioná-lo como provedor de frete.');
            return;
        }

        if ($this->mercado_pago_ativo && ! $tem('mercado_pago_access_token')) {
            $this->addError('mercado_pago_access_token', 'Informe o Access Token para ativar o Mercado Pago.');
            return;
        }

        $service->save([
            'frete_modo'                 => 'manual',
            'frete_mensagem_manual'      => $this->frete_mensagem_manual ?: null,
            'frete_valor_padrao'         => $this->frete_valor_padrao !== '' ? $this->frete_valor_padrao : null,
            'melhor_envio_ativo'         => $this->melhor_envio_ativo,
            'melhor_envio_client_id'     => $this->melhor_envio_client_id ?: null,
            'melhor_envio_sandbox'       => $this->melhor_envio_sandbox,
            'frenet_ativo'               => $this->frenet_ativo,
            'frete_provedor'             => $this->frete_provedor,

            'pagamento_modo'             => $this->mercado_pago_ativo ? 'mercado_pago' : 'manual',
            'comissao_percentual'        => $this->comissao_percentual,
            'mercado_pago_ativo'         => $this->mercado_pago_ativo,
            'mercado_pago_public_key'    => $this->mercado_pago_public_key ?: null,
            'mercado_pago_sandbox'       => $this->mercado_pago_sandbox,

            // Em branco, a credencial gravada é mantida; só a digitada a substitui.
            ...array_filter($novas, fn (string $valor) => filled($valor)),
        ]);

        $this->lerEstadoDasCredenciais(SiteSetting::instance());
        $this->saved = true;
    }

    public function disconnectMelhorEnvio(SiteSettingService $service): void
    {
        $this->recolherCredenciaisNovas();

        $this->authorizeAdminAction('configuracoes.editar');

        $service->save([
            'melhor_envio_token'            => null,
            'melhor_envio_refresh_token'    => null,
            'melhor_envio_token_expires_at' => null,
            'melhor_envio_ativo'            => false,
        ]);

        $this->melhor_envio_ativo = false;
        $this->lerEstadoDasCredenciais(SiteSetting::instance());
        $this->saved = true;
    }

    public function removerMelhorEnvioClientSecret(SiteSettingService $service): void
    {
        $this->removerCredencial($service, [
            'melhor_envio_client_secret' => null,
            'melhor_envio_ativo'         => false,
        ]);

        $this->melhor_envio_ativo = false;
    }

    public function removerFrenetToken(SiteSettingService $service): void
    {
        $this->removerCredencial($service, [
            'frenet_token' => null,
            'frenet_ativo' => false,
        ]);

        $this->frenet_ativo = false;
    }

    public function removerMercadoPagoAccessToken(SiteSettingService $service): void
    {
        $this->removerCredencial($service, [
            'mercado_pago_access_token' => null,
            'mercado_pago_ativo'        => false,
            'pagamento_modo'            => 'manual',
        ]);

        $this->mercado_pago_ativo = false;
        $this->pagamento_modo     = 'manual';
    }

    /** @param  array<string, mixed>  $dados */
    private function removerCredencial(SiteSettingService $service, array $dados): void
    {
        $this->recolherCredenciaisNovas();

        $this->authorizeAdminAction('configuracoes.editar');

        $service->save($dados);

        $this->lerEstadoDasCredenciais(SiteSetting::instance());
        $this->saved = true;
    }

    /**
     * Copia as credenciais digitadas e esvazia os campos, antes de qualquer outra coisa.
     *
     * @return array<string, string>
     */
    private function recolherCredenciaisNovas(): array
    {
        $novas = [];

        foreach (self::CREDENCIAIS as $campo) {
            $novas[$campo] = $this->{$campo};
            $this->{$campo} = '';
        }

        return $novas;
    }

    private function lerEstadoDasCredenciais(SiteSetting $s): void
    {
        $this->melhor_envio_client_secret_configurado = $s->segredoConfigurado('melhor_envio_client_secret');
        $this->melhor_envio_connected                 = $s->segredoConfigurado('melhor_envio_token');
        $this->melhor_envio_expires_at                = $this->melhor_envio_connected
            ? $s->melhor_envio_token_expires_at?->format('d/m/Y H:i')
            : null;
        $this->frenet_token_configurado               = $s->segredoConfigurado('frenet_token');
        $this->mercado_pago_access_token_configurado  = $s->segredoConfigurado('mercado_pago_access_token');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.settings.checkout-settings-form')
            ->layout('admin.layouts.app', ['title' => 'Frete & Pagamento']);
    }
}
