<?php

namespace App\Models\Ava;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AvaLessonMaterial extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'lesson_id',
        'title',
        'file_path',
        'file_type',
        'file_size_kb',
        'sort_order',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(AvaLesson::class, 'lesson_id');
    }

    /** Gera URL temporária assinada (15 min). Nunca expõe o path direto. */
    public function temporaryUrl(int $minutes = 15): string
    {
        return Storage::temporaryUrl($this->file_path, now()->addMinutes($minutes));
    }
}
