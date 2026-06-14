<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventService
{
    /**
     * Lista os eventos paginados com filtros.
     *
     * @param array<string, mixed> $filters
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Event::query()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->when($filters['state'] ?? null, fn ($q, $s) => $q->where('state', $s))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->orderBy('start_date')
            ->paginate($perPage);
    }

    /**
     * Cria um novo evento.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Event
    {
        DB::beginTransaction();

        try {
            $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['title']);

            $event = Event::create($data);

            DB::commit();

            return $event;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Atualiza um evento existente.
     *
     * @param array<string, mixed> $data
     */
    public function update(Event $event, array $data): Event
    {
        DB::beginTransaction();

        try {
            if (isset($data['slug']) && $data['slug'] !== $event->slug) {
                $data['slug'] = $this->uniqueSlug($data['slug'], $event->id);
            }

            $event->update($data);

            DB::commit();

            return $event->fresh();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove um evento.
     */
    public function delete(Event $event): void
    {
        DB::beginTransaction();

        try {
            $event->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base);
        $count = 0;
        $candidate = $slug;

        while (
            Event::where('slug', $candidate)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $slug . '-' . ++$count;
        }

        return $candidate;
    }
}
