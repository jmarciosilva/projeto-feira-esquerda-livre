<?php

namespace Database\Seeders;

use App\Models\ContentCategory;
use Illuminate\Database\Seeder;

class ContentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Eixo Produtos
            'Artesanato'             => 'produto',
            'Roupas e Acessórios'    => 'produto',
            'Alimentos e Bebidas'    => 'produto',
            'Bijuterias e Joias'     => 'produto',
            'Decoração'              => 'produto',
            'Livros e Zines'         => 'produto',
            'Arte e Ilustração'      => 'produto',
            'Brinquedos e Jogos'     => 'produto',
            'Plantas e Jardim'       => 'produto',
            'Música e Instrumentos'  => 'produto',
            'Cosméticos e Cuidados'  => 'produto',

            // Eixo Serviços
            'Design e Comunicação'   => 'servico',
            'Aulas e Workshops'      => 'servico',
            'Consultorias'           => 'servico',
            'Fotografia e Audiovisual' => 'servico',

            // Eixo Cuidados & Bem Viver
            'Massagens'              => 'cuidado',
            'Terapias Integrativas'  => 'cuidado',
            'Yoga e Meditação'       => 'cuidado',
            'Astrologia e Tarot'     => 'cuidado',

            // Geral (aparece em todos os eixos)
            'Outros'                 => null,
        ];

        foreach ($categories as $name => $eixo) {
            ContentCategory::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($name)],
                ['name' => $name, 'eixo' => $eixo, 'is_active' => true]
            );
        }
    }
}
