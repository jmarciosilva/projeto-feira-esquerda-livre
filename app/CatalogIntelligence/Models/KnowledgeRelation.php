<?php

namespace App\CatalogIntelligence\Models;

use App\CatalogIntelligence\Enums\KnowledgeRelationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ligação dirigida entre dois conceitos. */
class KnowledgeRelation extends Model
{
    use HasFactory;

    protected $table = 'catalog_knowledge_relations';

    protected $fillable = [
        'from_entry_id',
        'to_entry_id',
        'relation_type',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'relation_type' => KnowledgeRelationType::class,
            'weight' => 'decimal:2',
        ];
    }

    public function from(): BelongsTo
    {
        return $this->belongsTo(KnowledgeEntry::class, 'from_entry_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(KnowledgeEntry::class, 'to_entry_id');
    }
}
