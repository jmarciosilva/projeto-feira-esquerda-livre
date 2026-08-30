<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Quem pode mexer na verdade canônica de um item de catálogo.
 *
 * É o **único** lugar do sistema que responde essa pergunta. A CAT-DOM-01
 * espalhou a autorização comercial por três camadas — `ProdutoForm`,
 * `ProdutoController` e o escopo de `ProdutoIndex` — e cada uma precisou ser
 * migrada à mão quando o alvo mudou. Aqui a regra nasce em um ponto só,
 * justamente porque ela vai mudar de novo: a fase de contribuição vai
 * acrescentar propostas, e a de curadoria, revisão.
 *
 * ## O que esta Policy deliberadamente não pergunta
 *
 * Nenhum dos três atalhos que a D-CAT-09 proibiu aparece aqui:
 *
 * - **quantas ofertas o produto tem** — cardinalidade é estado comercial, não
 *   governança. Um produto que voltou de duas ofertas para uma continua
 *   compartilhado, e a delegação não ressuscita sozinha;
 * - **`products.expositor_id`** — é proveniência (D-CAT-11), registro
 *   histórico de quem trouxe o item. Não concede, não comprova e não restaura
 *   autoridade nenhuma;
 * - **"o expositor tem uma oferta sobre este produto?"** — é a pergunta certa
 *   para a oferta dele e a errada para o produto. Ela continua valendo, e
 *   continua onde sempre esteve: nos guards da SEC-02.
 *
 * O que resta é a pergunta que importa: **existe delegação declarada e viva
 * para este expositor?**
 */
class ProductPolicy
{
    /**
     * Editar os campos que dizem *o que este item é*.
     *
     * `Gate::before` já concede tudo a admin antes de chegar aqui; os demais
     * papéis internos entram por `produtos.moderar`, a permissão de curadoria
     * que o projeto já declarava — e que até agora não tinha nenhuma superfície
     * que a exercesse.
     */
    public function updateCanonical(User $user, Product $product): bool
    {
        if ($this->isCuradoria($user)) {
            return true;
        }

        return $product->delegaCanonicoPara($user->expositor?->id);
    }

    /**
     * Ligar e desligar a validade canônica do item no catálogo.
     *
     * Exclusivo da curadoria, **inclusive para quem tem delegação válida**
     * (D-CAT-10): a delegação alcança os campos canônicos e não alcança este.
     *
     * O expositor não perde nada com isso. Ele continua com o interruptor que
     * sempre usou — `product_offers.is_active` —, e como `scopeVigente()` exige
     * oferta ativa, desligar a oferta já tira o item de todas as vitrines. O
     * que ele deixa de conseguir é tirar do catálogo um item de que outros
     * podem depender.
     */
    public function updateStatus(User $user, Product $product): bool
    {
        return $this->isCuradoria($user);
    }

    private function isCuradoria(User $user): bool
    {
        return $user->can('produtos.moderar');
    }
}
