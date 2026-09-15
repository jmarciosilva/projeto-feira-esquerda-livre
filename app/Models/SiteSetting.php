<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class SiteSetting extends Model
{
    /**
     * As credenciais guardadas com o cast `encrypted` — CAT-10A.1.
     *
     * Nenhuma delas vai ao navegador depois de gravada: as telas do painel recebem só
     * se cada uma está configurada (`segredoConfigurado()`), e `$hidden` as tira de
     * qualquer serialização do model. `$hidden` é defesa em profundidade, e não a
     * proteção das telas.
     *
     * Toda coluna nova com cast `encrypted` entra aqui — há teste que confere.
     */
    public const SEGREDOS = [
        'mail_password',
        'melhor_envio_client_secret',
        'melhor_envio_token',
        'melhor_envio_refresh_token',
        'frenet_token',
        'mercado_pago_access_token',
        'catalog_ai_api_key',
    ];

    protected $fillable = [
        'site_name',
        'site_description',
        'logo_path',
        'favicon_path',
        'whatsapp',
        'email',
        'instagram_url',
        'facebook_url',
        'youtube_url',
        'address',
        'footer_text',
        'maintenance_mode',
        'sobre_titulo',
        'sobre_texto',
        'sobre_imagem_path',
        'color_primary',
        'color_primary_dark',
        'color_secondary',
        'color_secondary_light',
        'color_dark',
        'contrato_expositor',
        'mail_mailer',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
        'frete_modo',
        'frete_mensagem_manual',
        'frete_valor_padrao',
        'melhor_envio_ativo',
        'melhor_envio_client_id',
        'melhor_envio_client_secret',
        'melhor_envio_token',
        'melhor_envio_refresh_token',
        'melhor_envio_token_expires_at',
        'melhor_envio_sandbox',
        'frenet_ativo',
        'frenet_token',
        'frete_provedor',
        'pagamento_modo',
        'comissao_percentual',
        'mercado_pago_ativo',
        'mercado_pago_public_key',
        'mercado_pago_access_token',
        'mercado_pago_sandbox',
        'catalog_ai_ativo',
        'catalog_ai_provider',
        'catalog_ai_modelo',
        'catalog_ai_api_key',
        'catalog_ai_timeout',
    ];

    protected $hidden = self::SEGREDOS;

    protected function casts(): array
    {
        return [
            'maintenance_mode'           => 'boolean',
            'mail_port'                  => 'integer',
            'mail_password'              => 'encrypted',
            'frete_valor_padrao'         => 'decimal:2',
            'melhor_envio_ativo'         => 'boolean',
            'melhor_envio_client_secret'     => 'encrypted',
            'melhor_envio_token'             => 'encrypted',
            'melhor_envio_refresh_token'     => 'encrypted',
            'melhor_envio_token_expires_at'  => 'datetime',
            'melhor_envio_sandbox'       => 'boolean',
            'frenet_ativo'               => 'boolean',
            'frenet_token'               => 'encrypted',
            'comissao_percentual'        => 'decimal:2',
            'mercado_pago_ativo'         => 'boolean',
            'mercado_pago_access_token'  => 'encrypted',
            'mercado_pago_sandbox'       => 'boolean',
            'catalog_ai_ativo'           => 'boolean',
            'catalog_ai_api_key'         => 'encrypted',
            'catalog_ai_timeout'         => 'integer',
        ];
    }

    /**
     * Credencial em branco nunca é gravada cifrada: vira nula antes do cast.
     *
     * É o que permite decidir "configurada" pelo valor guardado, sem decriptar.
     */
    public function setAttribute($key, $value)
    {
        if (is_string($value) && trim($value) === '' && in_array($key, self::SEGREDOS, true)) {
            $value = null;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Credencial muda quando o valor guardado muda — sem decriptar o anterior.
     *
     * Para o cast `encrypted`, o Eloquent decriptaria os dois lados para saber se o
     * atributo mudou, e a credencial anterior pode não se decriptar mais (`APP_KEY`
     * trocada) — justamente quando é preciso substituí-la. Como cada cifragem gera um
     * texto diferente, o mesmo valor regravado também conta como alterado; o custo é
     * uma escrita a mais.
     */
    public function originalIsEquivalent($key)
    {
        if (in_array($key, self::SEGREDOS, true)) {
            return array_key_exists($key, $this->original)
                && ($this->attributes[$key] ?? null) === $this->original[$key];
        }

        return parent::originalIsEquivalent($key);
    }

    /**
     * Se a credencial está gravada — sem decriptá-la.
     *
     * Nula ou vazia conta como não configurada. A resposta vem do valor guardado, e não
     * do decriptado, para que a tela diga "configurada" e permita substituir mesmo
     * quando o valor não se decripta mais (`APP_KEY` trocada).
     */
    public function segredoConfigurado(string $atributo): bool
    {
        if (! in_array($atributo, self::SEGREDOS, true)) {
            throw new InvalidArgumentException("[{$atributo}] não é uma credencial de SiteSetting.");
        }

        $guardado = $this->getAttributes()[$atributo] ?? null;

        return is_string($guardado) && $guardado !== '';
    }

    /** Retorna o registro singleton de configurações */
    public static function instance(): static
    {
        return static::firstOrCreate(['id' => 1], [
            'site_name'            => 'Feira Esquerda Livre',
            'maintenance_mode'     => false,
            'color_primary'        => '#E8A000',
            'color_primary_dark'   => '#C47A00',
            'color_secondary'      => '#F4E294',
            'color_secondary_light'=> '#FDF8DC',
            'color_dark'           => '#3D3000',
        ]);
    }
}
