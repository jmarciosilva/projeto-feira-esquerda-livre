<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PageService
{
    /**
     * Lista as páginas paginadas com filtro de busca.
     *
     * @param array<string, mixed> $filters
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Page::query()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Cria uma nova página.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Page
    {
        DB::beginTransaction();

        try {
            $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['title']);

            $page = Page::create($data);

            if ($page->is_homepage) {
                $this->clearOtherHomepages($page->id);
            }

            DB::commit();

            return $page;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Atualiza uma página existente.
     *
     * @param array<string, mixed> $data
     */
    public function update(Page $page, array $data): Page
    {
        DB::beginTransaction();

        try {
            if (isset($data['slug']) && $data['slug'] !== $page->slug) {
                $data['slug'] = $this->uniqueSlug($data['slug'], $page->id);
            }

            $page->update($data);

            if ($page->is_homepage) {
                $this->clearOtherHomepages($page->id);
            }

            DB::commit();

            return $page->fresh();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove uma página e suas seções.
     */
    public function delete(Page $page): void
    {
        DB::beginTransaction();

        try {
            $page->sections()->delete();
            $page->delete();

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
            Page::where('slug', $candidate)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $slug . '-' . ++$count;
        }

        return $candidate;
    }

    private function clearOtherHomepages(int $currentId): void
    {
        Page::where('id', '!=', $currentId)->update(['is_homepage' => false]);
    }
}
