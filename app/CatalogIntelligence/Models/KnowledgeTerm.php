<?php

namespace App\CatalogIntelligence\Models;

use App\CatalogIntelligence\Enums\KnowledgeTermType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Outra forma de escrever ou procurar um conceito.
 *
 * Como em KnowledgeEntry, `normalized_term` fica fora de $fillable: é derivada.
 */
class KnowledgeTerm extends Model
{
    use HasFactory;

    protected $table = 'catalog_knowledge_terms';

    protected $fillable = [
        'knowledge_entry_id',
        'term',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'type' => KnowledgeTermType::class,
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(KnowledgeEntry::class, 'knowledge_entry_id');
    }
}
