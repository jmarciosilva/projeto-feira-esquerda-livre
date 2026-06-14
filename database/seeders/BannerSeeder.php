<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            [
                'title'       => 'Feira Esquerda Livre',
                'subtitle'    => 'O maior movimento de economia solidária do Brasil. Artesanato, cultura e resistência em um só lugar.',
                'button_text' => 'Conheça a Feira',
                'button_link' => '#quem-somos',
                'sort_order'  => 1,
                'is_active'   => true,
            ],
            [
                'title'       => 'Próximas Feiras pelo Brasil',
                'subtitle'    => 'São Paulo, Rio de Janeiro, Belo Horizonte e muito mais. Venha celebrar a cultura popular!',
                'button_text' => 'Ver Agenda',
                'button_link' => '#agenda',
                'sort_order'  => 2,
                'is_active'   => true,
            ],
            [
                'title'       => 'Seja um Expositor',
                'subtitle'    => 'Conecte seu negócio a milhares de pessoas. Venda seus produtos e faça parte deste movimento.',
                'button_text' => 'Quero Participar',
                'button_link' => '#expositores-cta',
                'sort_order'  => 3,
                'is_active'   => true,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::updateOrCreate(
                ['title' => $banner['title']],
                $banner
            );
        }

        $this->command->info('3 banners criados.');
    }
}
