<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\ContentCategory;
use App\Models\Expositor;
use App\Models\User;
use Database\Seeders\Concerns\SincronizaOfertaDoItem;
use Illuminate\Database\Seeder;

/**
 * Expositores do eixo Serviços com usuários lojistas e itens de demonstração.
 *
 * ┌──────────────────────────┬────────────────────────┬──────────┐
 * │ Loja                     │ E-mail                 │ Senha    │
 * ├──────────────────────────┼────────────────────────┼──────────┤
 * │ Costura Coletiva         │ costura@teste.com      │ password │
 * │ Foto Livre               │ foto@teste.com         │ password │
 * │ Conserto & Cia           │ conserto@teste.com     │ password │
 * │ Tecnologia Solidária     │ tech@teste.com         │ password │
 * └──────────────────────────┴────────────────────────┴──────────┘
 */
class ServicoSeeder extends Seeder
{
    use SincronizaOfertaDoItem;

    public function run(): void
    {
        // Mapa slug → id para lookup rápido
        $cats = ContentCategory::pluck('id', 'slug');

        $expositores = [
            [
                'user'      => ['name' => 'Costura Coletiva',     'email' => 'costura@teste.com'],
                'expositor' => [
                    'name'        => 'Costura Coletiva',
                    'description' => 'Coletivo de costureiras que oferece ajustes, reformas e customização de roupas. Valorizamos a moda sustentável e o reaproveitamento de tecidos.',
                    'city'        => 'São Paulo',
                    'state'       => 'SP',
                    'whatsapp'    => '(11) 98888-0101',
                    'email'       => 'costura@teste.com',
                    'eixos'       => ['servico'],
                    'is_featured' => true,
                    'is_active'   => true,
                    'sort_order'  => 10,
                ],
                'produtos'  => [
                    [
                        'name'          => 'Ajuste e Reforma de Roupa',
                        'description'   => 'Bainhas, ajuste de caimento, troca de zíper e pequenos reparos. Entrega em até 5 dias úteis.',
                        'price'         => 40.00,
                        'item_type'     => 'servico',
                        'price_type'    => 'fixo',
                        'modality'      => 'presencial',
                        'is_featured'   => true,
                        'sort_order'    => 1,
                        'category_slug' => 'consultorias',
                    ],
                    [
                        'name'          => 'Customização Completa de Peça',
                        'description'   => 'Transformamos uma peça de roupa com bordado, patch, tingimento natural ou recortes criativos. Consulte o ateliê para orçamento.',
                        'price'         => 0,
                        'item_type'     => 'servico',
                        'price_type'    => 'sob_consulta',
                        'modality'      => 'presencial',
                        'is_featured'   => true,
                        'sort_order'    => 2,
                        'category_slug' => 'consultorias',
                    ],
                    [
                        'name'          => 'Oficina de Remendo Visível (Kintsugi Têxtil)',
                        'description'   => 'Aprenda a remendar roupas com a técnica japonesa Sashiko. Turmas de até 8 pessoas, duração 3h.',
                        'price'         => 95.00,
                        'item_type'     => 'servico',
                        'price_type'    => 'por_sessao',
                        'modality'      => 'presencial',
                        'duration_min'  => 180,
                        'is_featured'   => false,
                        'sort_order'    => 3,
                        'category_slug' => 'aulas-e-workshops',
                    ],
                ],
            ],
            [
                'user'      => ['name' => 'Foto Livre',           'email' => 'foto@teste.com'],
                'expositor' => [
                    'name'        => 'Foto Livre',
                    'description' => 'Fotografia documental, social e de eventos culturais. Registramos lutas, festas e memórias de comunidades com respeito e sensibilidade.',
                    'city'        => 'Rio de Janeiro',
                    'state'       => 'RJ',
                    'whatsapp'    => '(21) 98888-0202',
                    'email'       => 'foto@teste.com',
                    'eixos'       => ['servico'],
                    'is_featured' => true,
                    'is_active'   => true,
                    'sort_order'  => 11,
                ],
                'produtos'  => [
                    [
                        'name'          => 'Ensaio Fotográfico (2h)',
                        'description'   => 'Sessão de 2 horas com entrega de 30 fotos editadas em alta resolução. Locação combinada com o fotógrafo.',
                        'price'         => 280.00,
                        'item_type'     => 'servico',
                        'price_type'    => 'por_sessao',
                        'modality'      => 'presencial',
                        'duration_min'  => 120,
                        'is_featured'   => true,
                        'sort_order'    => 1,
                        'category_slug' => 'fotografia-e-audiovisual',
                    ],
                    [
                        'name'          => 'Cobertura de Evento Cultural',
                        'description'   => 'Cobertura fotográfica completa de shows, feiras, saraus e eventos comunitários. Pacote por hora.',
                        'price'         => 180.00,
                        'item_type'     => 'servico',
                        'price_type'    => 'por_hora',
                        'modality'      => 'presencial',
                        'is_featured'   => true,
                        'sort_order'    => 2,
                        'category_slug' => 'fotografia-e-audiovisual',
                    ],
                    [
                        'name'          => 'Edição de Fotos — Pacote 20 imagens',
                        'description'   => 'Edição profissional de 20 fotografias com tratamento de cor, contraste e luz. Entrega em até 48h.',
                        'price'         => 120.00,
                        'item_type'     => 'servico',
                        'price_type'    => 'fixo',
                        'modality'      => 'online',
                        'is_featured'   => false,
                        'sort_order'    => 3,
                        'category_slug' => 'fotografia-e-audiovisual',
                    ],
                ],
            ],
            [
                'user'      => ['name' => 'Conserto & Cia',       'email' => 'conserto@teste.com'],
                'expositor' => [
                    'name'        => 'Conserto & Cia',
                    'description' => 'Reparos domésticos, marcenaria básica e manutenção elétrica residencial com preço justo. Profissionais autônomos organizados em rede solidária.',
                    'city'        => 'Belo Horizonte',
                    'state'       => 'MG',
                    'whatsapp'    => '(31) 98888-0303',
                    'email'       => 'conserto@teste.com',
                    'eixos'       => ['servico'],
                    'is_featured' => true,
                    'is_active'   => true,
                    'sort_order'  => 12,
                ],
                'produtos'  => [
                    [
                        'name'          => 'Reparo Elétrico Residencial (por hora)',
                        'description'   => 'Troca de tomadas, interruptores, disjuntores e pequenos reparos na instalação elétrica. Deslocamento incluso na Grande BH.',
                        'price'         => 85.00,
                        'item_type'     => 'servico',
                        'price_type'    => 'por_hora',
                        'modality'      => 'presencial',
                        'is_featured'   => true,
                        'sort_order'    => 1,
                        'category_slug' => 'consultorias',
                    ],
                    [
                        'name'          => 'Montagem de Móveis',
                        'description'   => 'Montagem de móveis de MDF, kits de quarto, cozinha e escritório. Preço por peça — consulte.',
                        'price'         => 0,
                        'item_type'     => 'servico',
                        'price_type'    => 'sob_consulta',
                        'modality'      => 'presencial',
                        'is_featured'   => true,
                        'sort_order'    => 2,
                        'category_slug' => 'consultorias',
                    ],
                ],
            ],
            [
                'user'      => ['name' => 'Tecnologia Solidária', 'email' => 'tech@teste.com'],
                'expositor' => [
                    'name'        => 'Tecnologia Solidária',
                    'description' => 'Suporte técnico, manutenção de computadores e smartphones e cursos de informática básica para comunidades e trabalhadores autônomos.',
                    'city'        => 'Porto Alegre',
                    'state'       => 'RS',
                    'whatsapp'    => '(51) 98888-0404',
                    'email'       => 'tech@teste.com',
                    'eixos'       => ['servico'],
                    'is_featured' => true,
                    'is_active'   => true,
                    'sort_order'  => 13,
                ],
                'produtos'  => [
                    [
                        'name'          => 'Manutenção de Computador (formatação + antivírus)',
                        'description'   => 'Formatação, reinstalação do sistema operacional, configuração de antivírus e backup de dados. Atendimento presencial ou retirada.',
                        'price'         => 120.00,
                        'item_type'     => 'servico',
                        'price_type'    => 'fixo',
                        'modality'      => 'presencial',
                        'is_featured'   => true,
                        'sort_order'    => 1,
                        'category_slug' => 'consultorias',
                    ],
                    [
                        'name'          => 'Curso de Informática Básica (4 aulas)',
                        'description'   => 'Aprenda a usar computador, internet, e-mail e aplicativos do dia a dia. Turmas presenciais de 4 encontros de 2h cada.',
                        'price'         => 160.00,
                        'item_type'     => 'servico',
                        'price_type'    => 'fixo',
                        'modality'      => 'presencial',
                        'duration_min'  => 120,
                        'is_featured'   => true,
                        'sort_order'    => 2,
                        'category_slug' => 'aulas-e-workshops',
                    ],
                    [
                        'name'          => 'Suporte Remoto (por hora)',
                        'description'   => 'Ajuda técnica via acesso remoto para configurações, erros e dúvidas de software. Agendamento via WhatsApp.',
                        'price'         => 60.00,
                        'item_type'     => 'servico',
                        'price_type'    => 'por_hora',
                        'modality'      => 'online',
                        'is_featured'   => false,
                        'sort_order'    => 3,
                        'category_slug' => 'consultorias',
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

        $this->command->info('ServicoSeeder: 4 expositores e ' . collect($expositores)->sum(fn ($e) => count($e['produtos'])) . ' serviços criados.');
    }
}
