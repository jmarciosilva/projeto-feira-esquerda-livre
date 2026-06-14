<?php

namespace App\Services;

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    /**
     * Lista a biblioteca de mídias paginada.
     *
     * @param array<string, mixed> $filters
     */
    public function list(array $filters = [], int $perPage = 24): LengthAwarePaginator
    {
        return Media::query()
            ->with('uploader')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('file_name', 'like', "%{$s}%"))
            ->when($filters['type'] ?? null, fn ($q, $t) => $q->where('file_type', 'like', "{$t}%"))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Faz upload e registra um arquivo de mídia.
     */
    public function upload(UploadedFile $file, User $uploader): Media
    {
        DB::beginTransaction();

        try {
            $originalName = $file->getClientOriginalName();
            $fileName     = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path         = $file->storeAs('media/' . date('Y/m'), $fileName, 'public');

            $media = Media::create([
                'file_name'   => $originalName,
                'file_path'   => $path,
                'file_type'   => $file->getMimeType(),
                'file_size'   => $file->getSize(),
                'uploaded_by' => $uploader->id,
            ]);

            DB::commit();

            return $media;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove um arquivo de mídia do disco e do banco.
     */
    public function delete(Media $media): void
    {
        DB::beginTransaction();

        try {
            Storage::disk('public')->delete($media->file_path);
            $media->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
