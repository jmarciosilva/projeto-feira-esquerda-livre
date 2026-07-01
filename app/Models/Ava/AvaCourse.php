<?php

namespace App\Models\Ava;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AvaCourse extends Model
{
    protected $fillable = [
        'product_id',
        'intro_video_url',
        'requirements',
        'what_youll_learn',
        'level',
        'estimated_hours',
        'access_duration_days',
        'is_drip',
        'certificate_enabled',
        'published_at',
    ];

    protected $casts = [
        'estimated_hours'     => 'float',
        'is_drip'             => 'boolean',
        'certificate_enabled' => 'boolean',
        'published_at'        => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(AvaModule::class, 'course_id')->orderBy('sort_order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(AvaEnrollment::class, 'course_id');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    public function totalLessons(): int
    {
        return $this->modules()
            ->withCount('lessons')
            ->get()
            ->sum('lessons_count');
    }

    public function levelLabel(): string
    {
        return match($this->level) {
            'intermediario' => 'Intermediário',
            'avancado'      => 'Avançado',
            default         => 'Iniciante',
        };
    }
}
