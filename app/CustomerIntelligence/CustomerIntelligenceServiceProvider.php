<?php

namespace App\CustomerIntelligence;

use App\CustomerIntelligence\Console\ForgetUserCommand;
use App\CustomerIntelligence\Console\PruneAuditLogsCommand;
use App\CustomerIntelligence\Console\PruneEventsCommand;
use App\CustomerIntelligence\Console\RebuildDailyMetricsCommand;
use App\CustomerIntelligence\Http\Middleware\TrackVisitorSession;
use App\CustomerIntelligence\Services\CustomerIntelligenceService;
use App\CustomerIntelligence\Support\ConsentContext;
use App\CustomerIntelligence\Support\TrackingPolicy;
use App\CustomerIntelligence\Support\VisitorContext;
use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\ServiceProvider;

/**
 * Provider do modulo interno de Customer Intelligence.
 *
 * Registra a configuracao, o binding por requisicao do VisitorContext, o
 * middleware de coleta e os comandos do modulo.
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
        // visitante — e a escolha de privacidade — de uma requisicao para a
        // proxima.
        $this->app->scoped(VisitorContext::class);
        $this->app->scoped(ConsentContext::class);
        $this->app->scoped(TrackingPolicy::class);

        // Uma instancia por requisicao, compartilhando o mesmo VisitorContext
        // que o middleware preenche. E o que a fachada resolve.
        $this->app->scoped(CustomerIntelligenceService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                RebuildDailyMetricsCommand::class,
                PruneEventsCommand::class,
                PruneAuditLogsCommand::class,
                ForgetUserCommand::class,
            ]);
        }

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
     */
    private function registerWebMiddleware(): void
    {
        /** @var Kernel $kernel */
        $kernel = $this->app->make(KernelContract::class);

        $kernel->appendMiddlewareToGroup('web', TrackVisitorSession::class);
    }
}
