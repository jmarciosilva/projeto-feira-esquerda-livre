<?php

namespace Database\Seeders;

use App\Models\ContentCategory;
use App\Models\Expositor;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoProductSeeder extends Seeder
{
    private const DEMO_PRICE = 0.01;

    public function run(): void
    {
        $categories = ContentCategory::pluck('id', 'slug');

        $templates = [
            'produto' => [
                ['name' => 'Kit Especial da Feira', 'category' => 'artesanato', 'description' => 'Seleção demonstrativa de itens autorais para apresentação da loja na feira.'],
                ['name' => 'Produto Artesanal de Demonstração', 'category' => 'decoracao', 'description' => 'Item criado para demonstrar o fluxo de compra, carrinho e checkout da plataforma.'],
                ['name' => 'Presente Popular da Feira', 'category' => 'artesanato', 'description' => 'Produto simbólico para vitrines, testes de pedido e validação de pagamento em ambiente de demonstração.'],
            ],
            'servico' => [
                ['name' => 'Atendimento de Demonstração', 'category' => 'consultorias', 'description' => 'Serviço demonstrativo para apresentar agenda, contratação e pagamento pela plataforma.'],
                ['name' => 'Oficina Popular de Demonstração', 'category' => 'aulas-e-workshops', 'description' => 'Atividade criada para validar a compra de serviços e o contato com o expositor.'],
                ['name' => 'Consultoria Solidária Demo', 'category' => 'consultorias', 'description' => 'Serviço simbólico para testes de pedido, checkout e confirmação de compra.'],
            ],
            'cuidado' => [
                ['name' => 'Sessão de Cuidado Demo', 'category' => 'terapias-integrativas', 'description' => 'Atendimento demonstrativo de cuidado e bem viver para validação do fluxo de compra.'],
                ['name' => 'Prática Integrativa de Demonstração', 'category' => 'terapias-integrativas', 'description' => 'Item de demonstração para apresentar serviços de cuidado no marketplace.'],
                ['name' => 'Experiência Bem Viver Demo', 'category' => 'massagens', 'description' => 'Oferta simbólica para testes de carrinho, pedido e pagamento.'],
            ],
        ];

        $created = 0;

        Expositor::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->each(function (Expositor $expositor) use ($templates, $categories, &$created) {
                $eixos = collect($expositor->eixos ?: ['produto'])
                    ->filter(fn ($eixo) => in_array($eixo, ['produto', 'servico', 'cuidado'], true))
                    ->values();

                if ($eixos->isEmpty()) {
                    $eixos = collect(['produto']);
                }

                foreach ($eixos as $eixo) {
                    foreach ($templates[$eixo] as $index => $template) {
                        $name = "{$template['name']} - {$expositor->name}";
                        $slug = Str::slug("demo {$eixo} {$expositor->slug} {$template['name']}");

                        Product::updateOrCreate(
                            ['slug' => $slug],
                            [
                                'expositor_id' => $expositor->id,
                                'category_id' => $categories[$template['category']] ?? null,
                                'item_type' => $eixo,
                                'name' => $name,
                                'description' => $template['description'].' Expositor: '.$expositor->name.'.',
                                'image_path' => null,
                                'images' => null,
                                'price' => self::DEMO_PRICE,
                                'weight' => $eixo === 'produto' ? 0.300 : null,
                                'height' => $eixo === 'produto' ? 5 : null,
                                'width' => $eixo === 'produto' ? 15 : null,
                                'length' => $eixo === 'produto' ? 20 : null,
                                'price_type' => 'fixo',
                                'modality' => $eixo === 'produto' ? null : 'presencial',
                                'duration_min' => $eixo === 'produto' ? null : 60,
                                'has_stock' => true,
                                'stock_quantity' => 100,
                                'is_featured' => $index < 2,
                                'is_active' => true,
                                'is_digital' => $eixo !== 'produto',
                                'sort_order' => 100 + $index,
                            ]
                        );

                        $created++;
                    }
                }
            });

        Product::query()->update([
            'price' => self::DEMO_PRICE,
            'price_type' => 'fixo',
            'is_active' => true,
        ]);

        $this->command->info("DemoProductSeeder: {$created} itens de demonstração garantidos. Todos os produtos ficaram com preço R$ 0,01.");
    }
}
