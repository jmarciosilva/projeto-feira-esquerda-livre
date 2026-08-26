<?php

namespace App\CatalogIntelligence\Actions;

use App\CatalogIntelligence\Enums\KnowledgeEntryType;
use App\CatalogIntelligence\Enums\KnowledgeSource;
use App\CatalogIntelligence\Enums\KnowledgeStatus;
use App\CatalogIntelligence\Models\KnowledgeEntry;
use App\CatalogIntelligence\Support\KnowledgeNormalizer;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * A única porta de entrada de conceitos na base.
 *
 * Nada cria KnowledgeEntry direto: nem seeder, nem command, nem Livewire. Se
 * criassem, cada um normalizaria do seu jeito e decidiria por conta própria se
 * pode sobrescrever o que já existia — e as duas coisas são justamente o que a
 * base não pode deixar em aberto.
 *
 * ## O que esta Action garante
 *
 * 1. Normalização central, sempre a mesma.
 * 2. Unicidade por `(type, normalized_name)`, apoiada na UNIQUE do banco e não
 *    num `if (! exists())` — duas requisições simultâneas com o mesmo conceito
 *    colidem no banco, e a colisão é tratada aqui como reencontro do registro
 *    existente, não como erro.
 * 3. Proveniência preservada: origem de menor confiança nunca sobrescreve o
 *    que uma de maior confiança afirmou.
 * 4. Status nunca sobe sozinho. Só uma pessoa aprova.
 */
class CreateOrUpdateKnowledge
{
    public function __construct(private readonly KnowledgeNormalizer $normalizer) {}

    /**
     * Cria o conceito, ou devolve o existente possivelmente enriquecido.
     *
     * @param  string|null  $description  Só é gravada se a origem tiver direito de escrever.
     */
    public function __invoke(
        KnowledgeEntryType $type,
        string $name,
        KnowledgeSource $source,
        ?KnowledgeStatus $status = null,
        ?string $description = null,
        ?int $createdBy = null,
    ): KnowledgeEntry {
        $displayName = $this->normalizer->cleanDisplayName($name);
        $normalized = $this->normalizer->normalize($name);

        if ($normalized === '') {
            throw new InvalidArgumentException(
                'Nome de conhecimento vazio depois de normalizado: '.var_export($name, true)
            );
        }

        $status ??= $this->defaultStatusFor($source);
        $this->guardStatus($status, $source);

        $existing = KnowledgeEntry::query()
            ->where('type', $type)
            ->where('normalized_name', $normalized)
            ->first();

        if ($existing) {
            return $this->enrich($existing, $source, $description);
        }

        try {
            return $this->insert($type, $displayName, $normalized, $source, $status, $description, $createdBy);
        } catch (QueryException $e) {
            // Corrida perdida: outra requisição inseriu o mesmo conceito entre
            // a consulta acima e este insert. A UNIQUE fez o trabalho dela;
            // aqui o desfecho correto é reencontrar o registro, não estourar.
            $winner = KnowledgeEntry::query()
                ->where('type', $type)
                ->where('normalized_name', $normalized)
                ->first();

            if (! $winner) {
                throw $e;
            }

            return $this->enrich($winner, $source, $description);
        }
    }

    private function insert(
        KnowledgeEntryType $type,
        string $displayName,
        string $normalized,
        KnowledgeSource $source,
        KnowledgeStatus $status,
        ?string $description,
        ?int $createdBy,
    ): KnowledgeEntry {
        return DB::transaction(function () use ($type, $displayName, $normalized, $source, $status, $description, $createdBy) {
            $entry = new KnowledgeEntry([
                'type' => $type,
                'name' => $displayName,
                'description' => $description,
                'status' => $status,
                'source' => $source,
                'created_by' => $createdBy,
            ]);

            // Atribuída fora do fillable: a chave é derivada do nome, nunca
            // recebida de quem chama.
            $entry->normalized_name = $normalized;
            $entry->save();

            return $entry;
        });
    }

    /**
     * Reencontro de conceito existente.
     *
     * Enriquecer é acrescentar o que faltava — nunca reescrever o que uma
     * origem mais confiável já disse. Uma dedução automática não corrige a
     * descrição escrita por uma pessoa; no máximo preenche uma que estava
     * vazia.
     *
     * O status não é tocado aqui em hipótese alguma: aprovar é ato humano
     * explícito, e um reencontro não é aprovação.
     */
    private function enrich(KnowledgeEntry $entry, KnowledgeSource $source, ?string $description): KnowledgeEntry
    {
        if ($description === null || trim($description) === '') {
            return $entry;
        }

        $temDescricao = $entry->description !== null && trim($entry->description) !== '';

        if ($temDescricao && ! $source->outranks($entry->source)) {
            return $entry;
        }

        $entry->description = $description;

        // A origem sobe junto quando quem escreveu vale mais; do contrário o
        // registro passaria a exibir um texto de curador sob a bandeira de uma
        // dedução automática.
        if ($source->outranks($entry->source)) {
            $entry->source = $source;
        }

        $entry->save();

        return $entry;
    }

    /**
     * Origem assinada por pessoa nasce aprovada; o resto nasce rascunho e
     * espera revisão. É esta linha que impede
     * "produto cadastrado → conhecimento aprovado".
     */
    private function defaultStatusFor(KnowledgeSource $source): KnowledgeStatus
    {
        return $source->isHuman() ? KnowledgeStatus::Approved : KnowledgeStatus::Draft;
    }

    private function guardStatus(KnowledgeStatus $status, KnowledgeSource $source): void
    {
        if ($status === KnowledgeStatus::Approved && ! $source->isHuman()) {
            throw new InvalidArgumentException(
                "Conhecimento de origem {$source->value} não pode nascer aprovado; precisa de revisão humana."
            );
        }
    }
}
