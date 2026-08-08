<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class DemoProductImageSeeder extends Seeder
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    public function run(): void
    {
        $disk = Storage::disk('public');
        $directory = 'demo-products';
        $count = 0;

        Product::query()
            ->with('expositor')
            ->where('is_active', true)
            ->orderBy('item_type')
            ->orderBy('name')
            ->get()
            ->each(function (Product $product) use ($disk, $directory, &$count) {
                $path = $directory.'/'.Str::slug($product->slug ?: $product->name).'.png';

                $disk->put($path, $this->imageFor($product));

                $product->forceFill([
                    'image_path' => $path,
                    'images' => [[
                        'thumb' => $path,
                        'medium' => $path,
                    ]],
                ])->save();

                $count++;
            });

        $this->command->info("DemoProductImageSeeder: {$count} imagens demonstrativas geradas e vinculadas aos produtos.");
    }

    /**
     * Placeholder raster (PNG) — precisa ser um formato que o app Flutter
     * consiga decodificar (SVG não é suportado sem um pacote extra tipo
     * flutter_svg, e o pipeline real de upload, ver ImageService, também
     * nunca gera SVG, só webp).
     */
    private function imageFor(Product $product): string
    {
        $palette = $this->paletteFor((string) $product->item_type->value);
        $label = $this->labelFor((string) $product->item_type->value);

        $image = $this->manager->create(900, 560);
        $image->fill($palette['light']);

        $image->drawRectangle(0, 380, function ($rectangle) use ($palette) {
            $rectangle->size(900, 180);
            $rectangle->background($palette['warm']);
        });

        $image->drawCircle(450, 260, function ($circle) use ($palette) {
            $circle->radius(150);
            $circle->background($palette['soft']);
        });

        $image->drawCircle(450, 260, function ($circle) use ($palette) {
            $circle->radius(100);
            $circle->background($palette['accent']);
        });

        $image->drawRectangle(336, 430, function ($rectangle) use ($palette) {
            $rectangle->size(228, 46);
            $rectangle->background($palette['dark']);
        });

        $image->text($label, 450, 453, function ($font) {
            $font->size(5);
            $font->color('#fff8d5');
            $font->align('center');
            $font->valign('middle');
        });

        return $image->toPng()->toString();
    }

    /**
     * @return array{soft: string, light: string, warm: string, accent: string, dark: string}
     */
    private function paletteFor(string $type): array
    {
        return match ($type) {
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

    private function labelFor(string $type): string
    {
        return match ($type) {
            'servico' => 'Servico',
            'cuidado' => 'Cuidado e Bem Viver',
            default => 'Produto',
        };
    }

}
