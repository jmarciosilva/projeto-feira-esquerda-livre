<?php

namespace Tests\Feature;

use App\Models\Expositor;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShippingQuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotes_shipping_with_melhor_envio(): void
    {
        config([
            'melhorenvio.base_url' => 'https://sandbox.melhorenvio.com.br',
            'melhorenvio.token' => 'TEST_TOKEN',
        ]);

        Http::fake([
            'sandbox.melhorenvio.com.br/api/v2/me/shipment/calculate' => Http::response([
                [
                    'id' => 1,
                    'name' => 'PAC',
                    'price' => '24.90',
                    'custom_price' => '23.50',
                    'delivery_time' => 7,
                    'custom_delivery_time' => 6,
                    'company' => ['name' => 'Correios'],
                ],
            ]),
        ]);

        [$store, $product] = $this->makeStoreAndProduct();

        $response = $this->postJson(route('shipping.quote'), [
            'store_id' => $store->id,
            'destination_zipcode' => '01001000',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('quotes.0.service_id', '1')
            ->assertJsonPath('quotes.0.company', 'Correios')
            ->assertJsonPath('quotes.0.service_name', 'PAC')
            ->assertJsonPath('quotes.0.price', 23.5)
            ->assertJsonPath('quotes.0.delivery_time', 6)
            ->assertJsonPath('quotes.0.currency', 'BRL');

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $request->hasHeader('Authorization', 'Bearer TEST_TOKEN')
                && $payload['from']['postal_code'] === '01310000'
                && $payload['to']['postal_code'] === '01001000'
                && $payload['products'][0]['weight'] === 0.3
                && $payload['products'][0]['quantity'] === 2;
        });
    }

    public function test_returns_clear_error_when_product_has_no_logistic_data(): void
    {
        config([
            'melhorenvio.base_url' => 'https://sandbox.melhorenvio.com.br',
            'melhorenvio.token' => 'TEST_TOKEN',
        ]);

        Http::fake();

        [$store, $product] = $this->makeStoreAndProduct([
            'weight' => null,
            'height' => null,
        ]);

        $response = $this->postJson(route('shipping.quote'), [
            'store_id' => $store->id,
            'destination_zipcode' => '01001000',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('quotes.0.service_id', null)
            ->assertJsonPath('quotes.0.price', null)
            ->assertJsonPath('quotes.0.currency', 'BRL');

        $this->assertStringContainsString(
            'não possui dados logísticos cadastrados',
            $response->json('quotes.0.error_message'),
        );

        Http::assertNothingSent();
    }

    /**
     * @param  array<string, mixed>  $productAttributes
     * @return array{0: Expositor, 1: Product}
     */
    private function makeStoreAndProduct(array $productAttributes = []): array
    {
        $store = Expositor::create([
            'name' => 'Atelie das Maos',
            'slug' => 'atelie-das-maos',
            'zipcode' => '01310-000',
        ]);

        $product = Product::create(array_merge([
            'expositor_id' => $store->id,
            'item_type' => 'produto',
            'name' => 'Bolsa Tecida',
            'slug' => 'bolsa-tecida',
            'price' => 89.90,
            'weight' => 0.300,
            'height' => 4,
            'width' => 16,
            'length' => 24,
            'is_active' => true,
        ], $productAttributes));

        return [$store, $product];
    }
}
