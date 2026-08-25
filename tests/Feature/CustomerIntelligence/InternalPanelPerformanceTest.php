<?php

namespace Tests\Feature\CustomerIntelligence;

use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Enums\MetricName;
use App\CustomerIntelligence\Models\DailyMetric;
use App\CustomerIntelligence\Models\Visitor;
use App\CustomerIntelligence\Models\VisitorSession;
use App\Enums\UserRole;
use App\Livewire\Admin\CustomerIntelligence\Dashboard;
use App\Livewire\Admin\CustomerIntelligence\EventIndex;
use App\Livewire\Admin\CustomerIntelligence\VisitorIndex;
use App\Livewire\Admin\CustomerIntelligence\VisitorShow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Comportamento do painel sob volume (CI-06).
 *
 * A massa e sintetica e vive apenas no banco em memoria do teste — nada disso
 * toca o banco de desenvolvimento.
 *
 * O que se mede aqui nao e velocidade absoluta (que depende da maquina), e sim
 * a FORMA das consultas: numero de queries constante independentemente do
 * volume. E assim que N+1 e agregacao repetida aparecem.
 */
class InternalPanelPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private const EVENTOS = 10000;

    private const VISITANTES = 200;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    /**
     * Insere a massa direto no banco: passar por `record()` dispararia 10.000
     * jobs e mediria o caminho de escrita, que nao e o objeto deste teste.
     */
    private function seedVolume(): void
    {
        $agora = Carbon::now();
        $eventos = array_map(fn (EventName $e) => $e->value, EventName::cases());

        $visitantes = [];
        for ($i = 0; $i < self::VISITANTES; $i++) {
            $visitantes[] = [
                'visitor_uuid' => (string) Str::orderedUuid(),
                'first_seen_at' => $agora->copy()->subDays(80),
                'last_seen_at' => $agora->copy()->subDays(random_int(0, 60)),
                'created_at' => $agora,
                'updated_at' => $agora,
            ];
        }
        Visitor::insert($visitantes);
        $idsVisitantes = Visitor::pluck('id')->all();

        $sessoes = [];
        foreach ($idsVisitantes as $vid) {
            for ($s = 0; $s < 3; $s++) {
                $sessoes[] = [
                    'session_uuid' => (string) Str::orderedUuid(),
                    'visitor_id' => $vid,
                    'started_at' => $agora->copy()->subDays(random_int(0, 60)),
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ];
            }
        }
        foreach (array_chunk($sessoes, 500) as $lote) {
            VisitorSession::insert($lote);
        }
        $idsSessoes = VisitorSession::pluck('id')->all();

        $linhas = [];
        for ($i = 0; $i < self::EVENTOS; $i++) {
            $quando = $agora->copy()->subDays($i % 60)->subMinutes($i % 1440);
            $linhas[] = [
                'event_uuid' => (string) Str::orderedUuid(),
                'visitor_id' => $idsVisitantes[$i % count($idsVisitantes)],
                'session_id' => $idsSessoes[$i % count($idsSessoes)],
                'user_id' => null,
                'event_name' => $eventos[$i % count($eventos)],
                'event_category' => explode('.', $eventos[$i % count($eventos)])[0],
                'entity_type' => null,
                'entity_id' => null,
                'properties' => null,
                'occurred_at' => $quando,
                'created_at' => $quando,
            ];
        }
        foreach (array_chunk($linhas, 1000) as $lote) {
            DB::table('ci_events')->insert($lote);
        }

        // Agregados coerentes com a massa, via o proprio comando de reconstrucao.
        $this->artisan('customer-intelligence:rebuild-daily-metrics')->assertSuccessful();
    }

    /**
     * @return array{queries:int, ms:float}
     */
    private function measure(callable $callback): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $inicio = microtime(true);

        $callback();

        $ms = (microtime(true) - $inicio) * 1000;
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return ['queries' => $queries, 'ms' => $ms];
    }

    public function test_the_panel_holds_up_with_ten_thousand_events(): void
    {
        $this->seedVolume();

        $this->assertSame(self::EVENTOS, DB::table('ci_events')->count());
        $admin = $this->admin();

        $dashboard = $this->measure(fn () => Livewire::actingAs($admin)->test(Dashboard::class)->assertOk());
        $eventos = $this->measure(fn () => Livewire::actingAs($admin)->test(EventIndex::class)->assertOk());
        $visitantes = $this->measure(fn () => Livewire::actingAs($admin)->test(VisitorIndex::class)->assertOk());

        $uuid = Visitor::value('visitor_uuid');
        $detalhe = $this->measure(
            fn () => Livewire::actingAs($admin)->test(VisitorShow::class, ['visitor' => $uuid])->assertOk()
        );

        fwrite(STDERR, sprintf(
            PHP_EOL.'  [CI-06] %d eventos · %d visitantes · %d sessões'.PHP_EOL.
            '          Dashboard   %2d queries  %6.1f ms'.PHP_EOL.
            '          Eventos     %2d queries  %6.1f ms'.PHP_EOL.
            '          Visitantes  %2d queries  %6.1f ms'.PHP_EOL.
            '          Detalhe     %2d queries  %6.1f ms'.PHP_EOL,
            self::EVENTOS,
            self::VISITANTES,
            self::VISITANTES * 3,
            $dashboard['queries'], $dashboard['ms'],
            $eventos['queries'], $eventos['ms'],
            $visitantes['queries'], $visitantes['ms'],
            $detalhe['queries'], $detalhe['ms'],
        ));

        // Teto generoso: o que importa e nao explodir com o volume. Um N+1 numa
        // pagina de 50 eventos passaria facilmente de 50 queries.
        $this->assertLessThan(15, $dashboard['queries'], 'Dashboard com queries demais.');
        $this->assertLessThan(15, $eventos['queries'], 'Listagem de eventos com N+1.');
        $this->assertLessThan(15, $visitantes['queries'], 'Listagem de visitantes com N+1.');
        $this->assertLessThan(15, $detalhe['queries'], 'Detalhe do visitante com N+1.');
    }

    public function test_query_count_does_not_grow_with_the_page_size(): void
    {
        $this->seedVolume();
        $admin = $this->admin();

        // A pagina de eventos traz 50 registros, cada um com visitante e usuario.
        // Sem eager loading isso seriam mais de 100 queries.
        $medida = $this->measure(fn () => Livewire::actingAs($admin)->test(EventIndex::class)->assertOk());

        $this->assertLessThan(
            15,
            $medida['queries'],
            'O número de queries precisa ser constante, não proporcional aos 50 itens da página.'
        );
    }

    public function test_the_dashboard_never_scans_the_raw_events_table(): void
    {
        $this->seedVolume();

        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::actingAs($this->admin())->test(Dashboard::class)->assertOk();
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        // O que não pode existir é agregação de `ci_events` SEM recorte por
        // visitante — isso seria varredura da tabela inteira, que cresce sem
        // limite. O `count` por visitante das listas de recentes é aceitável:
        // são 5 linhas e a subconsulta usa o índice (visitor_id, occurred_at).
        $varreduras = collect($log)->filter(
            fn (array $q) => str_contains($q['query'], 'ci_events')
                && (str_contains($q['query'], 'count(*)') || str_contains($q['query'], 'group by'))
                && ! str_contains($q['query'], 'visitor_id')
        );

        $this->assertCount(
            0,
            $varreduras,
            'Os cartões devem sair de ci_daily_metrics, nunca de uma agregação sobre ci_events inteira.'
        );

        $this->assertGreaterThan(
            0,
            DailyMetric::where('metric_name', MetricName::Eventos->value)->count(),
            'O dashboard depende dos agregados existirem.'
        );
    }
}
