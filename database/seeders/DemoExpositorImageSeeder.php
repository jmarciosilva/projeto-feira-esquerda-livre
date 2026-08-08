<?php

namespace Database\Seeders;

use App\Models\Expositor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class DemoExpositorImageSeeder extends Seeder
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    public function run(): void
    {
        $disk = Storage::disk('public');
        $directory = 'demo-expositores';
        $count = 0;

        Expositor::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->each(function (Expositor $expositor) use ($disk, $directory, &$count) {
                $slug = Str::slug($expositor->slug ?: $expositor->name);
                $coverPath = "{$directory}/{$slug}-capa.png";
                $logoPath = "{$directory}/{$slug}-logo.png";

                $disk->put($coverPath, $this->coverFor($expositor));
                $disk->put($logoPath, $this->logoFor($expositor));

                $expositor->forceFill([
                    'image_path' => $this->shouldReplace($expositor->image_path, $directory) ? $coverPath : $expositor->image_path,
                    'logo_path' => $this->shouldReplace($expositor->logo_path, $directory) ? $logoPath : $expositor->logo_path,
                ])->save();

                $count++;
            });

        $this->command->info("DemoExpositorImageSeeder: {$count} capas e logos demonstrativos gerados para expositores.");
    }

    private function shouldReplace(?string $path, string $directory): bool
    {
        return empty($path) || Str::startsWith($path, "{$directory}/");
    }

    /**
     * Placeholder raster (PNG) — precisa ser um formato que o app Flutter
     * consiga decodificar (SVG não é suportado sem um pacote extra tipo
     * flutter_svg, e o pipeline real de upload, ver ImageService, também
     * nunca gera SVG, só webp).
     */
    private function coverFor(Expositor $expositor): string
    {
        $palette = $this->paletteFor($expositor);

        $image = $this->manager->create(1200, 630);
        $image->fill($palette['light']);

        $image->drawCircle(1050, 60, function ($circle) use ($palette) {
            $circle->radius(160);
            $circle->background($palette['warm']);
        });

        $image->drawCircle(100, 580, function ($circle) use ($palette) {
            $circle->radius(190);
            $circle->background($palette['accent']);
        });

        $image->drawRectangle(150, 120, function ($rectangle) use ($palette) {
            $rectangle->size(900, 390);
            $rectangle->background($palette['soft']);
        });

        $image->text($expositor->name, 600, 315, function ($font) use ($palette) {
            $font->size(5);
            $font->color($palette['dark']);
            $font->align('center');
            $font->valign('middle');
        });

        return $image->toPng()->toString();
    }

    private function logoFor(Expositor $expositor): string
    {
        $palette = $this->paletteFor($expositor);
        $initials = $this->initials($expositor->name);

        $image = $this->manager->create(512, 512);
        $image->fill($palette['light']);

        $image->drawCircle(256, 256, function ($circle) use ($palette) {
            $circle->radius(220);
            $circle->background($palette['soft']);
        });

        $image->drawCircle(256, 256, function ($circle) use ($palette) {
            $circle->radius(180);
            $circle->background($palette['accent']);
        });

        $image->text($initials, 256, 256, function ($font) use ($palette) {
            $font->size(5);
            $font->color($palette['dark']);
            $font->align('center');
            $font->valign('middle');
        });

        return $image->toPng()->toString();
    }

    /**
     * @return array{soft: string, light: string, warm: string, accent: string, dark: string}
     */
    private function paletteFor(Expositor $expositor): array
    {
        $eixos = $expositor->eixos ?: [];
        $primary = $eixos[0] ?? 'produto';

        return match ($primary) {
            'servico' => [
                'soft' => '#f3fff4',
                'light' => '#d7f3dc',
                'warm' => '#b7df84',
                'accent' => '#75b843',
                'dark' => '#22502b',
            ],
            'cuidado' => [
                'soft' => '#fff8df',
                'light' => '#ffe5a3',
                'warm' => '#f3bb41',
                'accent' => '#d58900',
                'dark' => '#553900',
            ],
            default => [
                'soft' => '#fff9d8',
                'light' => '#f9df78',
                'warm' => '#f2b31a',
                'accent' => '#eea500',
                'dark' => '#473500',
            ],
        };
    }

    private function initials(string $text): string
    {
        $words = collect(explode(' ', Str::ascii($text)))
            ->filter(fn (string $word) => strlen($word) > 2)
            ->take(2)
            ->map(fn (string $word) => strtoupper($word[0]));

        return $words->implode('') ?: 'FL';
    }
}
