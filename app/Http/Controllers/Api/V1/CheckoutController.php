<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CheckoutRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\CustomerAddress;
use App\Services\CartService;
use App\Services\MercadoPagoService;
use App\Services\OrderService;
use App\Services\Shipping\CartShippingQuoter;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutController extends Controller
{
    /** POST /api/v1/checkout */
    public function store(
        CheckoutRequest $request,
        OrderService $orderService,
        CartService $cart,
        MercadoPagoService $mercadoPago,
        CartShippingQuoter $quoter
    ): JsonResponse {
        $user = $request->user();

        if (! $user->isMarketplaceActive()) {
            throw ValidationException::withMessages([
                'customer_name' => ['Sua conta de cliente está inativa no marketplace.'],
            ]);
        }

        $items = $cart->items();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'customer_name' => ['Seu carrinho está vazio.'],
            ]);
        }

        $data = $request->validated();
        $allDigital = $items->every(fn ($item) => $item->product?->is_digital);
        $address = null;

        if ($data['delivery_type'] === 'entrega' && ! $allDigital) {
            $address = $user->addresses()->find($data['customer_address_id'] ?? null);

            if (! $address) {
                throw ValidationException::withMessages([
                    'customer_address_id' => ['Selecione ou cadastre um endereço de entrega.'],
                ]);
            }
        }

        $frete = $this->resolverFrete($request, $cart, $quoter, $address, $allDigital);

        $order = $orderService->createFromCart([
            'customer_name' => $data['customer_name'],
            'customer_whatsapp' => $data['customer_whatsapp'],
            'customer_email' => $data['customer_email'] ?? null,
            'delivery_type' => $data['delivery_type'],
            'customer_address_id' => $address?->id,
            'address_cep' => $address?->cep,
            'address_rua' => $address?->rua,
            'address_numero' => $address?->numero,
            'address_complemento' => $address?->complemento,
            'address_bairro' => $address?->bairro,
            'address_cidade' => $address?->cidade,
            'address_estado' => $address?->estado,
            'shipping_total' => $frete['total'],
            'shipping_por_expositor' => $frete['por_expositor'],
            'shipping_note' => $data['shipping_note'] ?? null,
        ], $cart);

        if ($mercadoPago->isEnabled()) {
            try {
                $order = $mercadoPago->createPreference($order);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return response()->json([
            'order' => new OrderResource($order->load(['items', 'splits.expositor'])),
        ], 201);
    }

    /**
     * Quanto de frete este pedido cobra — decidido pelo servidor.
     *
     * O cliente informa **qual** serviço escolheu para cada loja; o preço vem
     * de uma recotação feita aqui, com o endereço que ele selecionou e os itens
     * que estão de fato no carrinho. `shipping_total` continua sendo aceito no
     * payload por compatibilidade, mas não decide mais nada: quando enviado e
     * divergente do valor cotado, o pedido é recusado, para que o cliente nunca
     * seja cobrado num valor diferente do que viu na tela do app.
     *
     * Era este o buraco do F-13. Validar o formato do número — `numeric`,
     * `min:0` — nunca respondeu à pergunta que importa, que não é *"o número é
     * válido?"* e sim *"quem decidiu esse número?"*.
     *
     * @return array{total: float, por_expositor: array<int|string, float>}
     */
    private function resolverFrete(
        CheckoutRequest $request,
        CartService $cart,
        CartShippingQuoter $quoter,
        ?CustomerAddress $address,
        bool $allDigital,
    ): array {
        $data = $request->validated();

        // Retirada, ou pedido só de item digital: não há transporte a cobrar,
        // e nenhuma escolha do cliente pode inventar um.
        if ($data['delivery_type'] !== 'entrega' || $allDigital || ! $address) {
            return ['total' => 0.0, 'por_expositor' => []];
        }

        $agrupado = $cart->grouped();
        $cotacoes = $quoter->porLoja($agrupado, (string) $address->cep);

        // Sem loja que precise de frete, o pedido é de entrega só no nome.
        if ($cotacoes === []) {
            return ['total' => 0.0, 'por_expositor' => []];
        }

        $escolhas = collect($data['shipping_options'] ?? [])
            ->keyBy(fn (array $opcao) => $opcao['expositor_id']);

        $porExpositor = [];

        foreach (array_keys($cotacoes) as $expositorId) {
            $preco = $quoter->precoDaEscolha(
                $cotacoes,
                $expositorId,
                $escolhas[$expositorId]['service_id'] ?? null,
            );

            if ($preco === null) {
                throw ValidationException::withMessages([
                    'shipping_options' => ["Escolha uma opção de entrega válida para a loja {$expositorId}."],
                ]);
            }

            $porExpositor[$expositorId] = $preco;
        }

        $total = round(array_sum($porExpositor), 2);

        // O campo depreciado só serve para flagrar app desatualizado.
        if (isset($data['shipping_total']) && abs(round((float) $data['shipping_total'], 2) - $total) > 0.001) {
            throw ValidationException::withMessages([
                'shipping_total' => ['O frete informado não corresponde ao valor cotado. Refaça a cotação e tente novamente.'],
            ]);
        }

        return ['total' => $total, 'por_expositor' => $porExpositor];
    }
}
