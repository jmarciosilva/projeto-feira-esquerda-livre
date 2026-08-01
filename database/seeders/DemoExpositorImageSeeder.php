<?php

namespace Database\Seeders;

use App\Models\Expositor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoExpositorImageSeeder extends Seeder
{
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
                $coverPath = "{$directory}/{$slug}-capa.svg";
                $logoPath = "{$directory}/{$slug}-logo.svg";

                $disk->put($coverPath, $this->coverSvgFor($expositor));
                $disk->put($logoPath, $this->logoSvgFor($expositor));

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

    private function coverSvgFor(Expositor $expositor): string
    {
        $palette = $this->paletteFor($expositor);
        $pattern = $this->patternFor($expositor->slug ?: $expositor->name, $palette);
        $display = $this->displayFor($expositor, $palette);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="720" viewBox="0 0 1200 720" role="img" aria-labelledby="title desc">
  <title id="title">{$this->escape($expositor->name)}</title>
  <desc id="desc">Capa demonstrativa do expositor na Feira Esquerda Livre</desc>
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$palette['soft']}"/>
      <stop offset="54%" stop-color="{$palette['light']}"/>
      <stop offset="100%" stop-color="{$palette['warm']}"/>
    </linearGradient>
    <linearGradient id="canopy" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0%" stop-color="#f43f2e"/>
      <stop offset="50%" stop-color="#fff8d5"/>
      <stop offset="100%" stop-color="{$palette['accent']}"/>
    </linearGradient>
    <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="22" stdDeviation="24" flood-color="#3b2d00" flood-opacity="0.16"/>
    </filter>
  </defs>
  <rect width="1200" height="720" fill="url(#bg)"/>
  <circle cx="1000" cy="118" r="188" fill="#ffffff" opacity="0.20"/>
  <circle cx="150" cy="614" r="220" fill="#ffffff" opacity="0.17"/>
  <path d="M0 500 C170 428 312 548 500 448 C700 342 850 388 1200 260 L1200 720 L0 720 Z" fill="#ffffff" opacity="0.24"/>
  {$pattern}
  <g filter="url(#shadow)">
    <rect x="162" y="186" width="876" height="346" rx="34" fill="#fffdf2" opacity="0.90"/>
  </g>
  <g transform="translate(232 112)">
    <rect x="0" y="78" width="736" height="64" rx="14" fill="url(#canopy)"/>
    <path d="M0 126 H736 L690 196 H46 Z" fill="#fff8d5" opacity="0.96"/>
    <path d="M46 196 H690" stroke="{$palette['dark']}" stroke-width="12" stroke-linecap="round" opacity="0.20"/>
    <rect x="36" y="270" width="664" height="72" rx="20" fill="{$palette['dark']}" opacity="0.90"/>
    <rect x="70" y="236" width="596" height="70" rx="18" fill="#fffdf2"/>
    <line x1="92" y1="196" x2="92" y2="338" stroke="{$palette['dark']}" stroke-width="12" stroke-linecap="round" opacity="0.35"/>
    <line x1="644" y1="196" x2="644" y2="338" stroke="{$palette['dark']}" stroke-width="12" stroke-linecap="round" opacity="0.35"/>
    <circle cx="104" cy="372" r="38" fill="#fffdf2"/>
    <circle cx="632" cy="372" r="38" fill="#fffdf2"/>
    {$display}
  </g>
</svg>
SVG;
    }

    private function logoSvgFor(Expositor $expositor): string
    {
        $palette = $this->paletteFor($expositor);
        $initials = $this->initials($expositor->name);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512" role="img" aria-labelledby="title desc">
  <title id="title">{$this->escape($expositor->name)}</title>
  <desc id="desc">Logo demonstrativo do expositor</desc>
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$palette['light']}"/>
      <stop offset="100%" stop-color="{$palette['accent']}"/>
    </linearGradient>
  </defs>
  <rect width="512" height="512" fill="url(#bg)"/>
  <circle cx="256" cy="256" r="190" fill="#fffdf2" opacity="0.88"/>
  <circle cx="346" cy="150" r="46" fill="{$palette['dark']}" opacity="0.18"/>
  <path d="M122 346 C186 386 326 386 390 346" fill="none" stroke="{$palette['dark']}" stroke-width="24" stroke-linecap="round" opacity="0.22"/>
  <text x="256" y="286" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="116" font-weight="900" fill="{$palette['dark']}">{$this->escape($initials)}</text>
  <text x="256" y="358" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="30" font-weight="900" fill="#6d5600">FEL</text>
</svg>
SVG;
    }

    /**
     * @param array{soft: string, light: string, warm: string, accent: string, dark: string} $palette
     */
    private function displayFor(Expositor $expositor, array $palette): string
    {
        $name = Str::lower(Str::ascii($expositor->name));

        if (str_contains($name, 'ceramica')) {
            return <<<SVG
    <ellipse cx="250" cy="268" rx="52" ry="18" fill="{$palette['accent']}" opacity="0.45"/>
    <path d="M220 170 C198 214 202 260 250 278 C298 260 302 214 280 170 Z" fill="#b86b35"/>
    <path d="M232 156 C232 128 268 128 268 156" fill="none" stroke="#7c3f1d" stroke-width="14" stroke-linecap="round"/>
    <path d="M412 162 C386 212 392 262 452 282 C512 262 518 212 492 162 Z" fill="#d58900"/>
    <path d="M430 146 C430 118 474 118 474 146" fill="none" stroke="#7c3f1d" stroke-width="14" stroke-linecap="round"/>
    <circle cx="344" cy="244" r="38" fill="#fff8d5" stroke="#7c3f1d" stroke-width="10"/>
SVG;
        }

        if (str_contains($name, 'mel')) {
            return <<<SVG
    <g fill="{$palette['accent']}" stroke="{$palette['dark']}" stroke-width="8">
      <path d="M234 178 H306 L342 240 L306 302 H234 L198 240 Z"/>
      <path d="M342 178 H414 L450 240 L414 302 H342 L306 240 Z" opacity="0.72"/>
      <path d="M306 302 H378 L414 364 L378 426 H306 L270 364 Z" opacity="0.56"/>
    </g>
    <rect x="482" y="210" width="82" height="136" rx="22" fill="#fff8d5" stroke="{$palette['dark']}" stroke-width="10"/>
    <rect x="494" y="188" width="58" height="34" rx="10" fill="{$palette['dark']}"/>
SVG;
        }

        if (str_contains($name, 'pinceis') || str_contains($name, 'foto') || str_contains($name, 'livre')) {
            return <<<SVG
    <rect x="204" y="154" width="154" height="178" rx="18" fill="#fff8d5" stroke="{$palette['dark']}" stroke-width="10"/>
    <circle cx="282" cy="240" r="48" fill="{$palette['accent']}"/>
    <path d="M426 330 L548 208" stroke="{$palette['dark']}" stroke-width="22" stroke-linecap="round"/>
    <path d="M528 184 L584 128" stroke="#f43f2e" stroke-width="30" stroke-linecap="round"/>
    <circle cx="440" cy="190" r="28" fill="#39a852"/>
    <circle cx="488" cy="252" r="26" fill="#f2b31a"/>
SVG;
        }

        if (str_contains($name, 'fios') || str_contains($name, 'atelie') || str_contains($name, 'maos')) {
            return <<<SVG
    <path d="M214 278 C286 156 444 156 518 278" fill="none" stroke="{$palette['dark']}" stroke-width="20" stroke-linecap="round"/>
    <circle cx="238" cy="280" r="28" fill="#f43f2e"/>
    <circle cx="298" cy="220" r="28" fill="{$palette['accent']}"/>
    <circle cx="370" cy="196" r="28" fill="#39a852"/>
    <circle cx="442" cy="220" r="28" fill="#f4e294"/>
    <circle cx="502" cy="280" r="28" fill="#1f6fb2"/>
    <rect x="232" y="326" width="280" height="38" rx="19" fill="#fff8d5" stroke="{$palette['dark']}" stroke-width="10"/>
SVG;
        }

        if (str_contains($name, 'raizes') || str_contains($name, 'terra') || str_contains($name, 'ervas') || str_contains($name, 'cura')) {
            return <<<SVG
    <path d="M366 332 C366 260 368 202 368 154" stroke="{$palette['dark']}" stroke-width="16" stroke-linecap="round"/>
    <path d="M366 250 C294 240 258 196 246 142 C314 146 358 180 366 250 Z" fill="#6aa84f"/>
    <path d="M370 220 C446 202 486 158 504 102 C426 104 384 146 370 220 Z" fill="{$palette['accent']}"/>
    <path d="M366 306 C292 310 240 276 210 222 C286 204 344 232 366 306 Z" fill="#8bcf67"/>
    <rect x="248" y="344" width="252" height="54" rx="22" fill="#fff8d5" stroke="{$palette['dark']}" stroke-width="10"/>
SVG;
        }

        return <<<SVG
    <rect x="218" y="180" width="132" height="122" rx="18" fill="#fff8d5" stroke="{$palette['dark']}" stroke-width="10"/>
    <rect x="384" y="156" width="142" height="150" rx="18" fill="{$palette['accent']}" opacity="0.72" stroke="{$palette['dark']}" stroke-width="10"/>
    <circle cx="284" cy="350" r="34" fill="#f43f2e"/>
    <circle cx="384" cy="350" r="34" fill="#39a852"/>
    <circle cx="484" cy="350" r="34" fill="#f2b31a"/>
SVG;
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

    /**
     * @param array{soft: string, light: string, warm: string, accent: string, dark: string} $palette
     */
    private function patternFor(string $seed, array $palette): string
    {
        $number = abs(crc32($seed));
        $x1 = 206 + ($number % 120);
        $y1 = 176 + (($number >> 3) % 90);
        $x2 = 792 + (($number >> 6) % 100);
        $y2 = 164 + (($number >> 9) % 100);
        $x3 = 274 + (($number >> 12) % 150);
        $y3 = 480 + (($number >> 15) % 80);

        return <<<SVG
  <circle cx="{$x1}" cy="{$y1}" r="28" fill="{$palette['accent']}" opacity="0.30"/>
  <rect x="{$x2}" y="{$y2}" width="76" height="76" rx="20" fill="#ffffff" opacity="0.32"/>
  <circle cx="{$x3}" cy="{$y3}" r="36" fill="{$palette['dark']}" opacity="0.10"/>
SVG;
    }

    private function initials(string $text): string
    {
        $words = collect(explode(' ', Str::ascii($text)))
            ->filter(fn (string $word) => strlen($word) > 2)
            ->take(2)
            ->map(fn (string $word) => strtoupper($word[0]));

        return $words->implode('') ?: 'FL';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
