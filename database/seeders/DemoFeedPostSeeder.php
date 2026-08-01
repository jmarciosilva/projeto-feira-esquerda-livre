<?php

namespace Database\Seeders;

use App\Enums\FeedPostType;
use App\Models\Expositor;
use App\Models\FeedPost;
use Illuminate\Database\Seeder;

class DemoFeedPostSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            FeedPostType::Feira->value => [
                'Hoje foi dia de feira cheia, conversa boa e muita troca entre quem produz e quem compra direto de quem faz.',
                'Registro de mais uma edição com bancas lindas, produção local e aquele clima de encontro popular que fortalece a rede.',
                'A montagem começou cedo por aqui. Cada detalhe da banca foi preparado com cuidado para receber a comunidade.',
            ],
            FeedPostType::Produto->value => [
                'Tem novidade chegando na banca: itens feitos em pequena escala, com produção cuidadosa e preço justo.',
                'Atualizamos nossa vitrine com novas peças e serviços para esta semana. Vale passar na loja e conferir.',
                'Produto novo cadastrado para demonstração da feira. Ótimo para testar carrinho, pedido e checkout.',
            ],
            FeedPostType::Aviso->value => [
                'Aviso importante: nesta semana teremos atendimento especial pelo WhatsApp para tirar dúvidas antes da próxima feira.',
                'Estamos organizando os pedidos da semana. Quem precisar combinar retirada pode chamar pelo contato da loja.',
                'Agenda aberta para conversas, encomendas e parcerias com outros coletivos da comunidade.',
            ],
            FeedPostType::Texto->value => [
                'A economia solidária também acontece nas pequenas escolhas: comprar local, divulgar uma banca e fortalecer quem produz.',
                'Por aqui acreditamos que feira é mais do que venda. É encontro, memória, cuidado, cultura e trabalho digno.',
                'Cada expositor traz uma história. A Comunidade Esquerda Livre existe para essas histórias circularem.',
            ],
        ];

        $expositores = Expositor::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $created = 0;

        foreach ($expositores as $index => $expositor) {
            $types = array_keys($templates);

            for ($i = 0; $i < 2; $i++) {
                $type = $types[($index + $i) % count($types)];
                $content = $templates[$type][($index + $i) % count($templates[$type])];

                FeedPost::updateOrCreate(
                    [
                        'expositor_id' => $expositor->id,
                        'content' => $content,
                    ],
                    [
                        'type' => $type,
                        'images' => null,
                        'is_visible' => true,
                        'reported_count' => 0,
                        'created_at' => now()->subHours(($index * 2) + $i),
                        'updated_at' => now()->subHours(($index * 2) + $i),
                    ]
                );

                $created++;
            }
        }

        $this->command->info("DemoFeedPostSeeder: {$created} publicações de comunidade garantidas.");
    }
}
