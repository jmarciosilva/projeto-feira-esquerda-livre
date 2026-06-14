<?php

namespace Database\Seeders;

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@feiraesquerdalivre.com.br')->first();

        $posts = [
            [
                'title'   => 'Feira Esquerda Livre chega a São Paulo com edição especial de inverno',
                'excerpt' => 'A próxima edição da Feira Esquerda Livre em São Paulo promete ser a maior já realizada, com mais de 80 expositores confirmados, shows ao vivo e gastronomia popular no Parque do Ibirapuera.',
                'content' => '<p>A <strong>Feira Esquerda Livre</strong> está de volta a São Paulo com uma edição especial de inverno que promete ser histórica. No dia 28 de junho, o Parque do Ibirapuera se transforma no maior encontro de economia solidária e cultura popular do estado, reunindo mais de 80 expositores de todo o Brasil.</p>

<h2>Uma feira para todos</h2>

<p>O evento é gratuito, a céu aberto, e pensado para toda a família. Das 9h às 18h, o visitante terá acesso a um universo de produtos artesanais, gastronomia popular e expressões culturais que raramente dividem o mesmo espaço em São Paulo.</p>

<p>Entre os destaques da programação está o coral de vozes afro-brasileiras "Nós do Baobá", que se apresenta às 15h no palco central, além de rodas de samba, percussão ao vivo e uma oficina de macramê gratuita para crianças.</p>

<h2>O que os expositores trazem</h2>

<p>A curadoria desta edição focou em três eixos principais:</p>

<ul>
<li><strong>Artesanato e moda autoral:</strong> peças em cerâmica, couro vegano, crochê, bordado e macramê feitos à mão por artesãos de diversas regiões do Brasil</li>
<li><strong>Gastronomia popular:</strong> sabores que cruzam o mapa do país, do acarajé baiano ao pão de queijo mineiro, passando por kombucha artesanal e doces nordestinos</li>
<li><strong>Arte e cultura:</strong> livros independentes, zines, gravuras, fotografia e artesanato indígena</li>
</ul>

<h2>Economia solidária na prática</h2>

<p>Mais do que um mercado, a Feira Esquerda Livre é um modelo econômico em ação. Cada produto vendido representa uma cadeia de trabalho justo, sem intermediários, onde quem produz recebe o valor real do seu trabalho.</p>

<p>"Aqui eu não pago atravessador, não dependo de algoritmo e meus clientes me conhecem de verdade", disse Maria das Graças, artesã de Feira de Santana (BA) que expõe pela terceira vez em São Paulo. "A feira mudou minha vida."</p>

<h2>Como chegar</h2>

<p>O Parque do Ibirapuera fica na Av. Pedro Álvares Cabral, s/n, e é acessível pelas linhas de metrô (estação Ana Rosa + ônibus) e por diversas linhas de ônibus municipais. Há estacionamentos nas proximidades.</p>

<p>A entrada é gratuita. <strong>Traga sacola reutilizável e venha de coração aberto!</strong></p>',
                'type'         => PostType::News,
                'published_at' => now()->subDays(2),
            ],
            [
                'title'   => 'Economia solidária: como a Feira Esquerda Livre transforma vidas no Brasil',
                'excerpt' => 'Conheça histórias reais de expositores que encontraram na Feira Esquerda Livre muito mais do que um ponto de venda — uma comunidade, um propósito e uma nova forma de viver.',
                'content' => '<p>Quando Dona Benedita Alves, ceramista do Vale do Jequitinhonha, exibiu suas peças pela primeira vez na Feira Esquerda Livre em 2022, ela não sabia que aquele seria o início de uma reviravolta em sua vida. Aos 58 anos, depois de décadas vendendo peças a atravessadores por preços irrisórios, ela finalmente viu o valor real do seu trabalho ser reconhecido.</p>

<p>"Aqui as pessoas vêm, conversam comigo, querem saber como eu faço, de onde vem o barro. Elas pagam o preço justo porque entendem o que está por trás da peça", conta ela com emoção.</p>

<h2>O que é economia solidária?</h2>

<p>A economia solidária é um conjunto de práticas econômicas baseadas em princípios de cooperação, autogestão, democracia e valorização do trabalho humano acima do lucro. Ao contrário do modelo capitalista tradicional, ela coloca as pessoas no centro das decisões econômicas.</p>

<p>Na prática, isso significa feiras sem atravessadores, preços acordados coletivamente, divisão justa dos lucros e suporte mútuo entre produtores — exatamente o modelo que a Feira Esquerda Livre pratica desde sua fundação.</p>

<h2>Histórias que a feira conta</h2>

<p>João Pimenta, 34, largou um emprego em escritório para se dedicar à produção de vinhos de frutas nativas do Cerrado. Na Feira Esquerda Livre, ele encontrou não apenas clientes, mas parceiros: hoje fornece matéria-prima para uma cooperativa de conservas e divide espaço de produção com dois outros produtores da região de Pirenópolis (GO).</p>

<p>"Ganho menos do que ganhava no escritório, mas acordo todo dia com vontade de trabalhar. Isso não tem preço", diz João.</p>

<p>Já o coletivo de costureiras "Fio a Fio", de São Luís (MA), foi formado durante a pandemia por seis mulheres que perderam o emprego formal. Hoje empregam 14 costureiras, exportam peças para o Exterior e são referência nacional em moda slow fashion com tecidos regionais.</p>

<h2>O impacto nos números</h2>

<p>Segundo levantamento próprio da Feira Esquerda Livre, a renda média mensal dos expositores cresceu <strong>43% após o primeiro ano de participação</strong>. Mais de 70% relatam ter ampliado sua rede de contatos e parcerias. E 89% dizem se sentir parte de uma comunidade — não apenas vendedores.</p>

<h2>Como participar desse movimento</h2>

<p>Você não precisa ser expositor para fazer parte da economia solidária. <strong>Comprar na feira já é um ato político</strong>: você valoriza o trabalho humano, fortalece a cultura local e opta por produtos com história e consciência.</p>

<p>E se você produz algo com as mãos, com o coração e com ética — as portas da Feira Esquerda Livre estão abertas para você.</p>',
                'type'         => PostType::Post,
                'published_at' => now()->subDays(5),
            ],
            [
                'title'   => 'Artesanato brasileiro: a riqueza das mãos que criam e resistem',
                'excerpt' => 'Do barro nordestino às tranças gaúchas, o artesanato brasileiro é um patrimônio vivo ameaçado pela industrialização — e que a Feira Esquerda Livre se orgulha de celebrar e preservar.',
                'content' => '<p>Há um Brasil que não aparece nas manchetes dos grandes jornais. Um Brasil de mãos calejadas e criativas, de olhos atentos à tradição e ao belo, de saberes transmitidos de mãe para filha, de avô para neto, de mestre para aprendiz. É o Brasil do artesanato — e ele está vivo, resistindo, e esbanjando beleza nas bancas da Feira Esquerda Livre.</p>

<h2>Uma herança de povos e culturas</h2>

<p>O artesanato brasileiro é fruto da confluência de três grandes matrizes culturais: a indígena, a africana e a europeia — cada uma com suas técnicas, seus materiais e sua visão de mundo.</p>

<p>Da cerâmica marajoara, produzida há mais de mil anos na ilha do Marajó, ao tear de palha dos povos Yanomami; dos bordados renascença do Nordeste, trazidos pelos portugueses e ressignificados pelas mãos brasileiras, à escultura em madeira dos afro-descendentes do Recôncavo Baiano — cada peça é uma enciclopédia viva da história do Brasil.</p>

<h2>O desafio da sobrevivência</h2>

<p>Apesar de sua riqueza, o artesanato brasileiro enfrenta desafios sérios. A industrialização trouxe produtos baratos que simulam o artesanal sem o ser. As novas gerações, atraídas por empregos urbanos, muitas vezes abandonam os saberes ancestrais. E quando um mestre artesão morre sem transmitir seu conhecimento, leva consigo uma técnica que levou séculos para se desenvolver.</p>

<p>"Minha avó me ensinou a fazer renda de bilro. Se eu não tiver quem me suceda, essa técnica específica — com os pontos que ela criou — acaba comigo", diz Rosária, rendeira de Divino (MG), que participa da Feira há quatro anos.</p>

<h2>O que você encontra na Feira</h2>

<p>Na Feira Esquerda Livre, o artesanato tem lugar de honra. São dezenas de técnicas representadas:</p>

<ul>
<li><strong>Cerâmica:</strong> do barro vermelho nordestino à porcelana fria paulistana</li>
<li><strong>Têxteis:</strong> macramê, crochê, bordado, tricô, tear e renda de bilro</li>
<li><strong>Madeira:</strong> esculturas, utensílios e móveis artesanais</li>
<li><strong>Couro:</strong> bolsas, cintos e sandálias trabalhadas à mão</li>
<li><strong>Joias e bijuterias:</strong> prata, sementes, resina e materiais reciclados</li>
<li><strong>Papel artesanal:</strong> cadernos encadernados à mão e papéis reciclados</li>
</ul>

<h2>Comprar é preservar</h2>

<p>Quando você compra uma peça artesanal, não está apenas levando um objeto para casa. Você está sustentando uma cadeia de preservação cultural. Está dizendo "sim" a um modo de vida baseado na criação, na paciência e no respeito pelo tempo das coisas.</p>

<p>Na próxima vez que você visitar a Feira Esquerda Livre, olhe nos olhos de quem fez o que você vai comprar. Pergunte como foi feito. Onde vem o material. Quanto tempo levou. Essa conversa já é, em si, um ato de resistência cultural.</p>',
                'type'         => PostType::Post,
                'published_at' => now()->subDays(8),
            ],
            [
                'title'   => 'Como se tornar um expositor da Feira Esquerda Livre: guia completo',
                'excerpt' => 'Sonha em expor seus produtos na Feira? Este guia completo explica os critérios de seleção, como se preparar, o que levar e o que esperar da experiência de ser um expositor.',
                'content' => '<p>Todos os meses recebemos centenas de mensagens de artesãos, produtores e artistas que querem participar da Feira Esquerda Livre. Se você é um deles, este guia foi feito especialmente para você.</p>

<h2>Quem pode ser expositor?</h2>

<p>A Feira Esquerda Livre é aberta a produtores independentes, artesãos, agricultores familiares, coletivos e pequenas cooperativas que:</p>

<ul>
<li>Produzam o que vendem (sem revenda de produtos industrializados)</li>
<li>Trabalhem com práticas éticas de produção (sem trabalho infantil ou análogo à escravidão)</li>
<li>Estejam alinhados com os valores do movimento: economia solidária, diversidade e cultura popular</li>
<li>Tenham CNPJ ou CPF (pessoa física também é bem-vinda)</li>
</ul>

<h2>O processo de inscrição</h2>

<p><strong>Passo 1 — Preencha o formulário:</strong> Acesse nossa página "Seja um Expositor" e preencha todas as informações sobre você, sua produção e seus produtos. Quanto mais completo, melhor.</p>

<p><strong>Passo 2 — Curadoria:</strong> Nossa equipe analisa cada solicitação em até 5 dias úteis. Avaliamos a autenticidade da produção, a qualidade das fotos e o alinhamento com os valores da feira.</p>

<p><strong>Passo 3 — Aprovação e onboarding:</strong> Se aprovado, você receberá um e-mail com todas as instruções para a primeira participação: montagem, horários, regras de exposição e dicas práticas.</p>

<h2>O que levar para a feira</h2>

<p>Para uma boa experiência, recomendamos:</p>

<ul>
<li>Estrutura própria de exposição (mesa, tela, suporte) — a feira não fornece</li>
<li>Cartão de visitas ou QR code com seus contatos e redes sociais</li>
<li>Maquininha de cartão (a maioria dos clientes não leva dinheiro)</li>
<li>Embalagem sustentável para as vendas (evite plástico)</li>
<li>Água, snacks e disposição para conversar — o contato humano é o coração da feira</li>
</ul>

<h2>Quanto custa participar?</h2>

<p>A taxa de participação varia por edição e cidade. Em média, fica entre R$ 80 e R$ 150 por dia de feira, dependendo do espaço ocupado. Expositores recorrentes têm desconto progressivo a partir da terceira edição consecutiva.</p>

<h2>O que esperar da experiência</h2>

<p>"O primeiro dia é sempre de adaptação. O segundo, você já entende o ritmo. A partir do terceiro, você não quer mais sair", brinca Carlos, marceneiro artesanal de Itajaí (SC) que expõe há dois anos.</p>

<p>Além das vendas, você vai construir uma rede de parceiros, ganhar visibilidade nas redes sociais da feira (com mais de 50 mil seguidores), receber feedback direto dos clientes e fazer parte de uma comunidade que acredita no mesmo que você.</p>

<p><strong>Pronto para dar o próximo passo? Acesse o formulário abaixo e comece sua jornada na Feira Esquerda Livre.</strong></p>',
                'type'         => PostType::Post,
                'published_at' => now()->subDays(12),
            ],
            [
                'title'   => 'Gastronomia popular: os sabores que contam a história do Brasil',
                'excerpt' => 'A Feira Esquerda Livre é também um festival gastronômico. Descubra os sabores que representam a identidade culinária dos povos brasileiros e as histórias por trás de cada prato.',
                'content' => '<p>Feche os olhos e imagine: o cheiro de dendê e camarão seco no acarajé que acabou de sair da frigideira. O vapor da tapioca estufando sobre a chapa quente. O aroma de ervas do chimichurri artesanal. O doce de leite em ponto de fio, feito em panela de cobre numa cozinha do sertão. Isso é a gastronomia popular brasileira — e ela está toda na Feira Esquerda Livre.</p>

<h2>Comida como identidade cultural</h2>

<p>No Brasil, cada região tem seus ingredientes, suas técnicas e seus rituais à mesa. E cada prato conta uma história de migração, de mistura de povos, de adaptação ao território. A gastronomia popular não é apenas sustento — é memória, é pertencimento, é resistência.</p>

<p>O acarajé, por exemplo, é muito mais do que um bolinho frito: é um alimento sagrado do candomblé, oferenda a Iansã, que atravessou o Atlântico nas mãos das mulheres africanas escravizadas e hoje alimenta Salvador com força e ancestralidade.</p>

<h2>Os sabores que você encontra na Feira</h2>

<p>A Feira Esquerda Livre tem uma curadoria rigorosa para a área de gastronomia. Priorizamos produtores que utilizam ingredientes locais, técnicas tradicionais e receitas transmitidas por gerações:</p>

<ul>
<li><strong>Nordeste:</strong> acarajé, cuscuz, tapioca, rapadura, mel de abelha nativa, licor de umbu, doce de leite de cabra</li>
<li><strong>Sudeste:</strong> pão de queijo artesanal, doce de abóbora com coco, goiabada cascão, cachaça de alambique, kombucha de frutas</li>
<li><strong>Sul:</strong> chimia de butiá, erva-mate orgânica, embutidos artesanais, pães coloniais, vinho de uva Isabel</li>
<li><strong>Centro-Oeste:</strong> pequi em conserva, baru torrado, mel do Cerrado, licor de cagaita, pamonha de forno</li>
<li><strong>Norte:</strong> açaí natural (sem xarope), castanha-do-pará fresca, farinha d\'água, tucumã, tacacá em versão vegana</li>
</ul>

<h2>Uma história: o bolo de macaxeira de Dona Lurdes</h2>

<p>Dona Lurdes Ferreira, 67 anos, vende bolo de macaxeira na Feira há três edições. A receita é da bisavó, que veio de Pernambuco para São Paulo nos anos 1950. "Minha bisavó fazia nas festas de rua do Brás. Minha avó aprendeu. Minha mãe aprendeu. Eu aprendi. E agora todo mundo que come meu bolo leva um pedaço dessa história pra casa."</p>

<p>É isso que a gastronomia popular da Feira Esquerda Livre oferece: não apenas sabor, mas memória. Não apenas comida, mas conexão.</p>

<h2>Dicas para aproveitar</h2>

<p>Venha com fome e com tempo. Não se apresse. Converse com quem vende. Pergunte a história do prato, de onde vem o ingrediente, como é a receita. E, principalmente, experimente coisas que você nunca comeu. A Feira Esquerda Livre é o melhor mapa gastronômico do Brasil inteiro num só lugar.</p>',
                'type'         => PostType::News,
                'published_at' => now()->subDays(15),
            ],
        ];

        foreach ($posts as $data) {
            $slug = Str::slug($data['title']);
            Post::updateOrCreate(
                ['slug' => $slug],
                array_merge($data, [
                    'user_id'   => $admin?->id ?? 1,
                    'slug'      => $slug,
                    'status'    => PostStatus::Published,
                    'is_active' => true,
                ])
            );
        }

        $this->command->info('5 posts criados/atualizados com conteúdo completo.');
    }
}
