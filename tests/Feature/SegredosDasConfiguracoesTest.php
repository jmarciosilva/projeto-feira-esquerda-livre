<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\Settings\CheckoutSettingsForm;
use App\Livewire\Admin\Settings\MailSettingsForm;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

/**
 * CAT-10A.1 — credencial gravada nunca volta ao navegador.
 *
 * A auditoria da CAT-10A.1 mostrou que as telas de e-mail e de frete e pagamento
 * carregavam as credenciais decriptadas em propriedades públicas do Livewire — e toda
 * propriedade pública viaja no snapshot, também para quem só pode ver as configurações
 * (gerente, editor). Agora cada tela recebe só se a credencial está configurada; o campo
 * guarda apenas o valor novo, esvaziado antes de qualquer resposta. Em branco, a gravada
 * é mantida; remover é ação explícita.
 */
class SegredosDasConfiguracoesTest extends TestCase
{
    use RefreshDatabase;

    private const SENHA_SMTP = 'smtp-SENHA-gravada-111';

    private const ME_CLIENT_SECRET = 'me-CLIENT-SECRET-gravado-222';

    private const ME_TOKEN = 'me-ACCESS-TOKEN-gravado-333';

    private const ME_REFRESH_TOKEN = 'me-REFRESH-TOKEN-gravado-444';

    private const FRENET_TOKEN = 'frenet-TOKEN-gravado-555';

    private const MP_ACCESS_TOKEN = 'APP_USR-mp-ACCESS-TOKEN-gravado-666';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        Http::preventStrayRequests();
    }

    // ─── Cenário ──────────────────────────────────────────────────────────────

    private function comPapel(UserRole $papel): User
    {
        $usuario = User::factory()->create(['role' => $papel]);
        $usuario->assignRole($papel->spatieRole());

        return $usuario;
    }

    /** @return array<string, string> */
    private static function credenciaisGravadas(): array
    {
        return [
            'mail_password' => self::SENHA_SMTP,
            'melhor_envio_client_secret' => self::ME_CLIENT_SECRET,
            'melhor_envio_token' => self::ME_TOKEN,
            'melhor_envio_refresh_token' => self::ME_REFRESH_TOKEN,
            'frenet_token' => self::FRENET_TOKEN,
            'mercado_pago_access_token' => self::MP_ACCESS_TOKEN,
        ];
    }

    /** Todas as credenciais gravadas, com as integrações que dependem delas ligadas. */
    private function gravarCredenciais(): void
    {
        SiteSetting::instance()->forceFill([
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.exemplo.com',
            'mail_port' => 587,
            'mail_username' => 'feira@exemplo.com',
            'mail_encryption' => 'tls',
            'melhor_envio_ativo' => true,
            'melhor_envio_client_id' => 'client-id-publico',
            'melhor_envio_token_expires_at' => now()->addDays(20),
            'frenet_ativo' => true,
            'frete_provedor' => 'frenet',
            'comissao_percentual' => 10,
            'mercado_pago_ativo' => true,
            'pagamento_modo' => 'mercado_pago',
            'mercado_pago_public_key' => 'APP_USR-public-key',
            ...self::credenciaisGravadas(),
        ])->save();
    }

    private function gravada(string $campo): mixed
    {
        return SiteSetting::query()->find(1)->{$campo};
    }

    /** @param  array<int, string>  $segredos */
    private function assertForaDoNavegador(Testable $componente, array $segredos): void
    {
        $html = $componente->html();
        $snapshot = (string) json_encode($componente->snapshot);

        foreach ($segredos as $segredo) {
            $this->assertStringNotContainsString($segredo, $html, "\"{$segredo}\" apareceu no HTML do componente");
            $this->assertStringNotContainsString($segredo, $snapshot, "\"{$segredo}\" apareceu no snapshot do Livewire");
        }
    }

    // ─── SiteSetting ──────────────────────────────────────────────────────────

    public function test_toda_coluna_criptografada_e_credencial_e_fica_fora_da_serializacao(): void
    {
        $settings = new SiteSetting;
        $criptografadas = array_keys(array_filter($settings->getCasts(), fn (string $cast) => $cast === 'encrypted'));

        $this->assertEqualsCanonicalizing($criptografadas, SiteSetting::SEGREDOS, 'coluna criptografada fora de SiteSetting::SEGREDOS');
        $this->assertEqualsCanonicalizing(SiteSetting::SEGREDOS, $settings->getHidden());

        $this->gravarCredenciais();
        $gravado = SiteSetting::query()->find(1);

        foreach (SiteSetting::SEGREDOS as $campo) {
            $this->assertArrayNotHasKey($campo, $gravado->toArray());
        }

        foreach (self::credenciaisGravadas() as $valor) {
            $this->assertStringNotContainsString($valor, $gravado->toJson());
        }
    }

    public function test_credencial_em_branco_nao_e_gravada_nem_conta_como_configurada(): void
    {
        SiteSetting::instance()->forceFill(['frenet_token' => '   ', 'mail_password' => ''])->save();

        $this->assertNull(DB::table('site_settings')->where('id', 1)->value('frenet_token'));
        $this->assertNull(DB::table('site_settings')->where('id', 1)->value('mail_password'));

        DB::table('site_settings')->where('id', 1)->update(['frenet_token' => '']);
        $gravado = SiteSetting::query()->find(1);

        $this->assertFalse($gravado->segredoConfigurado('frenet_token'));
        $this->assertFalse($gravado->segredoConfigurado('mail_password'));

        $this->expectException(InvalidArgumentException::class);

        $gravado->segredoConfigurado('site_name');
    }

    /** `APP_KEY` trocada: a credencial antiga não se lê mais, e mesmo assim precisa poder ser substituída. */
    public function test_credencial_que_nao_se_decripta_mais_pode_ser_substituida(): void
    {
        $this->gravarCredenciais();
        DB::table('site_settings')->where('id', 1)->update(['frenet_token' => 'nao-e-um-payload-criptografado']);

        Livewire::actingAs($this->comPapel(UserRole::Admin))
            ->test(CheckoutSettingsForm::class)
            ->assertSet('frenet_token_configurado', true)
            ->set('frenet_token', 'frenet-TOKEN-novo-999')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('frenet-TOKEN-novo-999', $this->gravada('frenet_token'));
    }

    // ─── Nada gravado vai ao navegador ────────────────────────────────────────

    public function test_nenhuma_credencial_gravada_aparece_nas_paginas_de_configuracao(): void
    {
        $this->gravarCredenciais();

        foreach ([UserRole::Admin, UserRole::Gerente, UserRole::Editor] as $papel) {
            $usuario = $this->comPapel($papel);

            foreach (['admin.settings.mail', 'admin.settings.checkout'] as $rota) {
                $resposta = $this->actingAs($usuario)->get(route($rota))->assertOk();

                foreach (self::credenciaisGravadas() as $valor) {
                    $resposta->assertDontSee($valor, false);
                }
            }
        }
    }

    public function test_a_senha_smtp_gravada_nao_vai_ao_componente(): void
    {
        $this->gravarCredenciais();

        $componente = Livewire::actingAs($this->comPapel(UserRole::Gerente))
            ->test(MailSettingsForm::class)
            ->assertSet('mail_password', '')
            ->assertSet('mail_password_configurada', true);

        $this->assertForaDoNavegador($componente, array_values(self::credenciaisGravadas()));
    }

    public function test_as_credenciais_de_frete_e_pagamento_gravadas_nao_vao_ao_componente(): void
    {
        $this->gravarCredenciais();

        $componente = Livewire::actingAs($this->comPapel(UserRole::Gerente))
            ->test(CheckoutSettingsForm::class)
            ->assertSet('melhor_envio_client_secret', '')
            ->assertSet('melhor_envio_token', '')
            ->assertSet('frenet_token', '')
            ->assertSet('mercado_pago_access_token', '')
            ->assertSet('melhor_envio_client_secret_configurado', true)
            ->assertSet('melhor_envio_connected', true)
            ->assertSet('frenet_token_configurado', true)
            ->assertSet('mercado_pago_access_token_configurado', true);

        $this->assertForaDoNavegador($componente, array_values(self::credenciaisGravadas()));
    }

    public function test_sem_credenciais_gravadas_nada_aparece_como_configurado(): void
    {
        $admin = $this->comPapel(UserRole::Admin);

        Livewire::actingAs($admin)
            ->test(CheckoutSettingsForm::class)
            ->assertSet('melhor_envio_client_secret_configurado', false)
            ->assertSet('melhor_envio_connected', false)
            ->assertSet('frenet_token_configurado', false)
            ->assertSet('mercado_pago_access_token_configurado', false);

        Livewire::actingAs($admin)
            ->test(MailSettingsForm::class)
            ->assertSet('mail_password_configurada', false);
    }

    // ─── E-mail ───────────────────────────────────────────────────────────────

    public function test_substituir_a_senha_smtp(): void
    {
        $this->gravarCredenciais();

        $componente = Livewire::actingAs($this->comPapel(UserRole::Admin))
            ->test(MailSettingsForm::class)
            ->set('mail_password', 'smtp-SENHA-nova-999')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('mail_password', '')
            ->assertSet('mail_password_configurada', true);

        $this->assertSame('smtp-SENHA-nova-999', $this->gravada('mail_password'));
        $this->assertForaDoNavegador($componente, ['smtp-SENHA-nova-999', self::SENHA_SMTP]);
    }

    public function test_salvar_o_e_mail_sem_digitar_a_senha_mantem_a_gravada(): void
    {
        $this->gravarCredenciais();

        Livewire::actingAs($this->comPapel(UserRole::Admin))
            ->test(MailSettingsForm::class)
            ->set('mail_host', 'smtp.outro.com')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(self::SENHA_SMTP, $this->gravada('mail_password'));
        $this->assertSame('smtp.outro.com', $this->gravada('mail_host'));
    }

    public function test_senha_smtp_nova_nao_volta_quando_a_validacao_falha(): void
    {
        $this->gravarCredenciais();

        $componente = Livewire::actingAs($this->comPapel(UserRole::Admin))
            ->test(MailSettingsForm::class)
            ->set('mail_port', 'porta')
            ->set('mail_password', 'smtp-SENHA-nova-999')
            ->call('save')
            ->assertHasErrors(['mail_port'])
            ->assertSet('mail_password', '');

        $this->assertForaDoNavegador($componente, ['smtp-SENHA-nova-999']);
        $this->assertSame(self::SENHA_SMTP, $this->gravada('mail_password'));
    }

    public function test_remover_a_senha_smtp(): void
    {
        $this->gravarCredenciais();

        Livewire::actingAs($this->comPapel(UserRole::Admin))
            ->test(MailSettingsForm::class)
            ->call('removerSenha')
            ->assertHasNoErrors()
            ->assertSet('mail_password_configurada', false);

        $this->assertFalse(SiteSetting::query()->find(1)->segredoConfigurado('mail_password'));
        $this->assertSame('smtp.exemplo.com', $this->gravada('mail_host'));
    }

    /** A mensagem da exceção do transporte pode trazer a conversa SMTP — não vai ao navegador. */
    public function test_falha_no_e_mail_de_teste_nao_expoe_a_mensagem_da_excecao(): void
    {
        $this->gravarCredenciais();

        $mensagem = '535 5.7.8 Authentication failed on smtp.exemplo.com for feira@exemplo.com with password ' . self::SENHA_SMTP;
        $fixa = 'Não foi possível enviar o e-mail de teste. Verifique as configurações e tente novamente.';

        Mail::shouldReceive('raw')->once()->andThrow(new TransportException($mensagem));

        $componente = Livewire::actingAs($this->comPapel(UserRole::Admin))
            ->test(MailSettingsForm::class)
            ->set('test_email', 'destino@exemplo.com')
            ->call('sendTest')
            ->assertHasNoErrors()
            ->assertSet('test_ok', false);

        $this->assertForaDoNavegador($componente, [$mensagem, '535 5.7.8', self::SENHA_SMTP]);

        $componente
            ->assertSet('test_result', $fixa)
            ->assertSee($fixa);
    }

    // ─── Frete e pagamento ────────────────────────────────────────────────────

    public function test_substituir_as_credenciais_de_frete_e_pagamento(): void
    {
        $this->gravarCredenciais();

        $novas = [
            'melhor_envio_client_secret' => 'me-CLIENT-SECRET-novo-777',
            'melhor_envio_token' => 'me-ACCESS-TOKEN-novo-888',
            'frenet_token' => 'frenet-TOKEN-novo-999',
            'mercado_pago_access_token' => 'APP_USR-mp-ACCESS-TOKEN-novo-000',
        ];

        $componente = Livewire::actingAs($this->comPapel(UserRole::Admin))->test(CheckoutSettingsForm::class);

        foreach ($novas as $campo => $valor) {
            $componente->set($campo, $valor);
        }

        $componente->call('save')->assertHasNoErrors();

        foreach ($novas as $campo => $valor) {
            $this->assertSame($valor, $this->gravada($campo), "{$campo} não foi substituída");
            $componente->assertSet($campo, '');
        }

        $this->assertSame(self::ME_REFRESH_TOKEN, $this->gravada('melhor_envio_refresh_token'), 'o refresh token do OAuth não é tocado');
        $this->assertForaDoNavegador($componente, [...array_values($novas), ...array_values(self::credenciaisGravadas())]);
    }

    public function test_salvar_frete_e_pagamento_sem_digitar_credenciais_mantem_as_gravadas(): void
    {
        $this->gravarCredenciais();

        Livewire::actingAs($this->comPapel(UserRole::Admin))
            ->test(CheckoutSettingsForm::class)
            ->set('comissao_percentual', '12')
            ->call('save')
            ->assertHasNoErrors();

        foreach (self::credenciaisGravadas() as $campo => $valor) {
            $this->assertSame($valor, $this->gravada($campo), "{$campo} não devia mudar");
        }

        $this->assertSame('12.00', (string) $this->gravada('comissao_percentual'));
    }

    public function test_credenciais_novas_nao_voltam_quando_a_validacao_falha(): void
    {
        $this->gravarCredenciais();

        $componente = Livewire::actingAs($this->comPapel(UserRole::Admin))
            ->test(CheckoutSettingsForm::class)
            ->set('comissao_percentual', '150')
            ->set('frenet_token', 'frenet-TOKEN-novo-999')
            ->set('mercado_pago_access_token', 'APP_USR-mp-ACCESS-TOKEN-novo-000')
            ->call('save')
            ->assertHasErrors(['comissao_percentual'])
            ->assertSet('frenet_token', '')
            ->assertSet('mercado_pago_access_token', '');

        $this->assertForaDoNavegador($componente, ['frenet-TOKEN-novo-999', 'APP_USR-mp-ACCESS-TOKEN-novo-000']);
        $this->assertSame(self::FRENET_TOKEN, $this->gravada('frenet_token'));
        $this->assertSame(self::MP_ACCESS_TOKEN, $this->gravada('mercado_pago_access_token'));
    }

    public function test_credencial_nova_nao_volta_quando_a_regra_de_ativacao_recusa(): void
    {
        SiteSetting::instance()->forceFill(['frete_provedor' => 'frenet'])->save();

        $componente = Livewire::actingAs($this->comPapel(UserRole::Admin))
            ->test(CheckoutSettingsForm::class)
            ->set('frenet_ativo', true)
            ->set('mercado_pago_access_token', 'APP_USR-mp-ACCESS-TOKEN-novo-000')
            ->call('save')
            ->assertHasErrors(['frenet_token'])
            ->assertSet('mercado_pago_access_token', '');

        $this->assertForaDoNavegador($componente, ['APP_USR-mp-ACCESS-TOKEN-novo-000']);
        $this->assertFalse(SiteSetting::query()->find(1)->segredoConfigurado('mercado_pago_access_token'));
    }

    public function test_ativar_com_credencial_ja_gravada_nao_exige_digitar_de_novo(): void
    {
        $this->gravarCredenciais();
        SiteSetting::query()->find(1)->forceFill(['mercado_pago_ativo' => false, 'pagamento_modo' => 'manual'])->save();

        Livewire::actingAs($this->comPapel(UserRole::Admin))
            ->test(CheckoutSettingsForm::class)
            ->set('mercado_pago_ativo', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($this->gravada('mercado_pago_ativo'));
        $this->assertSame('mercado_pago', $this->gravada('pagamento_modo'));
        $this->assertSame(self::MP_ACCESS_TOKEN, $this->gravada('mercado_pago_access_token'));
    }

    public function test_ativar_sem_credencial_gravada_nem_digitada_e_recusado(): void
    {
        SiteSetting::instance()->forceFill([
            'frenet_ativo' => true,
            'frenet_token' => self::FRENET_TOKEN,
            'frete_provedor' => 'frenet',
        ])->save();

        Livewire::actingAs($this->comPapel(UserRole::Admin))
            ->test(CheckoutSettingsForm::class)
            ->set('mercado_pago_ativo', true)
            ->call('save')
            ->assertHasErrors(['mercado_pago_access_token']);

        $this->assertFalse((bool) $this->gravada('mercado_pago_ativo'));
    }

    /** @return array<string, array{0: string, 1: string, 2: string}> */
    public static function remocoes(): array
    {
        return [
            'client secret do Melhor Envio' => ['removerMelhorEnvioClientSecret', 'melhor_envio_client_secret', 'melhor_envio_ativo'],
            'token da Frenet' => ['removerFrenetToken', 'frenet_token', 'frenet_ativo'],
            'access token do Mercado Pago' => ['removerMercadoPagoAccessToken', 'mercado_pago_access_token', 'mercado_pago_ativo'],
        ];
    }

    #[DataProvider('remocoes')]
    public function test_remover_credencial_apaga_e_desativa_a_integracao(string $acao, string $campo, string $ativo): void
    {
        $this->gravarCredenciais();

        $componente = Livewire::actingAs($this->comPapel(UserRole::Admin))
            ->test(CheckoutSettingsForm::class)
            ->call($acao)
            ->assertHasNoErrors()
            ->assertSet($ativo, false);

        $gravado = SiteSetting::query()->find(1);

        $this->assertFalse($gravado->segredoConfigurado($campo));
        $this->assertFalse($gravado->{$ativo});

        foreach (self::credenciaisGravadas() as $outro => $valor) {
            if ($outro !== $campo) {
                $this->assertSame($valor, $gravado->{$outro}, "{$outro} não devia mudar");
            }
        }

        $this->assertForaDoNavegador($componente, array_values(self::credenciaisGravadas()));
    }

    public function test_remover_o_access_token_do_mercado_pago_volta_o_pagamento_ao_modo_manual(): void
    {
        $this->gravarCredenciais();

        Livewire::actingAs($this->comPapel(UserRole::Admin))
            ->test(CheckoutSettingsForm::class)
            ->call('removerMercadoPagoAccessToken')
            ->assertHasNoErrors();

        $this->assertSame('manual', $this->gravada('pagamento_modo'));
    }

    public function test_desconectar_o_melhor_envio_continua_apagando_os_tokens(): void
    {
        $this->gravarCredenciais();

        Livewire::actingAs($this->comPapel(UserRole::Admin))
            ->test(CheckoutSettingsForm::class)
            ->call('disconnectMelhorEnvio')
            ->assertHasNoErrors()
            ->assertSet('melhor_envio_connected', false)
            ->assertSet('melhor_envio_ativo', false);

        $gravado = SiteSetting::query()->find(1);

        $this->assertFalse($gravado->segredoConfigurado('melhor_envio_token'));
        $this->assertFalse($gravado->segredoConfigurado('melhor_envio_refresh_token'));
        $this->assertNull($gravado->melhor_envio_token_expires_at);
        $this->assertFalse($gravado->melhor_envio_ativo);
        $this->assertSame(self::ME_CLIENT_SECRET, $gravado->melhor_envio_client_secret);
    }

    // ─── Quem só visualiza não altera ─────────────────────────────────────────

    /** @return array<string, array{0: class-string, 1: string}> */
    public static function acoesQueAlteramCredenciais(): array
    {
        return [
            'e-mail: salvar' => [MailSettingsForm::class, 'save'],
            'e-mail: remover a senha' => [MailSettingsForm::class, 'removerSenha'],
            'frete e pagamento: salvar' => [CheckoutSettingsForm::class, 'save'],
            'frete e pagamento: remover o client secret' => [CheckoutSettingsForm::class, 'removerMelhorEnvioClientSecret'],
            'frete e pagamento: remover o token da Frenet' => [CheckoutSettingsForm::class, 'removerFrenetToken'],
            'frete e pagamento: remover o access token do Mercado Pago' => [CheckoutSettingsForm::class, 'removerMercadoPagoAccessToken'],
            'frete e pagamento: desconectar o Melhor Envio' => [CheckoutSettingsForm::class, 'disconnectMelhorEnvio'],
        ];
    }

    #[DataProvider('acoesQueAlteramCredenciais')]
    public function test_quem_so_visualiza_configuracoes_nao_altera_credenciais(string $componente, string $acao): void
    {
        $this->gravarCredenciais();
        $antes = (array) DB::table('site_settings')->where('id', 1)->first();

        $teste = Livewire::actingAs($this->comPapel(UserRole::Gerente))->test($componente);
        $teste->set($componente === MailSettingsForm::class ? 'mail_password' : 'frenet_token', 'tentativa-de-TROCA');

        $teste->call($acao)->assertForbidden();

        $this->assertEquals($antes, (array) DB::table('site_settings')->where('id', 1)->first());
    }
}
