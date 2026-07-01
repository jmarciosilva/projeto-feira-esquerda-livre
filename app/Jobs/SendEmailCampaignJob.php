<?php

namespace App\Jobs;

use App\Enums\CampaignStatus;
use App\Mail\CampaignMail;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignSend;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class SendEmailCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function __construct(private readonly int $campaignId) {}

    public function handle(): void
    {
        $campaign = EmailCampaign::findOrFail($this->campaignId);

        if (! in_array($campaign->status, [CampaignStatus::Scheduled, CampaignStatus::Sending], true)) {
            return;
        }

        $campaign->update(['status' => CampaignStatus::Sending]);

        $rateLimit = (int) (config('mail.marketing_rate_limit_per_minute', 60));

        $campaign->sends()->whereNull('sent_at')->chunk(50, function ($sends) use ($campaign, $rateLimit) {
            foreach ($sends as $send) {
                RateLimiter::attempt(
                    key: 'email-campaign-send',
                    maxAttempts: $rateLimit,
                    callback: function () use ($campaign, $send) {
                        try {
                            Mail::to($send->email, $send->name ?? '')
                                ->send(new CampaignMail($campaign, $send));

                            $send->update(['sent_at' => now()]);
                            $campaign->increment('sent_count');
                        } catch (\Throwable) {
                            $campaign->increment('failed_count');
                        }
                    },
                    decaySeconds: 60,
                );
            }
        });

        $campaign->update([
            'status'  => $campaign->fresh()->failed_count > 0
                ? CampaignStatus::Failed
                : CampaignStatus::Sent,
            'sent_at' => now(),
        ]);
    }
}
