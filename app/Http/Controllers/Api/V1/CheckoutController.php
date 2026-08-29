<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Orders\CancelOrder;
use App\Exceptions\EstoqueInsuficiente;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CheckoutRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Services\CartService;
use App\Services\MercadoPagoService;
use App\Services\OrderService;
use App\Services\Shipping\CartShippingQuoter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
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

        try {
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
        } catch (EstoqueInsuficiente $esgotou) {
            // Mesma autoridade de estoque do checkout web, mesma resposta: o
            // app recebe erro de validacao, nao 500.
            throw ValidationException::withMessages([
                'items' => [$esgotou->mensagemParaOCliente()],
            ]);
        }

        if ($mercadoPago->isEnabled()) {
            try {
                $order = $mercadoPago->createPreference($order);
            } catch (Throwable $falhaDoGateway) {
                report($falhaDoGateway);

                return $this->desfazerPedidoSemPagamento($order, $falhaDoGateway);
            }
        }

        return response()->json([
            'order' => new OrderResource($order->load(['items', 'splits.expositor'])),
        ], 201);
    }

    /**
     * Compensa um pedido que nasceu sem intenção de pagamento (V-10).
     *
     * A criação do pedido é transacional; a chamada ao gateway não pode ser,
     * porque manter locks de estoque abertos durante I/O de rede prolongaria a
     * contenção pelo tempo da internet e envenenaria a concorrência que a
     * FIN-SEC-01E construiu. A saída é compensar depois: transação A cria e
     * reserva, a chamada externa falha, transação B cancela e devolve.
     *
     * O estado é `Cancelado`, e não `Expirado`: nada expirou, porque nenhuma
     * intenção de pagamento chegou a existir. A distinção que a 01F-B
     * estabeleceu vale aqui — expirar é o relógio agindo sobre algo válido.
     *
     * Sem isso, o pedido ficava `AguardandoPagamento` com estoque reservado e
     * `payment_expires_at` nulo: fora do alcance do varredor da 01F-C, e
     * portanto reservado para sempre.
     */
    private function desfazerPedidoSemPagamento(Order $order, Throwable $falhaDoGateway): JsonResponse
    {
        try {
            app(CancelOrder::class)($order);
        } catch (Throwable $falhaDaCompensacao) {
            // Os dois erros são reportados, e a resposta não finge sucesso: o
            // pedido ficou pendente segurando estoque e ninguém conseguiu
            // desfazê-lo. Precisa de intervenção, e o log tem de dizer isso.
            report($falhaDaCompensacao);

            Log::critical('checkout.pedido_sem_pagamento_nao_compensado', [
                'order_reference' => $order->reference,
                'falha_gateway' => $falhaDoGateway->getMessage(),
                'falha_compensacao' => $falhaDaCompensacao->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Não foi possível iniciar o pagamento agora. Seu pedido foi cancelado e nada foi cobrado — tente novamente em alguns instantes.',
        ], 502);
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
