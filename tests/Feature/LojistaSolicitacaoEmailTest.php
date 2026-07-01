<?php

namespace Tests\Feature;

use App\Enums\SolicitacaoStatus;
use App\Enums\UserRole;
use App\Livewire\Admin\Lojistas\SolicitacaoIndex;
use App\Mail\LojistaAprovado;
use App\Mail\LojistaSolicitacaoRecebida;
use App\Models\LojistasSolicitacao;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class LojistaSolicitacaoEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_confirmation_email_when_store_request_is_submitted(): void
    {
        Mail::fake();

        $response = $this->from(route('seja-um-expositor'))->post(route('seja-um-expositor.post'), [
            'nome_loja' => 'Atelie das Maos',
            'responsavel' => 'Maria Silva',
            'cpf_cnpj' => '123.456.789-09',
            'whatsapp' => '(11) 99999-9999',
            'email' => 'maria@example.com',
            'instagram_url' => 'https://instagram.com/atelie',
            'facebook_url' => 'https://facebook.com/atelie',
            'pix_tipo' => 'email',
            'pix_chave' => 'maria@example.com',
            'descricao' => 'Produtos artesanais',
            'eixos' => ['produto'],
        ]);

        $response->assertRedirect(route('seja-um-expositor'));

        $this->assertDatabaseHas('lojista_solicitacoes', [
            'nome_loja' => 'Atelie das Maos',
            'email' => 'maria@example.com',
            'status' => SolicitacaoStatus::Pendente->value,
        ]);

        Mail::assertSent(LojistaSolicitacaoRecebida::class, function (LojistaSolicitacaoRecebida $mail) {
            return $mail->hasTo('maria@example.com')
                && $mail->solicitacao->nome_loja === 'Atelie das Maos';
        });
    }

    public function test_approval_creates_lojista_user_and_sends_access_credentials(): void
    {
        Mail::fake();
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);
        $admin->assignRole(UserRole::Admin->spatieRole());

        $solicitacao = LojistasSolicitacao::create([
            'nome_loja' => 'Atelie das Maos',
            'responsavel' => 'Maria Silva',
            'cpf_cnpj' => '123.456.789-09',
            'whatsapp' => '(11) 99999-9999',
            'email' => 'maria@example.com',
            'instagram_url' => 'https://instagram.com/atelie',
            'facebook_url' => 'https://facebook.com/atelie',
            'pix_tipo' => 'email',
            'pix_chave' => 'maria@example.com',
            'descricao' => 'Produtos artesanais',
            'eixos' => ['produto'],
        ]);

        Livewire::actingAs($admin)
            ->test(SolicitacaoIndex::class)
            ->set('approveId', $solicitacao->id)
            ->call('approve')
            ->assertHasNoErrors();

        $user = User::where('email', 'maria@example.com')->firstOrFail();

        $this->assertSame(UserRole::Lojista, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('expositores', [
            'user_id' => $user->id,
            'name' => 'Atelie das Maos',
            'email' => 'maria@example.com',
        ]);
        $this->assertSame(SolicitacaoStatus::Aprovado, $solicitacao->fresh()->status);

        Mail::assertSent(LojistaAprovado::class, function (LojistaAprovado $mail) use ($user) {
            return $mail->hasTo('maria@example.com')
                && $mail->user->is($user)
                && $mail->nomeLoja === 'Atelie das Maos'
                && $mail->senha !== '12345678'
                && strlen($mail->senha) === 12;
        });
    }
}
