<?php

namespace App\Http\Controllers;

use App\Models\Expositor;
use App\Models\ProductOffer;
use App\Models\SiteSetting;
use App\Services\Shipping\FrenetService;
use App\Services\Shipping\MelhorEnvioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShippingController extends Controller
{
    public function quote(Request $request, MelhorEnvioService $melhorEnvio, FrenetService $frenet): JsonResponse
    {
        // Provedor de frete escolhido manualmente no admin (padrão: Melhor Envio).
        $shipping = SiteSetting::instance()->frete_provedor === 'frenet' ? $frenet : $melhorEnvio;

        $validated = $request->validate([
            'store_id' => 'required|integer|exists:expositores,id',
            'destination_zipcode' => 'required|string|max:9',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $store = Expositor::findOrFail($validated['store_id']);
        $productIds = collect($validated['items'])->pluck('product_id')->all();

        // O contrato da requisição não mudou — continua chegando `store_id` e
        // `product_id`. O que mudou é o que esse par identifica: a oferta
        // daquela loja sobre aquele item. Buscar as ofertas já filtrando por
        // expositor é, ao mesmo tempo, a resolução e a checagem de propriedade.
        $offers = ProductOffer::whereIn('product_id', $productIds)
            ->where('expositor_id', $store->id)
            ->with('product')
            ->get()
            ->keyBy('product_id');

        foreach ($validated['items'] as $item) {
            if (! $offers->has($item['product_id'])) {
                throw ValidationException::withMessages([
                    'items' => 'Todos os produtos informados devem pertencer à loja escolhida.',
                ]);
            }
        }

        $items = collect($validated['items'])->map(function (array $item) use ($offers) {
            return (object) [
                'offer' => $offers->get($item['product_id']),
                'quantity' => (int) $item['quantity'],
            ];
        });

        $quotes = $shipping->quoteForStore($store, $validated['destination_zipcode'], $items);

        return response()->json([
            'success' => collect($quotes)->contains(fn ($quote) => $quote->error_message === null),
            'store_id' => $store->id,
            'quotes' => collect($quotes)->map->toArray()->values(),
        ]);
    }
}
