<?php

namespace App\Jobs;

use App\Models\ExpositorImpression;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ExpositorImpressionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly array $expositorIds,
        private readonly string $sessionHash,
        private readonly array $sourceMap = [],
    ) {}

    public function handle(): void
    {
        if (empty($this->expositorIds)) {
            return;
        }

        $renderedAt = now();
        $rows = [];

        foreach ($this->expositorIds as $id) {
            $rows[] = [
                'expositor_id' => $id,
                'rendered_at'  => $renderedAt,
                'session_hash' => $this->sessionHash,
                'source'       => $this->sourceMap[$id] ?? 'home_rotation',
                'created_at'   => $renderedAt,
            ];
        }

        ExpositorImpression::insert($rows);

        // Atualiza contadores desnormalizados em bulk
        DB::table('expositores')
            ->whereIn('id', $this->expositorIds)
            ->increment('total_impressions');
    }
}
