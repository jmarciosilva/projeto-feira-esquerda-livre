<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmailCampaignSend extends Model
{
    protected $fillable = [
        'campaign_id',
        'email',
        'name',
        'tracking_pixel_token',
        'sent_at',
        'opened_at',
        'clicked_at',
        'unsubscribed_at',
        'bounce_type',
    ];

    protected function casts(): array
    {
        return [
            'sent_at'          => 'datetime',
            'opened_at'        => 'datetime',
            'clicked_at'       => 'datetime',
            'unsubscribed_at'  => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $send) {
            $send->tracking_pixel_token ??= Str::uuid()->toString();
        });
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'campaign_id');
    }
}
