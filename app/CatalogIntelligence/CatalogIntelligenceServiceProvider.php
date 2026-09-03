<?php

namespace App\CatalogIntelligence;

use App\CatalogIntelligence\Console\AssociateProductsCommand;
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
 * Continua sem middleware e sem binding: as Actions, o normalizador e a
 * `SuggestionPolicy` são resolvidos pelo container por injeção de construtor —
 * a política não tem dependência para amarrar, porque lê o limiar do config no
 * ponto de uso e não conhece provider algum.
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
