<?php

namespace Tests\Feature;

use App\Enums\AvaEnrollmentStatus;
use App\Models\Ava\AvaCourse;
use App\Models\Ava\AvaEnrollment;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderSplit;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * CAT-DOM-02G · G-10 — de qual oferta veio a matrícula.
 *
 * ## A decisão que estes testes fixam
 *
 * **O curso pertence ao `Product`.** `ava_courses.product_id` é `UNIQUE`, e o
 * conteúdo educacional é canônico: as aulas do item são as mesmas
 * independentemente de quem o vende.
 *
 * **A compra é que é comercial**, e a matrícula já a referencia por
 * `order_split_id` desde a FIN-SEC-01B. O caminho histórico existia inteiro no
 * schema; o que faltava era alguém percorrê-lo em vez de perguntar ao catálogo
 * como ele está hoje.
 *
 * ```text
 * matrícula → order_split → order → order_items → product_offer_id
 * ```
 *
 * O erro que isso evita é concreto: o aluno comprou de B, B recolheu a oferta, e
 * `ofertaVigente` passaria a devolver A — a plataforma reescreveria de quem ele
 * comprou porque o catálogo mudou depois.
 */
class AvaOfertaHistoricaTest extends TestCase
{
    use RefreshDatabase;

    private function expositor(string $nome): Expositor
    {
        return Expositor::factory()->create([
            'user_id' => User::factory()->create()->id,
            'name' => $nome,
        ]);
    }

    /**
     * Produto digital com duas ofertas, comprado da oferta B.
     *
     * @return array{matricula: AvaEnrollment, ofertaA: ProductOffer, ofertaB: ProductOffer, produto: Product}
     */
    private function compraDaOfertaB(): array
    {
        $expositorA = $this->expositor('Loja A');
        $expositorB = $this->expositor('Loja B');

        $produto = Product::factory()->create([
            'expositor_id' => $expositorA->id,
            'is_digital' => true,
        ]);
        $ofertaA = $produto->offers()->sole();

        $ofertaB = ProductOffer::factory()->create([
            'product_id' => $produto->id,
            'expositor_id' => $expositorB->id,
            'price' => 250,
        ]);

        $curso = AvaCourse::create(['product_id' => $produto->id, 'published_at' => now()->subDay()]);

        $aluno = User::factory()->create();

        $order = Order::create([
            'user_id' => $aluno->id,
            'reference' => 'TEST-'.uniqid(),
            'status' => 'pagamento_confirmado',
            'paid_at' => now(),
            'delivery_type' => 'entrega',
            'customer_name' => $aluno->name,
            'customer_whatsapp' => '11999990000',
            'items_total' => 250,
            'shipping_total' => 0,
            'total_amount' => 250,
        ]);

        $split = OrderSplit::create([
            'order_id' => $order->id,
            'expositor_id' => $expositorB->id,
            'expositor_name' => $expositorB->name,
            'gross_amount' => 250,
            'commission_percent' => 10,
            'commission_amount' => 25,
            'net_amount' => 225,
            'shipping_amount' => 0,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $produto->id,
            'product_offer_id' => $ofertaB->id,
            'expositor_id' => $expositorB->id,
            'expositor_name' => $expositorB->name,
            'product_name' => $produto->name,
            'unit_price' => 250,
            'quantity' => 1,
            'total_price' => 250,
        ]);

        $matricula = AvaEnrollment::create([
            'user_id' => $aluno->id,
            'course_id' => $curso->id,
            'order_split_id' => $split->id,
            'status' => AvaEnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        return compact('matricula', 'ofertaA', 'ofertaB', 'produto');
    }

    public function test_a_matricula_sabe_de_qual_oferta_veio(): void
    {
        ['matricula' => $matricula, 'ofertaB' => $ofertaB] = $this->compraDaOfertaB();

        $this->assertSame($ofertaB->id, $matricula->ofertaDeOrigem()?->id);
        $this->assertSame($ofertaB->expositor_id, $matricula->expositorDeOrigemId());
    }

    /**
     * O teste que fecha o G-10: a oferta comprada é recolhida, e a que resta
     * ativa **não** herda a matrícula.
     */
    public function test_oferta_comprada_inativa_nao_migra_para_a_que_sobrou(): void
    {
        ['matricula' => $matricula, 'ofertaA' => $ofertaA, 'ofertaB' => $ofertaB, 'produto' => $produto] = $this->compraDaOfertaB();

        $ofertaB->update(['is_active' => false]);

        // `ofertaVigente` agora devolveria A — e é exatamente por isso que o
        // histórico não pode passar por ela.
        $this->assertSame($ofertaA->id, $produto->fresh()->ofertaVigente?->id);

        $this->assertSame($ofertaB->id, $matricula->ofertaDeOrigem()?->id);
        $this->assertNotSame($ofertaA->id, $matricula->ofertaDeOrigem()?->id);
    }

    /** E sobrevive ao vendedor sair da Feira. */
    public function test_a_origem_sobrevive_a_loja_ser_desativada(): void
    {
        ['matricula' => $matricula, 'ofertaB' => $ofertaB] = $this->compraDaOfertaB();

        $ofertaB->expositor->update(['is_active' => false]);

        $this->assertSame($ofertaB->id, $matricula->fresh()->ofertaDeOrigem()?->id);
    }

    /**
     * Matrícula de cortesia não tem compra por trás: a resposta é "não houve
     * oferta de origem", e não "descubra qual foi".
     */
    public function test_matricula_sem_compra_nao_inventa_oferta(): void
    {
        $expositor = $this->expositor('Loja A');
        $produto = Product::factory()->create(['expositor_id' => $expositor->id, 'is_digital' => true]);
        $curso = AvaCourse::create(['product_id' => $produto->id]);

        $matricula = AvaEnrollment::create([
            'user_id' => User::factory()->create()->id,
            'course_id' => $curso->id,
            'status' => AvaEnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        $this->assertNull($matricula->ofertaDeOrigem());
        $this->assertNull($matricula->expositorDeOrigemId());
    }

    /**
     * Um pedido pode reunir itens de várias lojas; o split é de uma só. A
     * resolução casa produto **e** expositor, para não trazer o item da loja
     * errada do mesmo pedido.
     */
    public function test_pedido_com_duas_lojas_resolve_o_item_do_split_certo(): void
    {
        ['matricula' => $matricula, 'ofertaA' => $ofertaA, 'ofertaB' => $ofertaB, 'produto' => $produto] = $this->compraDaOfertaB();

        $split = $matricula->orderSplit;

        // O mesmo pedido também trouxe o item da loja A, sobre o mesmo produto.
        OrderItem::create([
            'order_id' => $split->order_id,
            'product_id' => $produto->id,
            'product_offer_id' => $ofertaA->id,
            'expositor_id' => $ofertaA->expositor_id,
            'expositor_name' => $ofertaA->expositor->name,
            'product_name' => $produto->name,
            'unit_price' => 300,
            'quantity' => 1,
            'total_price' => 300,
        ]);

        $this->assertSame($ofertaB->id, $matricula->fresh()->ofertaDeOrigem()?->id);
    }

    /** O curso é do produto, e não da oferta: conteúdo canônico. */
    public function test_o_curso_pertence_ao_produto(): void
    {
        ['matricula' => $matricula, 'produto' => $produto] = $this->compraDaOfertaB();

        $this->assertSame($produto->id, $matricula->course->product_id);
        $this->assertFalse(Schema::hasColumn('ava_courses', 'product_offer_id'));
        $this->assertFalse(Schema::hasColumn('ava_courses', 'expositor_id'));
    }
}
