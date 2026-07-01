<?php

namespace App\Models\Ava;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AvaModule extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'description',
        'sort_order',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(AvaCourse::class, 'course_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(AvaLesson::class, 'module_id')->orderBy('sort_order');
    }
}
