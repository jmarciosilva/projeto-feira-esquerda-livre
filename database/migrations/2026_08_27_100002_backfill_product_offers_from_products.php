<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * CAT-DOM-01C — cada produto existente vira exatamente uma oferta.
 *
 * A medição da CAT-DOM-01A decidiu esta migration antes de ela ser escrita: dos
 * 75 itens do banco real, **todos** têm expositor e preço, e **nenhum** nome se
 * repete entre lojas. Não há nada a fundir, nada a escolher por heurística e
 * nada a descartar — a transformação correta é 1 produto → 1 oferta.
 *
 * Fundir "Tapete de crochê" do artesão A com o do artesão B seria destruir
 * informação a pretexto de simplificar o schema, e o §9 da decisão proíbe
 * exatamente isso: em artesanato, dois itens de mesmo nome raramente são a
 * mesma peça. A fase entrega a **capacidade** de um produto ter várias ofertas;
 * quem decide que dois registros são o mesmo item é curadoria humana, em fase
 * futura.
 *
 * Puramente aditiva: nada é apagado nem alterado em `products`. O `down()`
 * apenas esvazia a tabela nova.
 *
 * `whereNotNull('expositor_id')` cobre o caso do §19.5-C — produto órfão de um
 * expositor já excluído. Órfão não gera oferta: ele permanece no catálogo, sem
 * quem o venda, que é o estado correto para ele.
 */
return new class extends Migration
{
    public function up(): void
    {
        $agora = now();

        DB::table('products')
            ->whereNotNull('expositor_id')
            ->orderBy('id')
            ->chunkById(200, function ($produtos) use ($agora) {
                $ofertas = [];

                foreach ($produtos as $produto) {
                    $ofertas[] = [
                        'product_id' => $produto->id,
                        'expositor_id' => $produto->expositor_id,
                        'price' => $produto->price,
                        'price_type' => $produto->price_type,
                        'modality' => $produto->modality,
                        'duration_min' => $produto->duration_min,
                        'weight' => $produto->weight,
                        'height' => $produto->height,
                        'width' => $produto->width,
                        'length' => $produto->length,
                        'has_stock' => $produto->has_stock,
                        'stock_quantity' => $produto->stock_quantity,
                        'is_active' => $produto->is_active,
                        'is_featured' => $produto->is_featured,
                        'sort_order' => $produto->sort_order,
                        'created_at' => $produto->created_at ?? $agora,
                        'updated_at' => $agora,
                    ];
                }

                // insertOrIgnore respeita a unique (product_id, expositor_id):
                // rodar de novo não duplica nem sobrescreve oferta já editada.
                DB::table('product_offers')->insertOrIgnore($ofertas);
            });
    }

    public function down(): void
    {
        DB::table('product_offers')->delete();
    }
};
