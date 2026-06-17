<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
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
        'melhor_envio_sandbox',
        'pagamento_modo',
        'comissao_percentual',
        'mercado_pago_ativo',
        'mercado_pago_public_key',
        'mercado_pago_access_token',
        'mercado_pago_sandbox',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_mode'           => 'boolean',
            'mail_port'                  => 'integer',
            'mail_password'              => 'encrypted',
            'frete_valor_padrao'         => 'decimal:2',
            'melhor_envio_ativo'         => 'boolean',
            'melhor_envio_client_secret' => 'encrypted',
            'melhor_envio_token'         => 'encrypted',
            'melhor_envio_sandbox'       => 'boolean',
            'comissao_percentual'        => 'decimal:2',
            'mercado_pago_ativo'         => 'boolean',
            'mercado_pago_access_token'  => 'encrypted',
            'mercado_pago_sandbox'       => 'boolean',
        ];
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
