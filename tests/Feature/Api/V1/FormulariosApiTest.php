<?php

namespace Tests\Feature\Api\V1;

use App\Mail\ContatoConfirmacaoUsuario;
use App\Mail\ContatoMensagemRecebida;
use App\Mail\LojistaSolicitacaoRecebida;
use App\Models\LojistasSolicitacao;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FormulariosApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_sends_message_to_platform_and_confirmation_to_sender(): void
    {
        Mail::fake();
        SiteSetting::instance()->update(['email' => 'contato@feiraesquerdalivre.com.br']);

        $this->postJson('/api/v1/contato', [
            'name' => 'Maria Interessada',
            'email' => 'maria@example.com',
            'phone' => '(11) 98888-7777',
            'subject' => 'Quero vender na feira',
            'message' => 'Olá, gostaria de mais informações sobre como me tornar expositora.',
        ])->assertOk()->assertJsonStructure(['message']);

        Mail::assertSent(ContatoMensagemRecebida::class, fn ($mail) => $mail->hasTo('contato@feiraesquerdalivre.com.br'));
        Mail::assertSent(ContatoConfirmacaoUsuario::class, fn ($mail) => $mail->hasTo('maria@example.com'));
    }

    public function test_contact_form_validates_required_fields(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/contato', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'subject', 'message']);

        Mail::assertNothingSent();
    }

    public function test_expositor_solicitacao_creates_record_and_sends_confirmation_email(): void
    {
        Mail::fake();

        $payload = [
            'nome_loja' => 'Ateliê Novo',
            'responsavel' => 'João Artesão',
            'cpf_cnpj' => '123.456.789-00',
            'whatsapp' => '(11) 97777-6666',
            'email' => 'joao@example.com',
            'instagram_url' => 'https://instagram.com/atelienovo',
            'pix_tipo' => 'email',
            'pix_chave' => 'joao@example.com',
            'eixos' => ['produto'],
        ];

        $this->postJson('/api/v1/seja-um-expositor', $payload)
            ->assertCreated()
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('lojista_solicitacoes', [
            'nome_loja' => 'Ateliê Novo',
            'email' => 'joao@example.com',
        ]);

        $solicitacao = LojistasSolicitacao::firstWhere('email', 'joao@example.com');
        Mail::assertSent(
            LojistaSolicitacaoRecebida::class,
            fn ($mail) => $mail->hasTo('joao@example.com') && $mail->solicitacao->is($solicitacao),
        );
    }

    public function test_expositor_solicitacao_validates_required_fields(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/seja-um-expositor', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'nome_loja', 'responsavel', 'cpf_cnpj', 'whatsapp', 'email', 'instagram_url', 'pix_tipo', 'pix_chave',
            ]);

        $this->assertDatabaseCount('lojista_solicitacoes', 0);
        Mail::assertNothingSent();
    }
}
