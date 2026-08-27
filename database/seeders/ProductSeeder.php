<?php

namespace Database\Seeders;

use App\Models\Expositor;
use App\Models\Product;
use Database\Seeders\Concerns\SincronizaOfertaDoItem;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    use SincronizaOfertaDoItem;

    public function run(): void
    {
        $expositores = Expositor::all()->keyBy('name');

        $products = [
            [
                'expositor'   => 'Ateliê das Mãos',
                'name'        => 'Bolsa Tecida Artesanal',
                'description' => 'Bolsa confeccionada à mão com técnica de macramê, em algodão cru. Resistente e única.',
                'price'       => 89.90,
                'is_featured' => true,
                'sort_order'  => 1,
            ],
            [
                'expositor'   => 'Ateliê das Mãos',
                'name'        => 'Almofada Bordada',
                'description' => 'Almofada com bordado manual de flores silvestres brasileiras. 45x45 cm, enchimento incluso.',
                'price'       => 65.00,
                'is_featured' => true,
                'sort_order'  => 2,
            ],
            [
                'expositor'   => 'Cerâmica Viva',
                'name'        => 'Vaso de Cerâmica Esmaltado',
                'description' => 'Vaso artesanal em cerâmica com esmalte natural. Peça única, ideal para decoração ou cultivo.',
                'price'       => 120.00,
                'is_featured' => true,
                'sort_order'  => 3,
            ],
            [
                'expositor'   => 'Cerâmica Viva',
                'name'        => 'Tigela de Barro Nordestina',
                'description' => 'Tigela em barro vermelho com padrão geométrico inspirado na cerâmica marajoara.',
                'price'       => 45.00,
                'is_featured' => true,
                'sort_order'  => 4,
            ],
            [
                'expositor'   => 'Mel do Cerrado',
                'name'        => 'Mel Puro do Cerrado 500g',
                'description' => 'Mel 100% natural de apicultura familiar do Cerrado goiano. Sem aditivos, sem aquecimento.',
                'price'       => 35.00,
                'is_featured' => true,
                'sort_order'  => 5,
            ],
            [
                'expositor'   => 'Mel do Cerrado',
                'name'        => 'Kit Própolis + Pólen',
                'description' => 'Combo com extrato de própolis 30ml e pólen apícola 100g. Rico em antioxidantes naturais.',
                'price'       => 58.00,
                'is_featured' => true,
                'sort_order'  => 6,
            ],
            [
                'expositor'   => 'Raízes da Terra',
                'name'        => 'Geleia de Jabuticaba Artesanal',
                'description' => 'Geleia feita com jabuticabas colhidas em pequena propriedade familiar. Sem conservantes.',
                'price'       => 22.00,
                'is_featured' => true,
                'sort_order'  => 7,
            ],
            [
                'expositor'   => 'Raízes da Terra',
                'name'        => 'Kit Temperos Orgânicos',
                'description' => 'Conjunto com 5 temperos orgânicos desidratados: alecrim, tomilho, manjericão, orégano e sálvia.',
                'price'       => 48.00,
                'is_featured' => true,
                'sort_order'  => 8,
            ],
            [
                'expositor'   => 'Pincéis Livres',
                'name'        => 'Gravura em Xilogravura A4',
                'description' => 'Impressão artesanal em xilogravura sobre papel algodão, assinada pelo artista. Edição limitada.',
                'price'       => 75.00,
                'is_featured' => true,
                'sort_order'  => 9,
            ],
            [
                'expositor'   => 'Fios e Saberes',
                'name'        => 'Colar de Sementes Amazônicas',
                'description' => 'Colar longo com sementes nativas da Amazônia, coletadas de forma sustentável. Peça única.',
                'price'       => 42.00,
                'is_featured' => true,
                'sort_order'  => 10,
            ],
        ];

        foreach ($products as $data) {
            $expositorName = $data['expositor'];
            unset($data['expositor']);

            $expositor = $expositores->get($expositorName);
            if (! $expositor) {
                continue;
            }

            $data['expositor_id'] = $expositor->id;
            $data['is_active']    = true;

            $product = Product::updateOrCreate(
                ['name' => $data['name']],
                $data
            );

            $this->sincronizarOferta($product, $expositor->id);
        }

        $this->command->info('10 produtos criados.');
    }
}
