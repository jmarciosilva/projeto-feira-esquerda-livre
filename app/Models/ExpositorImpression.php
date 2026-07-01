<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpositorImpression extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'expositor_id',
        'rendered_at',
        'session_hash',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'rendered_at' => 'datetime',
            'created_at'  => 'datetime',
        ];
    }

    public function expositor(): BelongsTo
    {
        return $this->belongsTo(Expositor::class);
    }
}
