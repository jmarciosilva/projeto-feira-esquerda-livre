<?php

namespace Database\Seeders;

use App\Models\Expositor;
use Illuminate\Database\Seeder;

class ExpositorSeeder extends Seeder
{
    public function run(): void
    {
        $expositores = [
            [
                'name'        => 'Ateliê das Mãos',
                'description' => 'Artesanato têxtil feito à mão com técnicas tradicionais brasileiras. Bolsas, almofadas, tapetes e muito mais.',
                'city'        => 'São Paulo',
                'state'       => 'SP',
                'whatsapp'    => '(11) 99999-0001',
                'email'       => 'atelie@exemplo.com',
                'is_featured' => true,
                'is_active'   => true,
                'sort_order'  => 1,
            ],
            [
                'name'        => 'Cerâmica Viva',
                'description' => 'Peças únicas de cerâmica artesanal. Utensílios, esculturas e decoração com a alma do barro nordestino.',
                'city'        => 'Fortaleza',
                'state'       => 'CE',
                'whatsapp'    => '(85) 99999-0002',
                'email'       => 'ceramica@exemplo.com',
                'is_featured' => true,
                'is_active'   => true,
                'sort_order'  => 2,
            ],
            [
                'name'        => 'Mel do Cerrado',
                'description' => 'Produtos agroecológicos do Cerrado. Mel puro, própolis, pólen e cosméticos naturais de apicultura familiar.',
                'city'        => 'Brasília',
                'state'       => 'DF',
                'whatsapp'    => '(61) 99999-0003',
                'email'       => 'mel@exemplo.com',
                'is_featured' => true,
                'is_active'   => true,
                'sort_order'  => 3,
            ],
            [
                'name'        => 'Raízes da Terra',
                'description' => 'Gastronomia popular com ingredientes locais e orgânicos. Compotas, conservas, temperos e quitutes artesanais.',
                'city'        => 'Minas Gerais',
                'state'       => 'MG',
                'whatsapp'    => '(31) 99999-0004',
                'email'       => 'raizes@exemplo.com',
                'is_featured' => true,
                'is_active'   => true,
                'sort_order'  => 4,
            ],
            [
                'name'        => 'Pincéis Livres',
                'description' => 'Arte visual, gravuras, pinturas e ilustrações autorais. Obra de artistas independentes do movimento cultural popular.',
                'city'        => 'Rio de Janeiro',
                'state'       => 'RJ',
                'whatsapp'    => '(21) 99999-0005',
                'email'       => 'pinceis@exemplo.com',
                'is_featured' => true,
                'is_active'   => true,
                'sort_order'  => 5,
            ],
            [
                'name'        => 'Fios e Saberes',
                'description' => 'Bijuterias, colares e acessórios feitos com sementes, fibras naturais e materiais reciclados.',
                'city'        => 'Salvador',
                'state'       => 'BA',
                'whatsapp'    => '(71) 99999-0006',
                'email'       => 'fios@exemplo.com',
                'is_featured' => true,
                'is_active'   => true,
                'sort_order'  => 6,
            ],
        ];

        foreach ($expositores as $expositor) {
            Expositor::updateOrCreate(
                ['name' => $expositor['name']],
                $expositor
            );
        }

        $this->command->info('6 expositores criados.');
    }
}
