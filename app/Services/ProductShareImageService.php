<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Typography\FontFactory;

class ProductShareImageService
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    public function make(Product $product): string
    {
        $product->loadMissing('expositor');

        $canvas = $this->manager->create(1080, 1080)->fill('#F7F3E4');
        $canvas->drawRectangle(0, 0, function ($rectangle): void {
            $rectangle->size(1080, 1080);
            $rectangle->background('#F7F3E4');
        });
        $canvas->drawRectangle(0, 0, function ($rectangle): void {
            $rectangle->size(1080, 150);
            $rectangle->background('#F4E294');
        });

        if ($imagePath = $this->productImagePath($product)) {
            $image = $this->manager->read(Storage::disk('public')->path($imagePath))->cover(900, 560);
            $canvas->place($image, 'top-left', 90, 190);
        } else {
            $canvas->drawRectangle(90, 190, function ($rectangle): void {
                $rectangle->size(900, 560);
                $rectangle->background('#E8A000');
            });
        }

        $regular = $this->fontPath('arial.ttf');
        $bold = $this->fontPath('arialbd.ttf');
        $storeName = $product->expositor?->name ?? 'Feira Esquerda Livre';
        $price = $product->price
            ? 'R$ ' . number_format((float) $product->price, 2, ',', '.')
            : 'Consulte o valor';
        $url = route('loja.produto', [$product->expositor?->slug, $product->slug]);
        $titleX = 90;

        if ($product->expositor?->logo_path && Storage::disk('public')->exists($product->expositor->logo_path)) {
            $logo = $this->manager->read(Storage::disk('public')->path($product->expositor->logo_path))->cover(78, 78);
            $canvas->place($logo, 'top-left', 90, 36);
            $titleX = 188;
        }

        $canvas->text('Feira Esquerda Livre', $titleX, 92, function (FontFactory $font) use ($bold): void {
            $font->filename($bold)->size(44)->color('#3D3000')->valign('middle');
        });
        $canvas->text($storeName, 990, 92, function (FontFactory $font) use ($regular): void {
            $font->filename($regular)->size(28)->color('#5C4500')->align('right')->valign('middle');
        });
        $canvas->text($this->wrap($product->name, 29, 2), 90, 820, function (FontFactory $font) use ($bold): void {
            $font->filename($bold)->size(52)->color('#1F2937')->lineHeight(1.15);
        });
        $canvas->text($price, 90, 965, function (FontFactory $font) use ($bold): void {
            $font->filename($bold)->size(52)->color('#C47A00')->valign('bottom');
        });
        $canvas->text($this->shortUrl($url), 990, 965, function (FontFactory $font) use ($regular): void {
            $font->filename($regular)->size(26)->color('#3D3000')->align('right')->valign('bottom');
        });

        return $canvas->toPng()->toString();
    }

    private function productImagePath(Product $product): ?string
    {
        $images = $product->images ?? [];

        return $images[0]['medium'] ?? $images[0]['thumb'] ?? $product->image_path;
    }

    private function fontPath(string $name): string
    {
        $windowsPath = "C:\\Windows\\Fonts\\{$name}";

        return file_exists($windowsPath) ? $windowsPath : $windowsPath;
    }

    private function wrap(string $text, int $width, int $lines): string
    {
        $wrapped = explode("\n", wordwrap($text, $width));

        return implode("\n", array_slice($wrapped, 0, $lines));
    }

    private function shortUrl(string $url): string
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? $url;

        return trim($path, '/');
    }
}
