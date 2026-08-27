<?php

namespace App\Services;

use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Facades\CustomerIntelligence;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductOffer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CartService
{
    private function sessionId(): string
    {
        return Session::getId();
    }

    private function baseQuery()
    {
        if (Auth::check()) {
            return CartItem::where('user_id', Auth::id());
        }

        return CartItem::where('session_id', $this->sessionId());
    }

    /**
     * Adiciona a oferta de um expositor ao carrinho.
     *
     * A linha do carrinho é identificada pela **oferta**, não pelo produto: se
     * dois expositores oferecem o mesmo item de catálogo, são duas compras
     * diferentes, de duas lojas diferentes, e somá-las na mesma linha faria o
     * cliente pagar um preço e receber de quem não vendeu.
     *
     * `price_snapshot` continua sendo o que protege o carrinho de uma mudança
     * de preço no meio da compra.
     */
    public function add(ProductOffer $offer, int $qty = 1): void
    {
        $existing = $this->baseQuery()->where('product_offer_id', $offer->id)->first();

        if ($existing) {
            $existing->increment('quantity', $qty);
        } else {
            CartItem::create([
                'session_id' => $this->sessionId(),
                'user_id' => Auth::id(),
                'product_id' => $offer->product_id,
                'product_offer_id' => $offer->id,
                'expositor_id' => $offer->expositor_id,
                'quantity' => $qty,
                'price_snapshot' => $offer->price ?? 0,
            ]);
        }

        $this->trackEvent(EventName::ProdutoAdicionadoCarrinho, [
            'produto_id' => $offer->product_id,
            'quantidade' => $qty,
            'preco_unitario' => (float) ($offer->price ?? 0),
        ], $offer->product);
    }

    public function update(int $cartItemId, int $qty): void
    {
        $item = $this->baseQuery()->findOrFail($cartItemId);
        if ($qty < 1) {
            $item->delete();

            return;
        }
        $item->update(['quantity' => $qty]);
    }

    public function remove(int $cartItemId): void
    {
        $item = $this->baseQuery()->where('id', $cartItemId)->first();

        if (! $item) {
            return;
        }

        $item->delete();

        $this->trackEvent(EventName::ProdutoRemovidoCarrinho, [
            'produto_id' => $item->product_id,
            'quantidade' => $item->quantity,
        ]);
    }

    /**
     * Envia evento de rastreamento sem deixar uma falha de analytics afetar
     * o fluxo de compra. O módulo interno apenas enfileira o evento, então o
     * risco é pequeno — mas rastreamento nunca deve derrubar um carrinho.
     *
     * @param  array<string, mixed>  $properties
     */
    private function trackEvent(EventName $event, array $properties, ?Product $product = null): void
    {
        try {
            CustomerIntelligence::track($event, $properties, $product);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function clear(): void
    {
        $this->baseQuery()->delete();
    }

    /** Retorna itens agrupados por expositor_id. */
    public function items(): Collection
    {
        return $this->baseQuery()
            ->with(['product', 'offer', 'expositor'])
            ->get();
    }

    public function count(): int
    {
        return (int) $this->baseQuery()->sum('quantity');
    }

    public function total(): float
    {
        return (float) $this->baseQuery()
            ->selectRaw('SUM(price_snapshot * quantity) as total')
            ->value('total');
    }

    /** Agrupa itens por expositor para exibição no carrinho. */
    public function grouped(): Collection
    {
        return $this->items()->groupBy('expositor_id');
    }

    /**
     * Mescla o carrinho guest no carrinho do usuário após login ou cadastro.
     */
    public function reassignSession(string $oldSessionId, int $userId): void
    {
        DB::transaction(function () use ($oldSessionId, $userId) {
            $guestItems = CartItem::where('session_id', $oldSessionId)
                ->whereNull('user_id')
                ->lockForUpdate()
                ->get();

            foreach ($guestItems as $guestItem) {
                // Casa pela oferta: mesmo item de catálogo comprado de duas
                // lojas são duas linhas, e mesclá-las trocaria o vendedor.
                //
                // `whereNull` explícito porque `where(coluna, null)` vira
                // `= NULL` em SQL e nunca casa — sem isso, um item anterior à
                // CAT-DOM-01, ainda sem oferta gravada, deixaria de ser
                // mesclado no login e viraria linha duplicada.
                $userItem = CartItem::where('user_id', $userId)
                    ->when(
                        $guestItem->product_offer_id === null,
                        fn ($q) => $q->whereNull('product_offer_id'),
                        fn ($q) => $q->where('product_offer_id', $guestItem->product_offer_id),
                    )
                    ->where('product_id', $guestItem->product_id)
                    ->lockForUpdate()
                    ->first();

                if ($userItem) {
                    $userItem->increment('quantity', $guestItem->quantity);
                    $guestItem->delete();

                    continue;
                }

                $guestItem->update([
                    'session_id' => $this->sessionId(),
                    'user_id' => $userId,
                ]);
            }

            CartItem::where('user_id', $userId)->update([
                'session_id' => $this->sessionId(),
            ]);
        });
    }
}
