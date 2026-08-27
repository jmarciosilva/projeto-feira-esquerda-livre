<?php

namespace Database\Seeders;

use App\Enums\ItemType;
use App\Models\Product;
use App\Models\ProductOffer;
use Illuminate\Database\Seeder;

/**
 * Preenche peso e dimensões padrão nos produtos físicos sem dados logísticos,
 * necessário para cotação de frete real via Melhor Envio.
 */
class ProductLogisticDataSeeder extends Seeder
{
    public function run(): void
    {
        $padrao = [
            'weight' => 0.5,
            'height' => 15,
            'width' => 15,
            'length' => 20,
        ];

        $semLogistica = function ($query) {
            $query->whereNull('weight')->orWhere('weight', '<=', 0)
                ->orWhereNull('height')->orWhere('height', '<=', 0)
                ->orWhereNull('width')->orWhere('width', '<=', 0)
                ->orWhereNull('length')->orWhere('length', '<=', 0);
        };

        // Quem despacha e a oferta: e la que o frete busca peso e dimensoes.
        $total = ProductOffer::query()
            ->whereHas('product', fn ($q) => $q->where('item_type', ItemType::Produto->value))
            ->where($semLogistica)
            ->update($padrao);

        // Espelho legado (divida D-1): `products` nao pode contradizer a oferta.
        Product::query()
            ->where('item_type', ItemType::Produto->value)
            ->where($semLogistica)
            ->update($padrao);

        $this->command->info("{$total} produtos físicos atualizados com dados logísticos padrão (peso 0,5kg, 15x15x20cm).");
    }
}
