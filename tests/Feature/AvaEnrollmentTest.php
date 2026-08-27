<?php

namespace Tests\Feature;

use App\Enums\AvaEnrollmentStatus;
use App\Enums\ItemType;
use App\Enums\OrderSplitStatus;
use App\Events\OrderSplitConfirmed;
use App\Mail\AvaEnrollmentConfirmedMail;
use App\Models\Ava\AvaCourse;
use App\Models\Ava\AvaEnrollment;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderSplit;
use App\Models\Product;
use App\Models\User;
use App\Services\AvaEnrollmentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AvaEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    // ─── helpers ──────────────────────────────────────────────────────────────

    private function makeDigitalProductWithPublishedCourse(Expositor $expositor): Product
    {
        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type'    => ItemType::Servico,
            'name'         => 'Curso de Culinária',
            'slug'         => 'curso-culinaria',
            'price'        => 99.90,
            'is_digital'   => true,
            'is_active'    => true,
        ]);

        AvaCourse::create([
            'product_id'   => $product->id,
            'published_at' => now()->subMinute(),
        ]);

        return $product;
    }

    private function makeExpositor(): Expositor
    {
        static $counter = 0;
        $counter++;

        return Expositor::create([
            'name' => 'Loja AVA ' . $counter,
            'slug' => 'loja-ava-' . $counter,
        ]);
    }

    private function makeOrderSplitForProduct(User $buyer, Product $product, Expositor $expositor): OrderSplit
    {
        $order = Order::create([
            'user_id'            => $buyer->id,
            'reference'          => 'TEST-' . uniqid(),
            'status'             => 'aguardando_pagamento',
            'delivery_type'      => 'entrega',
            'customer_name'      => $buyer->name,
            'customer_whatsapp'  => '11999990000',
            'items_total'        => $product->price,
            'shipping_total'     => 0,
            'total_amount'       => $product->price,
        ]);

        OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'expositor_id' => $expositor->id,
            'product_name' => $product->name,
            'unit_price'   => $product->price,
            'quantity'     => 1,
            'total_price'  => $product->price,
        ]);

        return OrderSplit::create([
            'order_id'           => $order->id,
            'expositor_id'       => $expositor->id,
            'gross_amount'       => $product->price,
            'commission_percent' => 10,
            'commission_amount'  => 9.99,
            'net_amount'         => 89.91,
            'status'             => OrderSplitStatus::Pendente,
        ]);
    }

    // ─── tests ────────────────────────────────────────────────────────────────

    public function test_confirmar_split_dispatches_order_split_confirmed_event(): void
    {
        Event::fake([OrderSplitConfirmed::class]);

        $buyer    = User::factory()->create();
        $expositor = $this->makeExpositor();
        $product  = $this->makeDigitalProductWithPublishedCourse($expositor);
        $split    = $this->makeOrderSplitForProduct($buyer, $product, $expositor);

        $split->confirmar();

        Event::assertDispatched(OrderSplitConfirmed::class, fn ($e) => $e->split->id === $split->id);
    }

    public function test_enrollment_created_when_split_confirmed_for_digital_product(): void
    {
        Mail::fake();

        $buyer    = User::factory()->create();
        $expositor = $this->makeExpositor();
        $product  = $this->makeDigitalProductWithPublishedCourse($expositor);
        $split    = $this->makeOrderSplitForProduct($buyer, $product, $expositor);

        $split->confirmar();

        $this->assertDatabaseHas('ava_enrollments', [
            'user_id'   => $buyer->id,
            'course_id' => $product->avaCourse->id,
            'status'    => AvaEnrollmentStatus::Active->value,
        ]);
    }

    public function test_enrollment_email_sent_on_confirmation(): void
    {
        Mail::fake();

        $buyer    = User::factory()->create();
        $expositor = $this->makeExpositor();
        $product  = $this->makeDigitalProductWithPublishedCourse($expositor);
        $split    = $this->makeOrderSplitForProduct($buyer, $product, $expositor);

        $split->confirmar();

        Mail::assertSent(AvaEnrollmentConfirmedMail::class, fn ($mail) => $mail->hasTo($buyer->email));
    }

    public function test_no_enrollment_for_non_digital_product(): void
    {
        Mail::fake();

        $buyer    = User::factory()->create();
        $expositor = $this->makeExpositor();
        $product  = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type'    => ItemType::Produto,
            'name'         => 'Bolsa Artesanal',
            'slug'         => 'bolsa-artesanal',
            'price'        => 150.00,
            'is_digital'   => false,
            'is_active'    => true,
        ]);
        $split = $this->makeOrderSplitForProduct($buyer, $product, $expositor);

        $split->confirmar();

        $this->assertDatabaseCount('ava_enrollments', 0);
    }

    public function test_no_enrollment_for_unpublished_course(): void
    {
        Mail::fake();

        $buyer    = User::factory()->create();
        $expositor = $this->makeExpositor();
        $product  = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type'    => ItemType::Servico,
            'name'         => 'Curso Rascunho',
            'slug'         => 'curso-rascunho',
            'price'        => 50.00,
            'is_digital'   => true,
            'is_active'    => true,
        ]);
        AvaCourse::create(['product_id' => $product->id, 'published_at' => null]);

        $split = $this->makeOrderSplitForProduct($buyer, $product, $expositor);

        $split->confirmar();

        $this->assertDatabaseCount('ava_enrollments', 0);
    }

    public function test_no_duplicate_enrollment_on_double_confirmation(): void
    {
        Mail::fake();

        $buyer    = User::factory()->create();
        $expositor = $this->makeExpositor();
        $product  = $this->makeDigitalProductWithPublishedCourse($expositor);
        $split    = $this->makeOrderSplitForProduct($buyer, $product, $expositor);

        $service = app(AvaEnrollmentService::class);
        $service->createFromOrderSplit($split);
        $service->createFromOrderSplit($split); // run twice

        $this->assertDatabaseCount('ava_enrollments', 1);
    }

    public function test_enrollment_has_expiry_when_course_has_duration(): void
    {
        Mail::fake();

        $buyer    = User::factory()->create();
        $expositor = $this->makeExpositor();
        $product  = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type'    => ItemType::Servico,
            'name'         => 'Curso com Prazo',
            'slug'         => 'curso-com-prazo',
            'price'        => 79.90,
            'is_digital'   => true,
            'is_active'    => true,
        ]);
        AvaCourse::create([
            'product_id'          => $product->id,
            'published_at'        => now()->subMinute(),
            'access_duration_days' => 365,
        ]);

        $split = $this->makeOrderSplitForProduct($buyer, $product, $expositor);
        $split->confirmar();

        $enrollment = AvaEnrollment::where('user_id', $buyer->id)->first();
        $this->assertNotNull($enrollment->expires_at);
        $this->assertTrue($enrollment->expires_at->isFuture());
    }

    public function test_enrollment_perpetual_when_no_duration(): void
    {
        Mail::fake();

        $buyer    = User::factory()->create();
        $expositor = $this->makeExpositor();
        $product  = $this->makeDigitalProductWithPublishedCourse($expositor);
        $split    = $this->makeOrderSplitForProduct($buyer, $product, $expositor);

        $split->confirmar();

        $enrollment = AvaEnrollment::where('user_id', $buyer->id)->first();
        $this->assertNull($enrollment->expires_at);
        $this->assertTrue($enrollment->isAccessible());
    }

    public function test_student_can_see_aprendizado_page(): void
    {
        Mail::fake();

        $buyer    = User::factory()->create();
        $expositor = $this->makeExpositor();
        $product  = $this->makeDigitalProductWithPublishedCourse($expositor);
        $split    = $this->makeOrderSplitForProduct($buyer, $product, $expositor);
        $split->confirmar();

        $response = $this->actingAs($buyer)->get(route('cliente.ava.index'));
        $response->assertOk();
        $response->assertSee('Curso de Culinária');
    }

    public function test_guest_redirected_from_aprendizado_page(): void
    {
        $response = $this->get(route('cliente.ava.index'));
        $response->assertRedirect(route('login'));
    }
}
