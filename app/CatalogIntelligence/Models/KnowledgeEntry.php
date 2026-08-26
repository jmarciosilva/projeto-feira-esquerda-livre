<?php

namespace App\CatalogIntelligence\Models;

use App\CatalogIntelligence\Enums\KnowledgeEntryType;
use App\CatalogIntelligence\Enums\KnowledgeSource;
use App\CatalogIntelligence\Enums\KnowledgeStatus;
use App\Models\Product;
use App\Models\User;
use Database\Factories\KnowledgeEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Um conceito reutilizável do catálogo: "Crochê", "Barro", "Feito à mão".
 *
 * `normalized_name` não está em $fillable de propósito — ela é derivada de
 * `name` pelo KnowledgeNormalizer, e deixar que alguém a preencha por fora
 * permitiria gravar uma chave que não corresponde ao nome. Quem cria conceito
 * passa pela Action.
 */
class KnowledgeEntry extends Model
{
    use HasFactory;

    protected $table = 'catalog_knowledge_entries';

    protected $fillable = [
        'type',
        'name',
        'description',
        'status',
        'source',
        'confidence',
        'created_by',
        'reviewed_by',
        'reviewed_at',
    ];

    /**
     * O resolver padrão procuraria em
     * Database\Factories\CatalogIntelligence\Models\ por causa do namespace
     * do módulo. As factories do projeto vivem todas na raiz de
     * database/factories, então a ligação é declarada aqui.
     */
    protected static function newFactory(): KnowledgeEntryFactory
    {
        return KnowledgeEntryFactory::new();
    }

    protected function casts(): array
    {
        return [
            'type' => KnowledgeEntryType::class,
            'status' => KnowledgeStatus::class,
            'source' => KnowledgeSource::class,
            'confidence' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function terms(): HasMany
    {
        return $this->hasMany(KnowledgeTerm::class, 'knowledge_entry_id');
    }

    /** Relações em que este conceito é a origem. */
    public function outgoingRelations(): HasMany
    {
        return $this->hasMany(KnowledgeRelation::class, 'from_entry_id');
    }

    /** Relações em que este conceito é o destino. */
    public function incomingRelations(): HasMany
    {
        return $this->hasMany(KnowledgeRelation::class, 'to_entry_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'catalog_product_knowledge', 'knowledge_entry_id', 'product_id')
            ->withPivot(['source', 'confidence'])
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * O que pode ser reutilizado para ajudar outro lojista. Rascunho e
     * rejeitado ficam de fora — é o ponto inteiro da governança.
     */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->where('status', KnowledgeStatus::Approved);
    }

    public function scopeOfType(Builder $query, KnowledgeEntryType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function isUsable(): bool
    {
        return $this->status->isUsable();
    }
}
