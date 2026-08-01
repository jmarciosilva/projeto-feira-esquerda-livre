<?php

namespace App\Services;

use App\Enums\VisibilitySlotType;
use App\Jobs\ExpositorImpressionJob;
use App\Models\Expositor;
use App\Models\ExpositorVisibilitySlot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ExpositorVisibilityService
{
    public function selectForHome(string $sessionHash): Collection
    {
        $cacheKey = 'expositor_home_selection';
        $ttl      = (int) config('app.home_cache_ttl_minutes', 5);

        $ids = Cache::remember($cacheKey, now()->addMinutes($ttl), function () {
            return $this->buildSelection()->toArray();
        });

        $selected = Expositor::whereIn('id', $ids)
            ->where('is_featured', true)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn ($e) => array_search($e->id, $ids))
            ->values();

        if ($selected->isNotEmpty()) {
            ExpositorImpressionJob::dispatch(
                $selected->pluck('id')->toArray(),
                $sessionHash,
                $this->resolveSourceMap($ids),
            )->onQueue('default');
        }

        return $selected;
    }

    private function buildSelection(): Collection
    {
        $totalSlots   = (int) config('app.home_expositores_count', 9);
        $featuredMax  = (int) config('app.home_featured_max', 2);

        // 1. Destaques pagos ativos
        $featuredSlots = ExpositorVisibilitySlot::where('slot_type', VisibilitySlotType::HomeFeatured)
            ->where('priority', '>', 0)
            ->where(function ($q) {
                $q->whereNull('active_from')->orWhere('active_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('active_until')->orWhere('active_until', '>=', now());
            })
            ->orderByDesc('priority')
            ->with('expositor')
            ->get()
            ->filter(fn ($slot) => $slot->expositor?->is_featured && $slot->expositor?->is_active)
            ->take($featuredMax);

        $featuredIds = $featuredSlots->pluck('expositor_id');

        // 2. Pool de rotação (is_featured = true, sem slot de destaque ativo)
        $rotationPool = Expositor::where('is_featured', true)
            ->where('is_active', true)
            ->whereNotIn('id', $featuredIds)
            ->get(['id', 'home_rotation_weight']);

        $rotationCount = $totalSlots - $featuredIds->count();
        $rotationSelected = $this->weightedRandomSample($rotationPool, $rotationCount);

        return collect(array_merge($featuredIds->toArray(), $rotationSelected));
    }

    private function weightedRandomSample(Collection $pool, int $count): array
    {
        if ($pool->isEmpty() || $count <= 0) {
            return [];
        }

        $pool   = $pool->shuffle();
        $result = [];
        $items  = $pool->toArray();

        while (count($result) < $count && ! empty($items)) {
            $totalWeight = array_sum(array_column($items, 'home_rotation_weight'));

            if ($totalWeight <= 0) {
                break;
            }

            $rand = mt_rand(1, $totalWeight);
            $cumulative = 0;

            foreach ($items as $key => $item) {
                $cumulative += $item['home_rotation_weight'];
                if ($rand <= $cumulative) {
                    $result[] = $item['id'];
                    unset($items[$key]);
                    $items = array_values($items);
                    break;
                }
            }
        }

        return $result;
    }

    private function resolveSourceMap(array $ids): array
    {
        $featuredMax = (int) config('app.home_featured_max', 2);

        $featuredSlotExpositores = ExpositorVisibilitySlot::where('slot_type', VisibilitySlotType::HomeFeatured)
            ->where('priority', '>', 0)
            ->pluck('expositor_id')
            ->toArray();

        $map = [];
        foreach ($ids as $id) {
            $map[$id] = in_array($id, $featuredSlotExpositores)
                ? VisibilitySlotType::HomeFeatured->value
                : VisibilitySlotType::HomeRotation->value;
        }

        return $map;
    }

    public function invalidateCache(): void
    {
        Cache::forget('expositor_home_selection');
    }
}
