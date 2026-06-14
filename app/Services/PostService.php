<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostService
{
    /**
     * Lista os posts paginados com filtros.
     *
     * @param array<string, mixed> $filters
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Post::query()
            ->with(['category', 'author'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->when($filters['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Cria um novo post.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Post
    {
        DB::beginTransaction();

        try {
            $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['title']);

            if (($data['status'] ?? '') === PostStatus::Published->value && empty($data['published_at'])) {
                $data['published_at'] = Carbon::now();
            }

            $post = Post::create($data);

            DB::commit();

            return $post;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Atualiza um post existente.
     *
     * @param array<string, mixed> $data
     */
    public function update(Post $post, array $data): Post
    {
        DB::beginTransaction();

        try {
            if (isset($data['slug']) && $data['slug'] !== $post->slug) {
                $data['slug'] = $this->uniqueSlug($data['slug'], $post->id);
            }

            if (
                ($data['status'] ?? '') === PostStatus::Published->value
                && $post->status !== PostStatus::Published
                && empty($data['published_at'])
            ) {
                $data['published_at'] = Carbon::now();
            }

            $post->update($data);

            DB::commit();

            return $post->fresh();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove um post.
     */
    public function delete(Post $post): void
    {
        DB::beginTransaction();

        try {
            $post->delete();

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
            Post::where('slug', $candidate)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $slug . '-' . ++$count;
        }

        return $candidate;
    }
}
