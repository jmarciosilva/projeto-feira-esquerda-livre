<?php

namespace Tests\Feature\CustomerIntelligence;

use App\CustomerIntelligence\Models\DailyMetric;
use App\CustomerIntelligence\Models\TrackedEvent;
use App\CustomerIntelligence\Models\Visitor;
use App\CustomerIntelligence\Models\VisitorSession;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Fundacao do modulo interno de Customer Intelligence (CI-02): estrutura das
 * quatro tabelas, chaves estrangeiras, unicidade e indices criticos.
 */
class InternalSchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Os nomes de indice diferem entre MySQL e SQLite; o que importa e que
     * exista um indice comecando pelas colunas informadas.
     *
     * @param  list<string>  $columns
     */
    private function assertIndexedBy(string $table, array $columns): void
    {
        $found = collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => array_slice($index['columns'], 0, count($columns)) === $columns);

        $this->assertTrue(
            $found,
            sprintf('Esperava indice em %s(%s).', $table, implode(', ', $columns))
        );
    }

    private function assertColumnNullable(string $table, string $column, bool $nullable): void
    {
        $definition = collect(Schema::getColumns($table))->firstWhere('name', $column);

        $this->assertNotNull($definition, "Coluna {$table}.{$column} nao existe.");
        $this->assertSame(
            $nullable,
            $definition['nullable'],
            "Nulabilidade inesperada em {$table}.{$column}."
        );
    }

    public function test_the_four_module_tables_exist(): void
    {
        foreach (['ci_visitors', 'ci_sessions', 'ci_events', 'ci_daily_metrics'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Tabela {$table} nao foi criada.");
        }
    }

    public function test_ci_visitors_has_expected_columns_and_indexes(): void
    {
        $this->assertTrue(Schema::hasColumns('ci_visitors', [
            'id', 'visitor_uuid', 'user_id', 'first_seen_at', 'last_seen_at',
            'metadata', 'created_at', 'updated_at',
        ]));

        $this->assertColumnNullable('ci_visitors', 'visitor_uuid', false);
        $this->assertColumnNullable('ci_visitors', 'user_id', true);

        $this->assertIndexedBy('ci_visitors', ['visitor_uuid']);
        $this->assertIndexedBy('ci_visitors', ['last_seen_at']);
    }

    public function test_ci_sessions_has_expected_columns_and_indexes(): void
    {
        $this->assertTrue(Schema::hasColumns('ci_sessions', [
            'id', 'session_uuid', 'visitor_id', 'started_at', 'last_activity_at',
            'ended_at', 'landing_url', 'referrer', 'utm_source', 'utm_medium', 'utm_campaign',
        ]));

        $this->assertColumnNullable('ci_sessions', 'visitor_id', false);
        $this->assertColumnNullable('ci_sessions', 'ended_at', true);

        $this->assertIndexedBy('ci_sessions', ['session_uuid']);
        $this->assertIndexedBy('ci_sessions', ['started_at']);
    }

    public function test_ci_events_has_expected_columns_and_indexes(): void
    {
        $this->assertTrue(Schema::hasColumns('ci_events', [
            'id', 'event_uuid', 'visitor_id', 'session_id', 'user_id', 'event_name',
            'event_category', 'entity_type', 'entity_id', 'properties',
            'occurred_at', 'created_at',
        ]));

        // Um evento pode existir sem visitante, sessao ou usuario resolvidos.
        $this->assertColumnNullable('ci_events', 'visitor_id', true);
        $this->assertColumnNullable('ci_events', 'session_id', true);
        $this->assertColumnNullable('ci_events', 'user_id', true);
        $this->assertColumnNullable('ci_events', 'event_name', false);
        $this->assertColumnNullable('ci_events', 'occurred_at', false);

        $this->assertIndexedBy('ci_events', ['event_uuid']);
        $this->assertIndexedBy('ci_events', ['event_name', 'occurred_at']);
        $this->assertIndexedBy('ci_events', ['occurred_at']);
        $this->assertIndexedBy('ci_events', ['entity_type', 'entity_id']);
    }

    public function test_ci_events_is_append_only_and_has_no_updated_at(): void
    {
        $this->assertFalse(
            Schema::hasColumn('ci_events', 'updated_at'),
            'ci_events e append-only: nao deve ter updated_at.'
        );
        $this->assertNull(TrackedEvent::UPDATED_AT);
    }

    public function test_uuid_columns_are_unique(): void
    {
        $visitor = Visitor::create(['first_seen_at' => now()]);

        $this->expectException(QueryException::class);

        Visitor::create([
            'visitor_uuid' => $visitor->visitor_uuid,
            'first_seen_at' => now(),
        ]);
    }

    public function test_daily_metrics_key_is_unique_across_date_name_and_dimension(): void
    {
        DailyMetric::create([
            'metric_date' => '2026-08-25',
            'metric_name' => 'eventos',
            'dimension_type' => '',
            'dimension_value' => '',
            'metric_value' => 10,
        ]);

        $this->expectException(QueryException::class);

        DailyMetric::create([
            'metric_date' => '2026-08-25',
            'metric_name' => 'eventos',
            'dimension_type' => '',
            'dimension_value' => '',
            'metric_value' => 20,
        ]);
    }

    public function test_daily_metrics_allows_the_same_metric_on_different_dimensions(): void
    {
        DailyMetric::record('2026-08-25', 'eventos', 10);
        DailyMetric::record('2026-08-25', 'eventos', 4, 'expositor', '14');
        DailyMetric::record('2026-08-25', 'eventos', 6, 'expositor', '15');

        $this->assertSame(3, DailyMetric::count());
    }

    public function test_deleting_a_visitor_cascades_to_sessions(): void
    {
        $visitor = Visitor::create(['first_seen_at' => now()]);
        VisitorSession::create(['visitor_id' => $visitor->id, 'started_at' => now()]);

        $visitor->delete();

        $this->assertSame(0, VisitorSession::count());
    }

    public function test_deleting_a_user_keeps_the_visitor_as_anonymous(): void
    {
        $user = User::factory()->create();
        $visitor = Visitor::create(['user_id' => $user->id, 'first_seen_at' => now()]);

        $user->delete();

        $this->assertNull($visitor->fresh()->user_id);
        $this->assertSame(1, Visitor::count());
    }
}
