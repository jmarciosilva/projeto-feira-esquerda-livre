<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Lojista\Perguntas\PerguntaIndex;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductQuestion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * CAT-DOM-02F — quem pode responder uma pergunta comercial.
 *
 * ## O defeito que esta suíte fecha
 *
 * Até a 02E a autorização perguntava *"tenho alguma oferta neste produto?"*.
 * Com `Product` e `ProductOffer` em 1:1 isso dava sempre a resposta certa, e por
 * isso o defeito era invisível — mas a pergunta errada estava lá. Com dois
 * vendedores no mesmo item, B responderia, assinando com a loja dele, o que o
 * cliente perguntou a A: uma promessa de prazo ou de troca que quem respondeu
 * não tem como cumprir.
 *
 * A pergunta certa é *"esta pergunta é da minha oferta?"* (D-02F-4).
 */
class PerguntaAutoridadeDeRespostaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function lojista(): User
    {
        $user = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);
        $user->assignRole('lojista');

        return $user;
    }

    /**
     * Produto compartilhado, uma pergunta dirigida à oferta de A.
     *
     * @return array{userA: User, userB: User, ofertaA: ProductOffer, ofertaB: ProductOffer, pergunta: ProductQuestion, produto: Product}
     */
    private function cenario(): array
    {
        $userA = $this->lojista();
        $userB = $this->lojista();

        $expositorA = Expositor::factory()->create(['user_id' => $userA->id]);
        $expositorB = Expositor::factory()->create(['user_id' => $userB->id]);

        $produto = Product::factory()->create(['expositor_id' => $expositorA->id]);
        $ofertaA = $produto->offers()->sole();

        $ofertaB = ProductOffer::factory()->create([
            'product_id' => $produto->id,
            'expositor_id' => $expositorB->id,
        ]);

        $pergunta = ProductQuestion::create([
            'product_id' => $produto->id,
            'product_offer_id' => $ofertaA->id,
            'user_id' => User::factory()->create()->id,
            'question' => 'Vocês entregam em Salvador?',
        ]);

        return compact('userA', 'userB', 'ofertaA', 'ofertaB', 'pergunta', 'produto');
    }

    // ------------------------------------------------------------ predicado

    public function test_so_o_dono_da_oferta_da_pergunta_e_o_destinatario(): void
    {
        ['userA' => $userA, 'userB' => $userB, 'pergunta' => $pergunta] = $this->cenario();

        $this->assertTrue($pergunta->podeSerRespondidaPor($userA));
        $this->assertFalse($pergunta->podeSerRespondidaPor($userB));

        // Cliente e visitante nunca são destinatários comerciais.
        $this->assertFalse($pergunta->podeSerRespondidaPor(User::factory()->create()));
        $this->assertFalse($pergunta->podeSerRespondidaPor(null));
    }

    /**
     * Pergunta sem oferta não tem destinatário: a FK é nullable por
     * compatibilidade histórica, e nulo significa "não se sabe a quem foi
     * feita". Ninguém a assume (D-02F-5).
     */
    public function test_pergunta_sem_oferta_nao_tem_destinatario(): void
    {
        ['userA' => $userA, 'userB' => $userB, 'produto' => $produto] = $this->cenario();

        $orfa = ProductQuestion::create([
            'product_id' => $produto->id,
            'user_id' => User::factory()->create()->id,
            'question' => 'Pergunta legada, sem contexto comercial',
        ]);

        $this->assertFalse($orfa->podeSerRespondidaPor($userA));
        $this->assertFalse($orfa->podeSerRespondidaPor($userB));
    }

    // -------------------------------------------------- painel do lojista

    public function test_o_dono_da_oferta_responde(): void
    {
        ['userA' => $userA, 'pergunta' => $pergunta] = $this->cenario();

        Livewire::actingAs($userA)
            ->test(PerguntaIndex::class)
            ->set("answers.{$pergunta->id}", 'Sim, em dois dias.')
            ->call('saveAnswer', $pergunta->id);

        $pergunta->refresh();

        $this->assertSame('Sim, em dois dias.', $pergunta->answer);
        $this->assertNotNull($pergunta->answered_at);

        // `answered_by` continua sendo a PESSOA (users.id). Nenhuma coluna de
        // autoria comercial foi criada — a loja é derivável pela oferta.
        $this->assertSame($userA->id, $pergunta->answered_by);
    }

    /** O caso que a 02F existe para impedir. */
    public function test_o_outro_lojista_do_mesmo_produto_nao_responde(): void
    {
        ['userB' => $userB, 'pergunta' => $pergunta] = $this->cenario();

        $this->expectException(ModelNotFoundException::class);

        try {
            Livewire::actingAs($userB)
                ->test(PerguntaIndex::class)
                ->set("answers.{$pergunta->id}", 'Resposta de quem não foi perguntado')
                ->call('saveAnswer', $pergunta->id);
        } finally {
            $this->assertNull($pergunta->fresh()->answer);
            $this->assertNull($pergunta->fresh()->answered_at);
        }
    }

    public function test_o_outro_lojista_nao_oculta_a_pergunta_alheia(): void
    {
        ['userB' => $userB, 'pergunta' => $pergunta] = $this->cenario();

        // Lido do banco: o default de `is_visible` é do schema, e o modelo
        // recém-criado ainda não o conhece.
        $visivelAntes = $pergunta->fresh()->is_visible;

        $this->expectException(ModelNotFoundException::class);

        try {
            Livewire::actingAs($userB)
                ->test(PerguntaIndex::class)
                ->call('toggleVisibility', $pergunta->id);
        } finally {
            $this->assertSame($visivelAntes, $pergunta->fresh()->is_visible);
        }
    }

    public function test_lojista_nao_responde_pergunta_sem_oferta(): void
    {
        ['userA' => $userA, 'produto' => $produto] = $this->cenario();

        $orfa = ProductQuestion::create([
            'product_id' => $produto->id,
            'user_id' => User::factory()->create()->id,
            'question' => 'Pergunta legada, sem contexto comercial',
        ]);

        $this->expectException(ModelNotFoundException::class);

        try {
            Livewire::actingAs($userA)
                ->test(PerguntaIndex::class)
                ->set("answers.{$orfa->id}", 'Assumindo o que não é meu')
                ->call('saveAnswer', $orfa->id);
        } finally {
            $this->assertNull($orfa->fresh()->answer);
        }
    }

    public function test_a_listagem_do_painel_mostra_so_as_perguntas_da_propria_oferta(): void
    {
        ['userA' => $userA, 'userB' => $userB, 'ofertaB' => $ofertaB, 'produto' => $produto, 'pergunta' => $pergunta] = $this->cenario();

        $deB = ProductQuestion::create([
            'product_id' => $produto->id,
            'product_offer_id' => $ofertaB->id,
            'user_id' => User::factory()->create()->id,
            'question' => 'Pergunta dirigida ao B',
        ]);

        Livewire::actingAs($userA)
            ->test(PerguntaIndex::class)
            ->assertSee($pergunta->question)
            ->assertDontSee($deB->question);

        Livewire::actingAs($userB)
            ->test(PerguntaIndex::class)
            ->assertSee($deB->question)
            ->assertDontSee($pergunta->question);
    }

    // ---------------------------------------------------------------- API

    public function test_api_o_dono_da_oferta_responde(): void
    {
        ['userA' => $userA, 'pergunta' => $pergunta] = $this->cenario();

        Sanctum::actingAs($userA);

        $this->patchJson(
            route('api.v1.lojista.perguntas.responder', $pergunta->id),
            ['answer' => 'Sim, entregamos.'],
        )->assertOk();

        $this->assertSame('Sim, entregamos.', $pergunta->fresh()->answer);
    }

    public function test_api_o_outro_lojista_recebe_404_e_nada_muda(): void
    {
        ['userB' => $userB, 'pergunta' => $pergunta] = $this->cenario();

        Sanctum::actingAs($userB);

        $this->patchJson(
            route('api.v1.lojista.perguntas.responder', $pergunta->id),
            ['answer' => 'Resposta indevida'],
        )->assertNotFound();

        $this->assertNull($pergunta->fresh()->answer);
    }

    public function test_api_pergunta_sem_oferta_nao_e_respondivel(): void
    {
        ['userA' => $userA, 'produto' => $produto] = $this->cenario();

        $orfa = ProductQuestion::create([
            'product_id' => $produto->id,
            'user_id' => User::factory()->create()->id,
            'question' => 'Sem contexto comercial',
        ]);

        Sanctum::actingAs($userA);

        $this->patchJson(
            route('api.v1.lojista.perguntas.responder', $orfa->id),
            ['answer' => 'Assumindo o que não é meu'],
        )->assertNotFound();

        $this->assertNull($orfa->fresh()->answer);
    }

    public function test_api_a_listagem_e_a_contagem_saem_da_propria_oferta(): void
    {
        ['userA' => $userA, 'ofertaB' => $ofertaB, 'produto' => $produto] = $this->cenario();

        ProductQuestion::create([
            'product_id' => $produto->id,
            'product_offer_id' => $ofertaB->id,
            'user_id' => User::factory()->create()->id,
            'question' => 'Pergunta dirigida ao B',
        ]);

        Sanctum::actingAs($userA);

        $this->getJson(route('api.v1.lojista.perguntas.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.pending_count', 1);
    }

    /**
     * Inconsistência defensiva: a oferta da pergunta é de outro produto.
     *
     * Não é estado que o sistema produza — a 02E resolve a oferta pela página
     * ou valida contra o produto da rota —, mas se chegar por dado importado ou
     * manipulação, a autoridade continua saindo da **oferta**, e o `product_id`
     * divergente não vira porta de entrada para ninguém. Nada é corrigido
     * silenciosamente.
     */
    public function test_pergunta_com_produto_e_oferta_divergentes_nao_amplia_ninguem(): void
    {
        ['userA' => $userA, 'userB' => $userB, 'ofertaB' => $ofertaB] = $this->cenario();

        $outroProduto = Product::factory()->create(['expositor_id' => $userA->expositor->id]);

        $inconsistente = ProductQuestion::create([
            'product_id' => $outroProduto->id,
            'product_offer_id' => $ofertaB->id,
            'user_id' => User::factory()->create()->id,
            'question' => 'Pergunta com contexto divergente',
        ]);

        // Manda a oferta, não o produto: B responde, A não — mesmo sendo A o
        // dono da única oferta do `product_id` registrado.
        $this->assertTrue($inconsistente->podeSerRespondidaPor($userB));
        $this->assertFalse($inconsistente->podeSerRespondidaPor($userA));

        // E o dado divergente permanece como está, para ser investigado.
        $this->assertSame($outroProduto->id, $inconsistente->fresh()->product_id);
        $this->assertSame($ofertaB->id, $inconsistente->fresh()->product_offer_id);
    }
}
