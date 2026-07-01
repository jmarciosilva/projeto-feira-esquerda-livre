<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FeedImageService
{
    public function __construct(private ImageService $imageService) {}

    /**
     * @param array<int, UploadedFile|null> $uploads
     * @return array<int, array{thumb: string, medium: string}>
     */
    public function storeMany(array $uploads, int $limit = 4): array
    {
        $images = [];

        foreach ($uploads as $upload) {
            if (! $upload || count($images) >= $limit) {
                continue;
            }

            $images[] = $this->imageService->store($upload, 'feed');
        }

        return $images;
    }

    public function deleteMany(array $images): void
    {
        foreach ($images as $image) {
            Storage::disk('public')->delete($image['thumb'] ?? '');
            Storage::disk('public')->delete($image['medium'] ?? '');
        }
    }
}
