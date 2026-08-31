<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductQuestion;
use Illuminate\View\View;
use Livewire\Component;

/**
 * As perguntas do público na página comercial de uma oferta.
 *
 * ## O que a CAT-DOM-02E mudou
 *
 * A pergunta passou a registrar **em que oferta foi feita**. Não é detalhe de
 * rastreabilidade: quem responde é o lojista, e uma pergunta sem contexto é uma
 * pergunta sem destinatário — com multi-oferta, iria para quem o cliente nunca
 * escolheu.
 *
 * A oferta chega da página, que já a resolveu pela URL `loja/{loja}/{produto}`.
 * Nunca é deduzida aqui: nada de `first()`, nada de `products.expositor_id`,
 * nada de delegação canônica. Se o contexto não vier, a pergunta não é criada.
 *
 * A leitura continua **por produto**, e de propósito: o cliente que abre a
 * página quer ver o que já foi perguntado sobre o item, e a resposta de outro
 * lojista sobre o mesmo item continua sendo informação útil. Quem responde é
 * assunto da 02F.
 */
class ProductQandA extends Component
{
    public Product $product;

    public ?ProductOffer $offer = null;

    public string $question = '';

    public bool $submitted = false;

    public function submit(): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'));
            return;
        }

        $this->validate([
            'question' => 'required|string|min:5|max:500',
        ]);

        // Sem contexto comercial não se registra pergunta: melhor recusar do
        // que atribuir a uma oferta escolhida por conveniência.
        abort_if($this->offer === null, 422, 'Contexto da oferta indisponível.');

        ProductQuestion::create([
            'product_id'       => $this->product->id,
            'product_offer_id' => $this->offer->id,
            'user_id'          => auth()->id(),
            'question'         => trim($this->question),
        ]);

        $this->question  = '';
        $this->submitted = true;
    }

    public function render(): View
    {
        $answered = ProductQuestion::where('product_id', $this->product->id)
            ->whereNotNull('answered_at')
            ->where('is_visible', true)
            ->with('user')
            ->orderByDesc('answered_at')
            ->get();

        $myPending = auth()->check()
            ? ProductQuestion::where('product_id', $this->product->id)
                ->where('user_id', auth()->id())
                ->whereNull('answered_at')
                ->orderByDesc('created_at')
                ->get()
            : collect();

        return view('livewire.product-q-and-a', compact('answered', 'myPending'));
    }
}
