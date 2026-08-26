<?php

namespace Database\Seeders;

use App\CatalogIntelligence\Actions\AttachKnowledgeTerm;
use App\CatalogIntelligence\Actions\CreateOrUpdateKnowledge;
use App\CatalogIntelligence\Actions\RelateKnowledge;
use App\CatalogIntelligence\Enums\KnowledgeEntryType as Tipo;
use App\CatalogIntelligence\Enums\KnowledgeRelationType as Rel;
use App\CatalogIntelligence\Enums\KnowledgeSource;
use App\CatalogIntelligence\Enums\KnowledgeTermType as TermoTipo;
use App\CatalogIntelligence\Models\KnowledgeEntry;
use Illuminate\Database\Seeder;

/**
 * Base inicial de conhecimento do catálogo.
 *
 * Os 28 conceitos abaixo NÃO vieram de uma lista genérica de artesanato: saíram
 * da leitura dos itens que a Feira realmente tem hoje. "Xilogravura" está aqui
 * porque existe uma gravura em xilogravura no catálogo; "Ervas medicinais"
 * porque existem tinturas, cremes e kits de plantas; "Costura" porque há ajuste,
 * reforma e customização de roupa. Semear conceitos que ninguém usa encheria a
 * base de ruído logo no primeiro dia.
 *
 * Crochê e Tricô entram como técnicas têxteis do mesmo ofício das que já
 * aparecem no catálogo (bordado, tecelagem) — são a direção natural de
 * crescimento das lojas de fios, não invenção.
 *
 * ## Idempotência
 *
 * Roda quantas vezes for preciso sem duplicar: tudo passa pelas Actions, que
 * casam pela chave normalizada. Não toca em produto nenhum.
 */
class CatalogKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $criar = app(CreateOrUpdateKnowledge::class);
        $termo = app(AttachKnowledgeTerm::class);
        $ligar = app(RelateKnowledge::class);

        /** @var array<string, KnowledgeEntry> $c */
        $c = [];

        foreach ($this->conceitos() as [$tipo, $nome, $descricao, $termos]) {
            $entry = $criar($tipo, $nome, KnowledgeSource::Seed, description: $descricao);
            $c[$nome] = $entry;

            foreach ($termos as $t => $tipoTermo) {
                $termo($entry, $t, $tipoTermo);
            }
        }

        foreach ($this->relacoes() as [$de, $tipo, $para]) {
            $ligar($c[$de], $c[$para], $tipo);
        }
    }

    /**
     * @return array<int, array{0: Tipo, 1: string, 2: string|null, 3: array<string, TermoTipo>}>
     */
    private function conceitos(): array
    {
        $sin = TermoTipo::Synonym;
        $ali = TermoTipo::Alias;
        $com = TermoTipo::CommercialTerm;

        return [
            // ── Técnicas ─────────────────────────────────────────────────────
            [Tipo::Technique, 'Crochê', 'Técnica têxtil feita com agulha única, ponto a ponto, à mão.', ['crochetar' => $sin, 'croche' => $ali]],
            [Tipo::Technique, 'Tricô', 'Técnica têxtil feita com duas agulhas, à mão ou em máquina manual.', ['tricotar' => $sin, 'trico' => $ali]],
            [Tipo::Technique, 'Bordado', 'Desenho feito com linha sobre tecido.', ['bordar' => $sin, 'bordado à mão' => $com]],
            [Tipo::Technique, 'Tecelagem', 'Entrelaçamento de fios para formar tecido.', ['tecer' => $sin, 'tecido artesanal' => $com]],
            [Tipo::Technique, 'Costura', 'União e ajuste de peças de tecido.', ['costurar' => $sin, 'ajuste de roupa' => $com]],
            [Tipo::Technique, 'Cerâmica', 'Modelagem e queima de argila.', ['ceramica' => $ali, 'cerâmica artesanal' => $com]],
            [Tipo::Technique, 'Xilogravura', 'Gravura impressa a partir de matriz entalhada em madeira.', ['gravura em madeira' => $sin]],

            // ── Materiais ────────────────────────────────────────────────────
            [Tipo::Material, 'Barro', 'Argila usada em peças cerâmicas.', ['argila' => $sin]],
            [Tipo::Material, 'Algodão', 'Fibra natural usada em fios e tecidos.', ['algodao' => $ali]],
            [Tipo::Material, 'Lã', 'Fibra animal usada em fios para tricô e crochê.', ['la' => $ali]],
            [Tipo::Material, 'Sementes', 'Sementes naturais usadas em peças de adorno.', ['sementes amazônicas' => $com]],
            [Tipo::Material, 'Ervas medicinais', 'Plantas usadas em preparos de cuidado e bem viver.', ['plantas medicinais' => $sin, 'ervas' => $ali]],

            // ── Tipos de item ────────────────────────────────────────────────
            [Tipo::ProductType, 'Almofada', null, []],
            [Tipo::ProductType, 'Bolsa', null, []],
            [Tipo::ProductType, 'Vaso', null, []],
            [Tipo::ProductType, 'Tigela', null, []],
            [Tipo::ProductType, 'Colar', null, []],
            [Tipo::ProductType, 'Kit', 'Conjunto de itens vendidos juntos.', ['combo' => $sin]],

            // ── Contextos de uso ─────────────────────────────────────────────
            [Tipo::Context, 'Decoração', 'Itens para compor ambientes.', ['decoracao' => $ali]],
            [Tipo::Context, 'Casa', 'Uso doméstico cotidiano.', ['lar' => $sin]],
            [Tipo::Context, 'Moda', 'Vestuário e acessórios de uso pessoal.', []],
            [Tipo::Context, 'Presente', 'Item escolhido para presentear.', ['lembrança' => $sin]],

            // ── Temas ────────────────────────────────────────────────────────
            [Tipo::Theme, 'Artesanato', 'Produção manual, em pequena escala, com saber próprio de quem faz.', ['produção artesanal' => $sin, 'trabalho manual' => $sin]],
            [Tipo::Theme, 'Bem viver', 'Práticas voltadas a saúde, equilíbrio e cuidado.', ['bem-estar' => $sin]],
            [Tipo::Theme, 'Terapia integrativa', 'Práticas de cuidado complementares.', ['práticas integrativas' => $sin]],
            [Tipo::Theme, 'Economia solidária', 'Produção e comércio baseados em cooperação.', []],

            // ── Atributos ────────────────────────────────────────────────────
            [Tipo::Attribute, 'Feito à mão', 'Produzido manualmente, sem linha de produção.', ['artesanal' => $sin, 'feito a mao' => $ali]],
            [Tipo::Attribute, 'Natural', 'Feito com ingredientes ou matérias-primas de origem natural.', ['ingredientes naturais' => $com]],
        ];
    }

    /**
     * @return array<int, array{0: string, 1: Rel, 2: string}>
     */
    private function relacoes(): array
    {
        return [
            // Técnicas são técnicas de artesanato.
            ['Crochê', Rel::TechniqueOf, 'Artesanato'],
            ['Tricô', Rel::TechniqueOf, 'Artesanato'],
            ['Bordado', Rel::TechniqueOf, 'Artesanato'],
            ['Tecelagem', Rel::TechniqueOf, 'Artesanato'],
            ['Costura', Rel::TechniqueOf, 'Artesanato'],
            ['Cerâmica', Rel::TechniqueOf, 'Artesanato'],
            ['Xilogravura', Rel::TechniqueOf, 'Artesanato'],

            // Onde cada técnica aparece.
            ['Crochê', Rel::UsedIn, 'Almofada'],
            ['Crochê', Rel::UsedIn, 'Bolsa'],
            ['Tricô', Rel::UsedIn, 'Bolsa'],
            ['Bordado', Rel::UsedIn, 'Almofada'],
            ['Cerâmica', Rel::UsedIn, 'Vaso'],
            ['Cerâmica', Rel::UsedIn, 'Tigela'],

            // De que cada coisa é feita.
            ['Barro', Rel::UsedIn, 'Cerâmica'],
            ['Algodão', Rel::UsedIn, 'Crochê'],
            ['Lã', Rel::UsedIn, 'Tricô'],
            ['Sementes', Rel::UsedIn, 'Colar'],

            // Onde cada tipo de item se encaixa.
            ['Almofada', Rel::BelongsTo, 'Decoração'],
            ['Vaso', Rel::BelongsTo, 'Decoração'],
            ['Tigela', Rel::BelongsTo, 'Casa'],
            ['Bolsa', Rel::BelongsTo, 'Moda'],
            ['Colar', Rel::BelongsTo, 'Moda'],
            ['Kit', Rel::BelongsTo, 'Presente'],

            // Vizinhanças conceituais.
            ['Artesanato', Rel::RelatedTo, 'Feito à mão'],
            ['Artesanato', Rel::RelatedTo, 'Economia solidária'],
            ['Ervas medicinais', Rel::RelatedTo, 'Bem viver'],
            ['Terapia integrativa', Rel::RelatedTo, 'Bem viver'],
            ['Ervas medicinais', Rel::RelatedTo, 'Natural'],
        ];
    }
}
