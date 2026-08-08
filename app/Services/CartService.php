<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use JmfSystem\CustomerIntelligence\Facades\CustomerIntelligence;

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

    public function add(Product $product, int $qty = 1): void
    {
        $existing = $this->baseQuery()->where('product_id', $product->id)->first();

        if ($existing) {
            $existing->increment('quantity', $qty);
        } else {
            CartItem::create([
                'session_id'     => $this->sessionId(),
                'user_id'        => Auth::id(),
                'product_id'     => $product->id,
                'expositor_id'   => $product->expositor_id,
                'quantity'        => $qty,
                'price_snapshot' => $product->price ?? 0,
            ]);
        }

        $this->trackEvent('produto.adicionado_carrinho', [
            'produto_id' => $product->id,
            'quantidade' => $qty,
            'preco_unitario' => (float) ($product->price ?? 0),
        ]);
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

        $this->trackEvent('produto.removido_carrinho', [
            'produto_id' => $item->product_id,
            'quantidade' => $item->quantity,
        ]);
    }

    /**
     * Envia evento de rastreamento sem deixar falhas do SDK afetarem o
     * fluxo de compra — a fila de rastreamento pode estar em modo síncrono
     * (JMF_CI_QUEUE_CONNECTION=sync), o que propagaria exceções de rede
     * direto para quem chamou este método.
     *
     * @param  array<string, mixed>  $properties
     */
    private function trackEvent(string $eventName, array $properties): void
    {
        try {
            CustomerIntelligence::track($eventName, $properties);
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
            ->with(['product', 'expositor'])
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
                $userItem = CartItem::where('user_id', $userId)
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
                    'user_id'    => $userId,
                ]);
            }

            CartItem::where('user_id', $userId)->update([
                'session_id' => $this->sessionId(),
            ]);
        });
    }
}
