<?php

namespace App\Livewire\Admin\EmailMarketing;

use App\Livewire\Admin\Concerns\AuthorizesAdminActions;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignSend;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class CampaignReport extends Component
{
    use AuthorizesAdminActions, WithPagination;

    public EmailCampaign $campaign;

    public function mount(int $id): void
    {
        $this->authorizeAdminAction('email-marketing.gerenciar');
        $this->campaign = EmailCampaign::with('creator')->findOrFail($id);
    }

    public function render(): View
    {
        $sends = EmailCampaignSend::where('campaign_id', $this->campaign->id)
            ->latest('sent_at')
            ->paginate(25);

        $stats = [
            'recipients'  => $this->campaign->recipients_count,
            'sent'        => $this->campaign->sent_count,
            'opened'      => EmailCampaignSend::where('campaign_id', $this->campaign->id)->whereNotNull('opened_at')->count(),
            'clicked'     => EmailCampaignSend::where('campaign_id', $this->campaign->id)->whereNotNull('clicked_at')->count(),
            'unsubscribed'=> EmailCampaignSend::where('campaign_id', $this->campaign->id)->whereNotNull('unsubscribed_at')->count(),
            'failed'      => $this->campaign->failed_count,
            'open_rate'   => $this->campaign->openRate(),
            'click_rate'  => $this->campaign->clickRate(),
        ];

        return view('livewire.admin.email-marketing.campaign-report', [
            'sends' => $sends,
            'stats' => $stats,
        ])->layout('admin.layouts.app', ['title' => 'Relatório: ' . $this->campaign->name]);
    }
}
