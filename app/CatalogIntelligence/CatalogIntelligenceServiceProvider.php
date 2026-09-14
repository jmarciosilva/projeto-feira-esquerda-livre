<?php

namespace App\CatalogIntelligence;

use App\CatalogIntelligence\Console\AssociateProductsCommand;
use App\CatalogIntelligence\Contracts\CatalogAiProvider;
use App\Services\CatalogAi\CatalogAiProviderSelector;
use Illuminate\Support\ServiceProvider;

/**
 * Provider do módulo de Catalog Intelligence.
 *
 * A CAT-03 registrou a decisão de **não** criar este provider: naquela fase não
 * havia nada para registrar, e provider vazio é estética. A CAT-04 trouxe a
 * primeira responsabilidade real — o comando de associação em lote, que vive
 * fora de `app/Console/Commands` e por isso não é descoberto sozinho.
 *
 * A CAT-06C trouxe a segunda: `config/catalog-intelligence.php`, o limiar de
 * fallback. Antes dela este docblock dizia *"continua sem config a mesclar"* —
 * deixou de ser verdade, e a linha foi reescrita em vez de mantida por inércia.
 *
 * A CAT-06G trouxe a terceira, e o único binding do módulo: o contrato
 * `CatalogAiProvider`, que resolvia para `NullCatalogAiProvider` (D-CAT-06G-9). Sem
 * credencial, operar sem IA externa é o estado normal (D-CAT-06B-5), e o
 * assistente precisa de um provider a quem perguntar `isAvailable()`.
 *
 * A CAT-10A trocou esta linha, como ela previa: o contrato passa a resolver pelo
 * `CatalogAiProviderSelector`, fora do módulo, que devolve o `Null` quando o recurso
 * está desligado ou mal configurado e o adaptador real quando está pronto. O nome
 * do fornecedor mora lá, e não aqui. Continua sendo um binding só, resolvido a cada
 * pedido — sem `singleton`, para que valha o config do momento —, e o `Fake` nunca
 * é registrado aqui.
 *
 * Continua sem middleware. As Actions, o normalizador, a `SuggestionPolicy`, o
 * guard, os redatores e o validador são resolvidos por injeção de construtor, sem
 * binding: nenhum deles tem dependência que o container não saiba montar.
 */
class CatalogIntelligenceServiceProvider extends ServiceProvider
{
    /**
     * Mescla o config do módulo.
     *
     * `mergeConfigFrom` e não `publishes`: o arquivo é da aplicação, versionado,
     * e não um stub a copiar — mesmo padrão de
     * `CustomerIntelligenceServiceProvider`.
     *
     * Vale registrar que o merge é **redundante para resolução**: o Laravel já
     * carrega todo arquivo em `config/`, e `config('catalog-intelligence.…')`
     * resolveria sem ele (é o caso de `orders.php` e `frenet.php`, que nenhum
     * provider mescla). Fica por simetria com o módulo irmão e para tornar a
     * dependência explícita no registro. Quem garante o limiar se a chave
     * sumir é o `LIMIAR_PADRAO` da própria política, não este merge.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/catalog-intelligence.php',
            'catalog-intelligence'
        );

        $this->app->bind(CatalogAiProvider::class, fn ($app) => $app->make(CatalogAiProviderSelector::class)->resolve());
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AssociateProductsCommand::class,
            ]);
        }
    }
}
