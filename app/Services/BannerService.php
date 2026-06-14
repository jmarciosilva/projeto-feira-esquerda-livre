<?php

namespace App\Services;

use App\Models\Banner;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BannerService
{
    /**
     * Lista os banners paginados.
     *
     * @param array<string, mixed> $filters
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Banner::query()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->orderBy('sort_order')
            ->paginate($perPage);
    }

    /**
     * Cria um novo banner.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Banner
    {
        DB::beginTransaction();

        try {
            $banner = Banner::create($data);

            DB::commit();

            return $banner;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Atualiza um banner existente.
     *
     * @param array<string, mixed> $data
     */
    public function update(Banner $banner, array $data): Banner
    {
        DB::beginTransaction();

        try {
            $banner->update($data);

            DB::commit();

            return $banner->fresh();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove um banner e seus arquivos.
     */
    public function delete(Banner $banner): void
    {
        DB::beginTransaction();

        try {
            if ($banner->image_path) {
                Storage::disk('public')->delete($banner->image_path);
            }
            if ($banner->mobile_image_path) {
                Storage::disk('public')->delete($banner->mobile_image_path);
            }

            $banner->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reordena banners conforme array de IDs.
     *
     * @param list<int> $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        DB::beginTransaction();

        try {
            foreach ($orderedIds as $position => $id) {
                Banner::where('id', $id)->update(['sort_order' => $position]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
