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
 * Continua sem config a mesclar, sem middleware e sem binding: as Actions e o
 * normalizador são resolvidos pelo container por injeção de construtor, e
 * amarrá-los aqui só acrescentaria indireção.
 */
class CatalogIntelligenceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AssociateProductsCommand::class,
            ]);
        }
    }
}
