<?php

namespace App\Models\Ava;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AvaLesson extends Model
{
    protected $fillable = [
        'module_id',
        'title',
        'description',
        'content_type',
        'video_url',
        'video_provider',
        'video_duration_sec',
        'text_content',
        'is_preview',
        'is_visible',
        'sort_order',
        'drip_day',
    ];

    protected $casts = [
        'is_preview'  => 'boolean',
        'is_visible'  => 'boolean',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(AvaModule::class, 'module_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(AvaLessonMaterial::class, 'lesson_id')->orderBy('sort_order');
    }

    public function isVideo(): bool
    {
        return $this->content_type === 'video';
    }

    public function isPdf(): bool
    {
        return $this->content_type === 'pdf';
    }

    public function isAudio(): bool
    {
        return $this->content_type === 'audio';
    }

    public function isTexto(): bool
    {
        return $this->content_type === 'texto';
    }

    /** Converte a URL do YouTube/Vimeo para URL de embed. */
    public function embedUrl(): ?string
    {
        if (! $this->video_url) {
            return null;
        }

        if ($this->video_provider === 'youtube' || str_contains($this->video_url, 'youtu')) {
            preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([A-Za-z0-9_\-]{11})/', $this->video_url, $m);
            return isset($m[1]) ? "https://www.youtube.com/embed/{$m[1]}?rel=0&modestbranding=1" : null;
        }

        if ($this->video_provider === 'vimeo' || str_contains($this->video_url, 'vimeo')) {
            preg_match('/vimeo\.com\/(\d+)/', $this->video_url, $m);
            return isset($m[1]) ? "https://player.vimeo.com/video/{$m[1]}" : null;
        }

        return $this->video_url;
    }

    public function durationLabel(): string
    {
        if (! $this->video_duration_sec) {
            return '';
        }

        $minutes = intdiv($this->video_duration_sec, 60);
        $seconds = $this->video_duration_sec % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
