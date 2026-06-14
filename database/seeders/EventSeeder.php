<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'title'       => 'Feira Esquerda Livre — São Paulo',
                'slug'        => 'feira-esquerda-livre-sao-paulo',
                'description' => "A maior edição paulistana da Feira Esquerda Livre chega ao Parque do Ibirapuera com uma programação incrível para toda a família!

São mais de 80 expositores reunidos em um espaço a céu aberto, trazendo o melhor do artesanato brasileiro, gastronomia popular, moda autoral e arte visual. Tudo produzido com trabalho justo, sem atravessadores, direto de quem faz.

O que você vai encontrar:
• Artesanato em cerâmica, macramê, couro vegano e tear manual
• Gastronomia: acarajé, tapioca, pães artesanais, kombucha e doces caseiros
• Moda autoral e roupas upcycling feitas de materiais reciclados
• Livros, zines e publicações independentes
• Arte urbana: pinturas, gravuras e fotografia
• Plantas e mudas de espécies nativas

Programação Cultural:
- 10h às 12h: Roda de samba com o grupo Raízes do Samba
- 13h às 14h: Apresentação de capoeira do Grupo Axé Capoeira
- 14h30 às 16h: Show de MPB com Cláudia Ventura
- 16h30 às 18h: Encerramento com percussão afro-brasileira

Entrada gratuita. Crianças e animais de estimação são bem-vindos.

O evento faz parte de um movimento nacional de fortalecimento da economia solidária e da cultura popular brasileira. Venha fazer parte!",
                'city'                   => 'São Paulo',
                'state'                  => 'SP',
                'address'                => 'Parque do Ibirapuera — Portão 10, Av. Pedro Álvares Cabral, s/n',
                'start_date'             => now()->addDays(15)->setTime(9, 0),
                'end_date'               => now()->addDays(15)->setTime(18, 0),
                'registration_url'       => null,
                'capacidade_expositores' => 80,
                'vagas_restantes'        => 23,
                'is_active'              => true,
                'is_featured'            => true,
            ],
            [
                'title'       => 'Feira Esquerda Livre — Rio de Janeiro',
                'slug'        => 'feira-esquerda-livre-rio-de-janeiro',
                'description' => "No Rio de Janeiro, a Feira Esquerda Livre chega ao icônico Parque Lage com a energia e a alegria que só o povo fluminense sabe oferecer!

Com mais de 60 expositores cuidadosamente selecionados, a edição carioca celebra a diversidade cultural do Brasil, com especial atenção à arte popular afro-brasileira e à produção artesanal do estado do Rio.

O que você vai encontrar:
• Artesanato carioca: bijuterias, bonecas Abayomi, bolsas de palha e crochê
• Culinária baiana e carioca: acarajé, coxinha de jaca, brigadeiro gourmet e açaí
• Arte de rua: telas, grafite em miniatura e lambe-lambe
• Moda praia sustentável: biquínis de pet reciclado e saídas de tecido natural
• Plantas raras e terrários artesanais
• Cosméticos naturais: sabonetes, óleos e velas aromáticas

Programação Cultural:
- 9h30: Abertura com roda de choro ao vivo
- 11h: Oficina de pintura para crianças (gratuita, até 12 anos)
- 13h: Show de bossa nova no jardim
- 15h: Apresentação de dança afro com o grupo Odara
- 17h: Encerramento com samba de raiz

O Parque Lage oferece estacionamento gratuito e fácil acesso de transporte público. Traga sua família, seus amigos e sua alegria!

Entrada gratuita. Aceita-se alimentos não perecíveis para doação.",
                'city'                   => 'Rio de Janeiro',
                'state'                  => 'RJ',
                'address'                => 'Parque Lage — Rua Jardim Botânico, 414, Jardim Botânico',
                'start_date'             => now()->addDays(22)->setTime(9, 0),
                'end_date'               => now()->addDays(22)->setTime(17, 0),
                'registration_url'       => null,
                'capacidade_expositores' => 60,
                'vagas_restantes'        => 8,
                'is_active'              => true,
                'is_featured'            => true,
            ],
            [
                'title'       => 'Feira Esquerda Livre — Belo Horizonte',
                'slug'        => 'feira-esquerda-livre-belo-horizonte',
                'description' => "Belo Horizonte recebe a Feira Esquerda Livre na histórica Praça da Liberdade! Uma tarde especial de trocas, aprendizado e resistência popular no coração de Minas Gerais.

A edição mineira celebra a riqueza do artesanato e da cultura popular do interior, com expositores vindos de todo o estado — de Ouro Preto a Montes Claros, do Vale do Jequitinhonha à capital.

O que você vai encontrar:
• Cerâmica do Vale do Jequitinhonha: bonecas e utensílios em barro
• Doces e quitandas mineiras: broa de fubá, pão de queijo, biscoito de polvilho
• Cachaças artesanais e licores de frutas do Cerrado
• Tapetes e bordados do interior mineiro
• Madeira entalhada: esculturas e objetos decorativos
• Ervas medicinais e plantas do Cerrado
• Queijos artesanais de leite cru (certificados)

Programação Cultural:
- 10h: Abertura com viola caipira e moda de viola
- 11h30: Roda de conversa: \"Economia solidária no interior de Minas\"
- 13h30: Show com músicos locais — Música Mineira ao Vivo
- 15h: Oficina de barro para crianças e adultos
- 17h30: Encerramento com coral popular

A praça tem amplo espaço verde, com sombra e conforto para toda a família. Traga cadeiras e piquenique — é um dia inteiro de celebração!

Entrada totalmente gratuita.",
                'city'                   => 'Belo Horizonte',
                'state'                  => 'MG',
                'address'                => 'Praça da Liberdade, s/n — Funcionários',
                'start_date'             => now()->addDays(35)->setTime(10, 0),
                'end_date'               => now()->addDays(35)->setTime(18, 0),
                'registration_url'       => null,
                'capacidade_expositores' => 50,
                'vagas_restantes'        => 31,
                'is_active'              => true,
                'is_featured'            => false,
            ],
            [
                'title'       => 'Feira Esquerda Livre — Porto Alegre',
                'slug'        => 'feira-esquerda-livre-porto-alegre',
                'description' => "A Feira Esquerda Livre chega ao Sul do Brasil para sua edição gaúcha no Parque Farroupilha — a famosa Redenção, um dos parques mais amados de Porto Alegre!

A edição gaúcha une o melhor da cultura e do artesanato da Região Sul com a força dos movimentos populares locais. Uma tarde de resistência, beleza e economia solidária às margens do Guaíba.

O que você vai encontrar:
• Couro artesanal gaúcho: cintos, bolsas e acessórios trabalhados à mão
• Gastronomia sulina: churrasco vegetariano, chimia artesanal, erva-mate e quentão
• Tricô e crochê: roupas de lã e agasalhos artesanais
• Instrumentos musicais artesanais: violão, gaita e percussão
• Vinhos e sucos de uva orgânicos da Serra Gaúcha
• Mel e produtos apícolas do interior do RS
• Arte e fotografia com temáticas regionais

Programação Cultural:
- 9h: Abertura com gaita e acordeom — músicas tradicionais gaúchas
- 11h: Roda de chimarrão com produtores de erva-mate orgânica
- 13h: Show de música popular gaúcha
- 15h: Apresentação de dança de salão
- 16h30: Roda de samba com convidados especiais

O Parque Farroupilha é o pulmão verde de Porto Alegre. Venha de bicicleta, de metrô ou a pé — o acesso é fácil de qualquer ponto da cidade!

Entrada gratuita. Evento pet-friendly.",
                'city'                   => 'Porto Alegre',
                'state'                  => 'RS',
                'address'                => 'Parque Farroupilha (Redenção) — Av. José Bonifácio, s/n',
                'start_date'             => now()->addDays(50)->setTime(9, 0),
                'end_date'               => now()->addDays(50)->setTime(17, 0),
                'registration_url'       => null,
                'capacidade_expositores' => 45,
                'vagas_restantes'        => 45,
                'is_active'              => true,
                'is_featured'            => false,
            ],
            [
                'title'       => 'Feira Esquerda Livre — Salvador',
                'slug'        => 'feira-esquerda-livre-salvador',
                'description' => "A Bahia pulsa! A Feira Esquerda Livre chega a Salvador com axé, artesanato baiano e a vibrante energia da cultura afro-brasileira no coração histórico do Pelourinho!

A edição baiana é uma das mais aguardadas do ano, celebrando a riqueza cultural e artística da Bahia, berço do samba, do candomblé e de tantas expressões da alma brasileira. Mais de 70 expositores confirmados!

O que você vai encontrar:
• Artesanato afro-brasileiro: colares de orixás, estatuetas, bordados e tecidos africanos
• Gastronomia baiana: acarajé, vatapá, caruru, cocada e moqueca vegetariana
• Moda afrocentrada: turbantes, roupas com estampas africanas e acessórios
• Produtos de beleza afro: manteigas, óleos naturais e cosméticos veganos
• Instrumentos de percussão: atabaques, berimbaus e agogôs artesanais
• Renda de bilro e bordado do interior baiano
• Livros, zines e publicações sobre cultura africana e afro-brasileira

Programação Cultural:
- 9h: Abertura com roda de capoeira — Mestre Cobra Mansa
- 10h30: Apresentação de Maculelê
- 12h: Show de axé music com artistas locais
- 14h: Cortejo de Afoxé pelas ruas do Pelourinho
- 16h: Show de samba reggae — grupos Olodum e convidados
- 17h30: Encerramento com roda de samba duro

O Pelourinho é Patrimônio Mundial da UNESCO — um cenário histórico e emocionante para esta edição especial da Feira Esquerda Livre.

Entrada gratuita. Venha celebrar a resistência e a beleza do povo brasileiro!",
                'city'                   => 'Salvador',
                'state'                  => 'BA',
                'address'                => 'Praça da Sé — Pelourinho, Centro Histórico',
                'start_date'             => now()->addDays(60)->setTime(9, 0),
                'end_date'               => now()->addDays(60)->setTime(18, 0),
                'registration_url'       => null,
                'capacidade_expositores' => 70,
                'vagas_restantes'        => 70,
                'is_active'              => true,
                'is_featured'            => false,
            ],
        ];

        foreach ($events as $event) {
            Event::updateOrCreate(
                ['slug' => $event['slug']],
                $event
            );
        }

        $this->command->info('5 eventos criados/atualizados com textos completos.');
    }
}
