<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageService
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Salva uma imagem em dois tamanhos (thumb 300px e médio 800px) em WebP.
     * Retorna array com ['thumb' => path, 'medium' => path].
     */
    public function store(UploadedFile $file, string $directory): array
    {
        $name = Str::uuid();
        $disk = Storage::disk('public');

        $dir = ltrim($directory, '/');

        $thumbPath  = "{$dir}/{$name}_thumb.webp";
        $mediumPath = "{$dir}/{$name}_medium.webp";

        $thumbBytes = $this->manager
            ->read($file->getRealPath())
            ->scaleDown(width: 300, height: 300)
            ->toWebp(quality: 80)
            ->toString();

        $mediumBytes = $this->manager
            ->read($file->getRealPath())
            ->scaleDown(width: 800, height: 800)
            ->toWebp(quality: 82)
            ->toString();

        $disk->put($thumbPath,  $thumbBytes);
        $disk->put($mediumPath, $mediumBytes);

        return [
            'thumb'  => $thumbPath,
            'medium' => $mediumPath,
        ];
    }

    /** Remove os arquivos de um conjunto de imagens (thumb + medium). */
    public function delete(array $imagePaths): void
    {
        $disk = Storage::disk('public');
        foreach ($imagePaths as $path) {
            if ($path) {
                $disk->delete($path);
            }
        }
    }
}
