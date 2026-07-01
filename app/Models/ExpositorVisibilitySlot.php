<?php

namespace App\Models;

use App\Enums\VisibilitySlotType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpositorVisibilitySlot extends Model
{
    protected $fillable = [
        'expositor_id',
        'slot_type',
        'priority',
        'active_from',
        'active_until',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'slot_type'    => VisibilitySlotType::class,
            'active_from'  => 'datetime',
            'active_until' => 'datetime',
        ];
    }

    public function expositor(): BelongsTo
    {
        return $this->belongsTo(Expositor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        $now = now();

        if ($this->active_from && $this->active_from->gt($now)) {
            return false;
        }

        if ($this->active_until && $this->active_until->lt($now)) {
            return false;
        }

        return true;
    }
}
