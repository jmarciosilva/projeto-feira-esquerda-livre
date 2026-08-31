<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Lojista\Ava\CursoBuilder;
use App\Livewire\Lojista\Ava\CursoIndex;
use App\Models\Ava\AvaCourse;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * CAT-DOM-02G · G-10 — quem administra o curso canônico.
 *
 * ## A contradição que esta suíte fecha
 *
 * A 02G decidiu que **o curso pertence ao `Product`** (D-02G-5):
 * `ava_courses.product_id` é `UNIQUE`, e o conteúdo educacional é canônico — as
 * aulas do item são as mesmas independentemente de quem o vende.
 *
 * Mas a autorização continuava perguntando *"tenho alguma oferta neste
 * produto?"*. As duas coisas não podem ser verdade ao mesmo tempo: se o curso é
 * canônico, possuir uma oferta não é autoridade sobre ele. Com dois vendedores,
 * o que apenas acrescentou uma oferta editaria as aulas, publicaria e
 * despublicaria o curso do outro.
 *
 * ## A regra correta já existia
 *
 * Editar *o que o item ensina* é editar *o que o item é* — território de
 * `ProductPolicy::updateCanonical`, que a 02C escreveu e a 02F preservou:
 * **curadoria (`produtos.moderar`) ou delegação canônica declarada e viva**.
 *
 * Nenhuma role nova, nenhuma Policy nova, nenhuma superfície nova. E nenhuma
 * regressão hoje: quem cadastra um item recebe a delegação no mesmo ato
 * (`SaveProductWithOffer`), então o autor do produto digital continua entrando
 * no builder. Quem apenas acrescentou uma oferta, não.
 */
class AvaAutoridadeDoCursoTest extends TestCase
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
     * Produto digital criado por A — que por isso recebe a delegação canônica —
     * e uma oferta de B acrescentada depois.
     *
     * @return array{userA: User, userB: User, curso: AvaCourse, produto: Product}
     */
    private function cenario(): array
    {
        $userA = $this->lojista();
        $userB = $this->lojista();

        $expositorA = Expositor::factory()->create(['user_id' => $userA->id]);
        $expositorB = Expositor::factory()->create(['user_id' => $userB->id]);

        $produto = Product::factory()->create([
            'expositor_id' => $expositorA->id,
            'is_digital' => true,
        ]);
        $produto->delegarCanonicoPara($expositorA->id);

        ProductOffer::factory()->create([
            'product_id' => $produto->id,
            'expositor_id' => $expositorB->id,
        ]);

        $curso = AvaCourse::create(['product_id' => $produto->id]);

        return ['userA' => $userA, 'userB' => $userB, 'curso' => $curso, 'produto' => $produto->fresh()];
    }

    // ---------------------------------------------------- o bug estrutural

    /**
     * O caso que a reconciliação fecha: B tem oferta e **nenhuma** autoridade
     * canônica. Antes ele abria o builder inteiro.
     */
    public function test_ter_oferta_nao_da_acesso_ao_builder_do_curso_canonico(): void
    {
        ['userB' => $userB, 'curso' => $curso, 'produto' => $produto] = $this->cenario();

        // B de fato vende o item — é isso que tornava o guard antigo permissivo.
        $this->assertTrue(
            $produto->offers()->where('expositor_id', $userB->expositor->id)->exists()
        );
        $this->assertFalse($userB->can('updateCanonical', $produto));

        Livewire::actingAs($userB)
            ->test(CursoBuilder::class, ['course' => $curso])
            ->assertForbidden();
    }

    public function test_ter_oferta_nao_publica_o_curso_pela_api(): void
    {
        ['userB' => $userB, 'curso' => $curso] = $this->cenario();

        Sanctum::actingAs($userB);

        $this->patchJson(route('api.v1.lojista.cursos.publicar', $curso->id))
            ->assertNotFound();

        $this->assertFalse($curso->fresh()->isPublished());
    }

    // ------------------------------------------------- a autoridade correta

    /** Quem cadastrou o item tem a delegação, e continua administrando. */
    public function test_o_delegado_canonico_administra_o_curso(): void
    {
        ['userA' => $userA, 'curso' => $curso, 'produto' => $produto] = $this->cenario();

        $this->assertTrue($userA->can('updateCanonical', $produto));

        Livewire::actingAs($userA)
            ->test(CursoBuilder::class, ['course' => $curso])
            ->assertOk()
            ->call('togglePublish');

        $this->assertTrue($curso->fresh()->isPublished());
    }

    public function test_o_delegado_publica_pela_api(): void
    {
        ['userA' => $userA, 'curso' => $curso] = $this->cenario();

        Sanctum::actingAs($userA);

        $this->patchJson(route('api.v1.lojista.cursos.publicar', $curso->id))
            ->assertOk()
            ->assertJsonPath('is_published', true);
    }

    /**
     * Delegação revogada: o acesso ao curso vai junto. É o mesmo ciclo de vida
     * da autoridade canônica, e não um estado paralelo.
     */
    public function test_delegacao_revogada_encerra_o_acesso_ao_curso(): void
    {
        ['userA' => $userA, 'curso' => $curso, 'produto' => $produto] = $this->cenario();

        // Pelo caminho do domínio: as colunas de governança ficam fora do
        // `$fillable` de propósito, e revogar preserva quem detinha a delegação
        // e desde quando — a evidência não é apagada.
        $produto->revogarDelegacaoCanonica();

        $this->assertFalse($userA->fresh()->can('updateCanonical', $produto->fresh()));

        Livewire::actingAs($userA)
            ->test(CursoBuilder::class, ['course' => $curso])
            ->assertForbidden();
    }

    /** Curadoria administra conteúdo canônico — inclusive sem ter loja. */
    public function test_curadoria_administra_o_curso(): void
    {
        ['curso' => $curso, 'produto' => $produto] = $this->cenario();

        $curador = User::factory()->create(['role' => UserRole::Editor, 'is_active' => true]);
        $curador->assignRole('supervisor');

        $this->assertTrue($curador->can('updateCanonical', $produto));
        $this->assertNull($curador->expositor);

        Livewire::actingAs($curador)
            ->test(CursoBuilder::class, ['course' => $curso])
            ->assertOk();
    }

    /**
     * A separação que a fase inteira sustenta: autoridade sobre o curso é
     * canônica; ownership da oferta é comercial. Um não vira o outro.
     */
    public function test_autoridade_sobre_o_curso_nao_e_ownership_da_oferta(): void
    {
        ['userA' => $userA, 'userB' => $userB, 'produto' => $produto] = $this->cenario();

        $ofertaB = $produto->offers()->where('expositor_id', $userB->expositor->id)->sole();

        // A administra o curso e NÃO é dono da oferta de B.
        $this->assertTrue($userA->can('updateCanonical', $produto));
        $this->assertFalse($ofertaB->pertenceAoExpositorDe($userA));

        // B é dono da própria oferta e NÃO administra o curso.
        $this->assertTrue($ofertaB->pertenceAoExpositorDe($userB));
        $this->assertFalse($userB->can('updateCanonical', $produto));
    }

    /**
     * A listagem do painel continua sendo dos itens que o lojista vende — ela
     * mostra o que ele oferece, e não o que ele pode editar. O poder está no
     * builder, e é lá que a autoridade é conferida.
     */
    public function test_a_listagem_continua_mostrando_o_que_o_lojista_vende(): void
    {
        ['userB' => $userB, 'produto' => $produto] = $this->cenario();

        Livewire::actingAs($userB)
            ->test(CursoIndex::class)
            ->assertOk()
            ->assertSee($produto->name);
    }
}
