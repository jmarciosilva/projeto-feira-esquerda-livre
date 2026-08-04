<?php

namespace Database\Seeders;

use App\Enums\ItemType;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Preenche peso e dimensões padrão nos produtos físicos sem dados logísticos,
 * necessário para cotação de frete real via Melhor Envio.
 */
class ProductLogisticDataSeeder extends Seeder
{
    public function run(): void
    {
        $total = Product::query()
            ->where('item_type', ItemType::Produto->value)
            ->where(function ($query) {
                $query->whereNull('weight')->orWhere('weight', '<=', 0)
                    ->orWhereNull('height')->orWhere('height', '<=', 0)
                    ->orWhereNull('width')->orWhere('width', '<=', 0)
                    ->orWhereNull('length')->orWhere('length', '<=', 0);
            })
            ->update([
                'weight' => 0.5,
                'height' => 15,
                'width'  => 15,
                'length' => 20,
            ]);

        $this->command->info("{$total} produtos físicos atualizados com dados logísticos padrão (peso 0,5kg, 15x15x20cm).");
    }
}
