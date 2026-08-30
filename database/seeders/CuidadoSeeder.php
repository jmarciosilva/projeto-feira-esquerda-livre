<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\ContentCategory;
use App\Models\Expositor;
use App\Models\User;
use Database\Seeders\Concerns\SincronizaOfertaDoItem;
use Illuminate\Database\Seeder;

/**
 * Expositores do eixo Cuidado & Bem Viver com usuários lojistas e itens de demonstração.
 *
 * ┌──────────────────────────┬─────────────────────────┬──────────┐
 * │ Loja                     │ E-mail                  │ Senha    │
 * ├──────────────────────────┼─────────────────────────┼──────────┤
 * │ Raiz Cura                │ raiz@teste.com          │ password │
 * │ Corpo Livre              │ corpo@teste.com         │ password │
 * │ Mente Viva               │ mente@teste.com         │ password │
 * │ Ervas do Cerrado         │ ervas@teste.com         │ password │
 * └──────────────────────────┴─────────────────────────┴──────────┘
 */
class CuidadoSeeder extends Seeder
{
    use SincronizaOfertaDoItem;

    public function run(): void
    {
        // Mapa slug → id para lookup rápido
        $cats = ContentCategory::pluck('id', 'slug');

        $expositores = [
            [
                'user'      => ['name' => 'Raiz Cura',        'email' => 'raiz@teste.com'],
                'expositor' => [
                    'name'        => 'Raiz Cura',
                    'description' => 'Práticas integrativas baseadas em medicina popular e plantas medicinais. Atendimentos individuais e coletivos com raizeiras e parteiras de sabedoria ancestral.',
                    'city'        => 'Salvador',
                    'state'       => 'BA',
                    'whatsapp'    => '(71) 98888-0501',
                    'email'       => 'raiz@teste.com',
                    'eixos'       => ['cuidado'],
                    'is_featured' => true,
                    'is_active'   => true,
                    'sort_order'  => 20,
                ],
                'produtos'  => [
                    [
                        'name'          => 'Consulta com Raizeira (1h)',
                        'description'   => 'Conversa individual sobre sintomas e indicação de plantas medicinais, chás e banhos. Prática ancestral de cuidado comunitário.',
                        'price'         => 80.00,
                        'item_type'     => 'cuidado',
                        'price_type'    => 'por_sessao',
                        'modality'      => 'presencial',
                        'duration_min'  => 60,
                        'is_featured'   => true,
                        'sort_order'    => 1,
                        'category_slug' => 'terapias-integrativas',
                    ],
                    [
                        'name'          => 'Kit Plantas Medicinais (12 espécies)',
                        'description'   => 'Mudas de 12 plantas medicinais em vasinhos reciclados: boldo, erva-cidreira, alecrim, babosa, capim-limão e outras. Com guia de uso.',
                        'price'         => 65.00,
                        'item_type'     => 'cuidado',
                        'price_type'    => 'fixo',
                        'modality'      => 'presencial',
                        'is_featured'   => true,
                        'sort_order'    => 2,
                        'category_slug' => 'terapias-integrativas',
                    ],
                    [
                        'name'          => 'Roda de Plantas — Encontro Mensal',
                        'description'   => 'Encontro coletivo mensal para trocar conhecimentos sobre plantas, receitas de chás e práticas de cuidado. Aberto a todas as pessoas.',
                        'price'         => 30.00,
                        'item_type'     => 'cuidado',
                        'price_type'    => 'por_sessao',
                        'modality'      => 'presencial',
                        'duration_min'  => 180,
                        'is_featured'   => false,
                        'sort_order'    => 3,
                        'category_slug' => 'terapias-integrativas',
                    ],
                ],
            ],
            [
                'user'      => ['name' => 'Corpo Livre',      'email' => 'corpo@teste.com'],
                'expositor' => [
                    'name'        => 'Corpo Livre',
                    'description' => 'Massoterapia, yoga popular e práticas de movimento consciente. Cuidado do corpo acessível para trabalhadores, mães e comunidade em geral.',
                    'city'        => 'São Paulo',
                    'state'       => 'SP',
                    'whatsapp'    => '(11) 98888-0602',
                    'email'       => 'corpo@teste.com',
                    'eixos'       => ['cuidado'],
                    'is_featured' => true,
                    'is_active'   => true,
                    'sort_order'  => 21,
                ],
                'produtos'  => [
                    [
                        'name'          => 'Massagem Terapêutica (1h)',
                        'description'   => 'Sessão de massagem relaxante e terapêutica para aliviar tensões musculares. Uso de óleos naturais. Atendimento domiciliar disponível na Grande SP.',
                        'price'         => 120.00,
                        'item_type'     => 'cuidado',
                        'price_type'    => 'por_sessao',
                        'modality'      => 'presencial',
                        'duration_min'  => 60,
                        'is_featured'   => true,
                        'sort_order'    => 1,
                        'category_slug' => 'massagens',
                    ],
                    [
                        'name'          => 'Aula de Yoga Popular (por mês)',
                        'description'   => 'Aulas semanais de yoga acessível, sem jargão esotérico ou elitismo. Foco em respiração, alongamento e presença. Turmas de até 12 pessoas.',
                        'price'         => 80.00,
                        'item_type'     => 'cuidado',
                        'price_type'    => 'fixo',
                        'modality'      => 'presencial',
                        'duration_min'  => 60,
                        'is_featured'   => true,
                        'sort_order'    => 2,
                        'category_slug' => 'yoga-e-meditacao',
                    ],
                    [
                        'name'          => 'Sessão de Meditação Guiada Online (45min)',
                        'description'   => 'Prática de meditação guiada ao vivo via videochamada. Técnicas de atenção plena adaptadas para o cotidiano de quem trabalha muito.',
                        'price'         => 45.00,
                        'item_type'     => 'cuidado',
                        'price_type'    => 'por_sessao',
                        'modality'      => 'online',
                        'duration_min'  => 45,
                        'is_featured'   => false,
                        'sort_order'    => 3,
                        'category_slug' => 'yoga-e-meditacao',
                    ],
                ],
            ],
            [
                'user'      => ['name' => 'Mente Viva',       'email' => 'mente@teste.com'],
                'expositor' => [
                    'name'        => 'Mente Viva',
                    'description' => 'Psicologia comunitária, escuta qualificada e rodas de conversa para saúde mental coletiva. Acreditamos que cuidar da mente é direito de todos.',
                    'city'        => 'Recife',
                    'state'       => 'PE',
                    'whatsapp'    => '(81) 98888-0703',
                    'email'       => 'mente@teste.com',
                    'eixos'       => ['cuidado'],
                    'is_featured' => true,
                    'is_active'   => true,
                    'sort_order'  => 22,
                ],
                'produtos'  => [
                    [
                        'name'          => 'Atendimento Psicológico por Videochamada (50min)',
                        'description'   => 'Sessão individual de psicologia clínica com abordagem humanista. Preço solidário para trabalhadores informais — informe sua renda na marcação.',
                        'price'         => 0,
                        'item_type'     => 'cuidado',
                        'price_type'    => 'sob_consulta',
                        'modality'      => 'online',
                        'duration_min'  => 50,
                        'is_featured'   => true,
                        'sort_order'    => 1,
                        'category_slug' => 'terapias-integrativas',
                    ],
                    [
                        'name'          => 'Roda de Conversa — Saúde Mental no Trabalho',
                        'description'   => 'Encontro mensal em grupo para falar sobre ansiedade, esgotamento e relações de trabalho. Facilitado por psicóloga. Máx. 15 pessoas.',
                        'price'         => 25.00,
                        'item_type'     => 'cuidado',
                        'price_type'    => 'por_sessao',
                        'modality'      => 'presencial',
                        'duration_min'  => 120,
                        'is_featured'   => true,
                        'sort_order'    => 2,
                        'category_slug' => 'terapias-integrativas',
                    ],
                ],
            ],
            [
                'user'      => ['name' => 'Ervas do Cerrado', 'email' => 'ervas@teste.com'],
                'expositor' => [
                    'name'        => 'Ervas do Cerrado',
                    'description' => 'Fitoterápicos artesanais, tinturas e cosméticos naturais feitos com ervas do Cerrado. Produção familiar, extrativismo sustentável e embalagens reutilizáveis.',
                    'city'        => 'Goiânia',
                    'state'       => 'GO',
                    'whatsapp'    => '(62) 98888-0804',
                    'email'       => 'ervas@teste.com',
                    'eixos'       => ['cuidado'],
                    'is_featured' => true,
                    'is_active'   => true,
                    'sort_order'  => 23,
                ],
                'produtos'  => [
                    [
                        'name'          => 'Tintura de Copaíba 30ml',
                        'description'   => 'Extrato de copaíba em álcool de cereais. Indicada para inflamações, gripes e cuidados da pele. Produção artesanal certificada.',
                        'price'         => 38.00,
                        'item_type'     => 'cuidado',
                        'price_type'    => 'fixo',
                        'modality'      => null,
                        'is_featured'   => true,
                        'sort_order'    => 1,
                        'category_slug' => 'terapias-integrativas',
                    ],
                    [
                        'name'          => 'Creme Hidratante de Buriti e Murumuru',
                        'description'   => 'Hidratante corporal feito com manteigas vegetais do Cerrado. Sem parabenos, sem sulfatos. Embalagem em vidro retornável.',
                        'price'         => 55.00,
                        'item_type'     => 'cuidado',
                        'price_type'    => 'fixo',
                        'modality'      => null,
                        'is_featured'   => true,
                        'sort_order'    => 2,
                        'category_slug' => 'terapias-integrativas',
                    ],
                    [
                        'name'          => 'Kit Bem-Estar Cerrado (3 produtos)',
                        'description'   => 'Combo com tintura de copaíba, sabonete de pequi e creme de buriti. Embalado em caixa de papel reciclado. Ótimo presente.',
                        'price'         => 120.00,
                        'item_type'     => 'cuidado',
                        'price_type'    => 'fixo',
                        'modality'      => null,
                        'is_featured'   => true,
                        'sort_order'    => 3,
                        'category_slug' => 'terapias-integrativas',
                    ],
                ],
            ],
        ];

        foreach ($expositores as $dados) {
            $user = User::updateOrCreate(
                ['email' => $dados['user']['email']],
                [
                    'name'      => $dados['user']['name'],
                    'password'  => 'password',
                    'role'      => UserRole::Lojista,
                    'is_active' => true,
                ]
            );

            $expositor = Expositor::updateOrCreate(
                ['name' => $dados['expositor']['name']],
                array_merge($dados['expositor'], ['user_id' => $user->id])
            );

            foreach ($dados['produtos'] as $prod) {
                $categorySlug = $prod['category_slug'] ?? null;
                unset($prod['category_slug']);

                $this->semearItemComOferta(
                    ['name' => $prod['name']],
                    array_merge($prod, [
                        'expositor_id' => $expositor->id,
                        'is_active'    => true,
                        'category_id'  => $categorySlug ? ($cats[$categorySlug] ?? null) : null,
                    ]),
                    $expositor->id,
                );
            }

            $this->command->line("  ✓ {$dados['expositor']['name']} → {$dados['user']['email']}");
        }

        $this->command->info('CuidadoSeeder: 4 expositores e ' . collect($expositores)->sum(fn ($e) => count($e['produtos'])) . ' itens criados.');
    }
}
