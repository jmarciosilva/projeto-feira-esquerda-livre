<?php

namespace App\Models;

use App\Enums\SectionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSection extends Model
{
    protected $fillable = [
        'page_id',
        'type',
        'title',
        'subtitle',
        'content',
        'image_path',
        'button_text',
        'button_link',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type'      => SectionType::class,
            'is_active' => 'boolean',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
