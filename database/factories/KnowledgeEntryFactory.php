<?php

namespace Database\Factories;

use App\CatalogIntelligence\Enums\KnowledgeEntryType;
use App\CatalogIntelligence\Enums\KnowledgeSource;
use App\CatalogIntelligence\Enums\KnowledgeStatus;
use App\CatalogIntelligence\Models\KnowledgeEntry;
use App\CatalogIntelligence\Support\KnowledgeNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Conceito de conhecimento para testes.
 *
 * Nasce em `draft`, como todo conhecimento que ninguém assinou — o caminho
 * "ainda não aprovado" é o padrão, e testes que precisam de conceito utilizável
 * pedem `->aprovado()` explicitamente. O contrário faria a governança
 * desaparecer da suíte sem que ninguém notasse.
 *
 * `normalized_name` é derivada aqui pelo mesmo normalizador da Action, e não
 * inventada, para a factory não produzir registros que a produção jamais
 * geraria.
 *
 * @extends Factory<KnowledgeEntry>
 */
class KnowledgeEntryFactory extends Factory
{
    protected $model = KnowledgeEntry::class;

    public function definition(): array
    {
        $name = Str::ucfirst($this->faker->unique()->words(2, true));

        return [
            'type' => KnowledgeEntryType::Technique->value,
            'name' => $name,
            'normalized_name' => app(KnowledgeNormalizer::class)->normalize($name),
            'description' => null,
            'status' => KnowledgeStatus::Draft->value,
            'source' => KnowledgeSource::Derived->value,
            'confidence' => null,
        ];
    }

    public function aprovado(): static
    {
        return $this->state(fn () => [
            'status' => KnowledgeStatus::Approved->value,
            'source' => KnowledgeSource::HumanCurated->value,
        ]);
    }

    public function doTipo(KnowledgeEntryType $type): static
    {
        return $this->state(fn () => ['type' => $type->value]);
    }

    public function chamado(string $name): static
    {
        return $this->state(fn () => [
            'name' => $name,
            'normalized_name' => app(KnowledgeNormalizer::class)->normalize($name),
        ]);
    }

    public function daOrigem(KnowledgeSource $source): static
    {
        return $this->state(fn () => ['source' => $source->value]);
    }
}
