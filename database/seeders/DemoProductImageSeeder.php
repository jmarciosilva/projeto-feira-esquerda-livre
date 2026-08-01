<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoProductImageSeeder extends Seeder
{
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
                $path = $directory.'/'.Str::slug($product->slug ?: $product->name).'.svg';

                $disk->put($path, $this->svgFor($product));

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

    private function svgFor(Product $product): string
    {
        $palette = $this->paletteFor((string) $product->item_type->value);
        $label = $this->labelFor((string) $product->item_type->value);
        $symbol = $this->symbolFor((string) $product->item_type->value, $palette);
        $pattern = $this->patternFor($product->slug ?: $product->name, $palette);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="900" height="560" viewBox="0 0 900 560" role="img" aria-labelledby="title desc">
  <title id="title">{$this->escape($product->name)}</title>
  <desc id="desc">Imagem demonstrativa do produto na Feira Esquerda Livre</desc>
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$palette['soft']}"/>
      <stop offset="58%" stop-color="{$palette['light']}"/>
      <stop offset="100%" stop-color="{$palette['warm']}"/>
    </linearGradient>
    <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="20" stdDeviation="22" flood-color="#3b2d00" flood-opacity="0.18"/>
    </filter>
  </defs>
  <rect width="900" height="560" fill="url(#bg)"/>
  <rect width="900" height="560" fill="#fff8d7" opacity="0.18"/>
  <circle cx="210" cy="88" r="120" fill="#ffffff" opacity="0.20"/>
  <circle cx="724" cy="472" r="162" fill="#ffffff" opacity="0.22"/>
  <path d="M0 394 C142 342 244 432 392 362 C548 288 680 312 900 214 L900 560 L0 560 Z" fill="#ffffff" opacity="0.26"/>
  {$pattern}
  <g filter="url(#shadow)">
    <circle cx="450" cy="278" r="160" fill="#fffdf2" opacity="0.94"/>
    <circle cx="450" cy="278" r="132" fill="{$palette['soft']}" opacity="0.86"/>
  </g>
  {$symbol}
  <rect x="336" y="430" width="228" height="46" rx="23" fill="{$palette['dark']}" opacity="0.96"/>
  <text x="450" y="460" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="20" font-weight="900" fill="#fff8d5">{$this->escape($label)}</text>
  <text x="450" y="516" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="18" font-weight="800" fill="{$palette['dark']}" opacity="0.72">Feira Esquerda Livre</text>
</svg>
SVG;
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

    /**
     * @param array{soft: string, light: string, warm: string, accent: string, dark: string} $palette
     */
    private function symbolFor(string $type, array $palette): string
    {
        return match ($type) {
            'servico' => <<<SVG
  <g transform="translate(450 278)">
    <circle cx="0" cy="-8" r="74" fill="{$palette['accent']}" opacity="0.18"/>
    <path d="M-72 42 L-72 -42 Q-72 -72 -42 -72 L42 -72 Q72 -72 72 -42 L72 20 Q72 50 42 50 L-18 50 L-54 82 L-46 50 L-42 50 Q-72 50 -72 42 Z" fill="#fffdf2" stroke="{$palette['dark']}" stroke-width="14" stroke-linejoin="round"/>
    <circle cx="-30" cy="-14" r="10" fill="{$palette['accent']}"/>
    <circle cx="0" cy="-14" r="10" fill="{$palette['accent']}"/>
    <circle cx="30" cy="-14" r="10" fill="{$palette['accent']}"/>
    <path d="M-70 88 C-30 110 30 110 70 88" fill="none" stroke="{$palette['dark']}" stroke-width="14" stroke-linecap="round" opacity="0.28"/>
  </g>
SVG,
            'cuidado' => <<<SVG
  <g transform="translate(450 278)">
    <circle cx="0" cy="0" r="80" fill="{$palette['accent']}" opacity="0.16"/>
    <path d="M0 96 C-82 48 -118 -10 -86 -58 C-58 -100 -12 -80 0 -42 C12 -80 58 -100 86 -58 C118 -10 82 48 0 96 Z" fill="#fffdf2" stroke="{$palette['dark']}" stroke-width="13" stroke-linejoin="round"/>
    <path d="M-88 8 C-26 -2 14 -34 52 -86 C72 -44 54 0 12 32 C-22 58 -54 56 -88 8 Z" fill="{$palette['accent']}" opacity="0.76"/>
    <path d="M-48 18 C-12 4 20 -26 48 -66" fill="none" stroke="#fff8d5" stroke-width="10" stroke-linecap="round" opacity="0.82"/>
  </g>
SVG,
            default => <<<SVG
  <g transform="translate(450 278)">
    <circle cx="0" cy="-2" r="78" fill="{$palette['accent']}" opacity="0.18"/>
    <path d="M-86 -18 H86 L68 78 H-68 Z" fill="#fffdf2" stroke="{$palette['dark']}" stroke-width="14" stroke-linejoin="round"/>
    <path d="M-48 -18 C-42 -78 42 -78 48 -18" fill="none" stroke="{$palette['dark']}" stroke-width="14" stroke-linecap="round"/>
    <rect x="-42" y="10" width="84" height="42" rx="12" fill="{$palette['accent']}" opacity="0.85"/>
    <path d="M-94 96 C-44 118 44 118 94 96" fill="none" stroke="{$palette['dark']}" stroke-width="14" stroke-linecap="round" opacity="0.28"/>
  </g>
SVG,
        };
    }

    /**
     * @param array{soft: string, light: string, warm: string, accent: string, dark: string} $palette
     */
    private function patternFor(string $seed, array $palette): string
    {
        $number = abs(crc32($seed));
        $x1 = 230 + ($number % 80);
        $y1 = 160 + (($number >> 3) % 70);
        $x2 = 590 + (($number >> 6) % 90);
        $y2 = 120 + (($number >> 9) % 80);
        $x3 = 232 + (($number >> 12) % 120);
        $y3 = 382 + (($number >> 15) % 70);

        return <<<SVG
  <circle cx="{$x1}" cy="{$y1}" r="18" fill="{$palette['accent']}" opacity="0.34"/>
  <rect x="{$x2}" y="{$y2}" width="54" height="54" rx="14" fill="#ffffff" opacity="0.33"/>
  <circle cx="{$x3}" cy="{$y3}" r="26" fill="{$palette['dark']}" opacity="0.11"/>
SVG;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
