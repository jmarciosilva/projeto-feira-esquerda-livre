<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Enums\RecipientType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailCampaign extends Model
{
    protected $fillable = [
        'name',
        'subject',
        'from_name',
        'from_email',
        'body_html',
        'body_text',
        'recipient_type',
        'recipient_emails_manual',
        'template_key',
        'status',
        'scheduled_at',
        'sent_at',
        'recipients_count',
        'sent_count',
        'failed_count',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status'                  => CampaignStatus::class,
            'recipient_type'          => RecipientType::class,
            'recipient_emails_manual' => 'array',
            'scheduled_at'            => 'datetime',
            'sent_at'                 => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sends(): HasMany
    {
        return $this->hasMany(EmailCampaignSend::class, 'campaign_id');
    }

    public function openRate(): float
    {
        if ($this->sent_count === 0) {
            return 0.0;
        }

        return round($this->sends()->whereNotNull('opened_at')->count() / $this->sent_count * 100, 1);
    }

    public function clickRate(): float
    {
        if ($this->sent_count === 0) {
            return 0.0;
        }

        return round($this->sends()->whereNotNull('clicked_at')->count() / $this->sent_count * 100, 1);
    }

    public function unsubscribeCount(): int
    {
        return $this->sends()->whereNotNull('unsubscribed_at')->count();
    }
}
