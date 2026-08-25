<?php

namespace App\CustomerIntelligence;

use App\CustomerIntelligence\Http\Middleware\TrackVisitorSession;
use App\CustomerIntelligence\Services\CustomerIntelligenceService;
use App\CustomerIntelligence\Support\VisitorContext;
use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\ServiceProvider;

/**
 * Provider do modulo interno de Customer Intelligence.
 *
 * Nasce na fase CI-03, quando passou a existir algo real para registrar: o
 * binding por requisicao do VisitorContext e o middleware de coleta. Ate a
 * CI-02 o container resolvia tudo por autowiring e um provider seria camada
 * vazia.
 */
class CustomerIntelligenceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/customer-intelligence-internal.php',
            'customer-intelligence-internal'
        );

        // scoped() e nao singleton(): sob Octane um singleton vazaria o
        // visitante de uma requisicao para a proxima.
        $this->app->scoped(VisitorContext::class);

        // Uma instancia por requisicao, compartilhando o mesmo VisitorContext
        // que o middleware preenche. E o que a fachada resolve.
        $this->app->scoped(CustomerIntelligenceService::class);
    }

    public function boot(): void
    {
        if (! config('customer-intelligence-internal.enabled', true)) {
            return;
        }

        $this->registerWebMiddleware();
    }

    /**
     * O middleware precisa ser anexado no Kernel, e nao no Router: o Kernel
     * mantem sua propria copia de $middlewareGroups e a resincroniza para o
     * Router a cada requisicao, entao um push feito direto no Router seria
     * silenciosamente sobrescrito.
     *
     * Registrar aqui — e nao em bootstrap/app.php — garante que este middleware
     * entre DEPOIS do middleware do SDK externo: providers da aplicacao
     * inicializam depois dos descobertos por pacote, enquanto a configuracao de
     * bootstrap/app.php e aplicada antes de qualquer boot. A ordem importa
     * porque o middleware adota o cookie que o SDK ja tenha enfileirado.
     */
    private function registerWebMiddleware(): void
    {
        /** @var Kernel $kernel */
        $kernel = $this->app->make(KernelContract::class);

        $kernel->appendMiddlewareToGroup('web', TrackVisitorSession::class);
    }
}
