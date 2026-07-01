<?php

namespace App\Livewire\Admin\EmailMarketing;

use App\Enums\CampaignStatus;
use App\Enums\RecipientType;
use App\Jobs\SendEmailCampaignJob;
use App\Livewire\Admin\Concerns\AuthorizesAdminActions;
use App\Models\CustomerProfile;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignSend;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class CampaignIndex extends Component
{
    use AuthorizesAdminActions, WithPagination;

    public string $search       = '';
    public string $filterStatus = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function duplicate(int $id): void
    {
        $this->authorizeAdminAction('email-marketing.gerenciar');

        $original = EmailCampaign::findOrFail($id);

        $copy = $original->replicate(['sent_at', 'scheduled_at', 'sent_count', 'failed_count', 'recipients_count']);
        $copy->name   = 'Cópia de ' . $original->name;
        $copy->status = CampaignStatus::Draft;
        $copy->created_by = auth()->id();
        $copy->save();

        session()->flash('success', 'Campanha duplicada com sucesso.');
    }

    public function delete(int $id): void
    {
        $this->authorizeAdminAction('email-marketing.gerenciar');

        $campaign = EmailCampaign::findOrFail($id);

        abort_unless($campaign->status->isDeletable(), 403, 'Somente rascunhos podem ser excluídos.');

        $campaign->delete();

        session()->flash('success', 'Campanha excluída.');
    }

    public function sendNow(int $id): void
    {
        $this->authorizeAdminAction('email-marketing.gerenciar');

        $campaign = EmailCampaign::findOrFail($id);

        abort_unless($campaign->status === CampaignStatus::Draft || $campaign->status === CampaignStatus::Scheduled, 403);

        $recipients = $this->resolveRecipients($campaign);

        if (empty($recipients)) {
            session()->flash('error', 'Nenhum destinatário encontrado para este segmento.');
            return;
        }

        foreach ($recipients as $r) {
            EmailCampaignSend::firstOrCreate(
                ['campaign_id' => $campaign->id, 'email' => $r['email']],
                ['name' => $r['name'] ?? null],
            );
        }

        $campaign->update([
            'status'           => CampaignStatus::Scheduled,
            'recipients_count' => count($recipients),
        ]);

        SendEmailCampaignJob::dispatch($campaign->id)->onQueue('email-marketing');

        session()->flash('success', 'Campanha enfileirada para envio. ' . count($recipients) . ' destinatários.');
    }

    private function resolveRecipients(EmailCampaign $campaign): array
    {
        return match ($campaign->recipient_type) {
            RecipientType::AllSubscribers => NewsletterSubscriber::where('is_active', true)
                ->get(['email', 'name'])
                ->map(fn ($s) => ['email' => $s->email, 'name' => $s->name])
                ->toArray(),

            RecipientType::Customers => CustomerProfile::with('user')
                ->where('marketing_opt_in', true)
                ->get()
                ->map(fn ($cp) => ['email' => $cp->user->email, 'name' => $cp->user->name])
                ->toArray(),

            RecipientType::CustomersActive => CustomerProfile::with('user')
                ->where('marketing_opt_in', true)
                ->whereHas('user.orders', fn ($q) => $q->where('created_at', '>=', now()->subDays(60)))
                ->get()
                ->map(fn ($cp) => ['email' => $cp->user->email, 'name' => $cp->user->name])
                ->toArray(),

            RecipientType::SegmentManual => collect($campaign->recipient_emails_manual ?? [])
                ->map(fn ($email) => ['email' => trim($email), 'name' => null])
                ->filter(fn ($r) => filter_var($r['email'], FILTER_VALIDATE_EMAIL))
                ->values()
                ->toArray(),
        };
    }

    public function render(): View
    {
        $campaigns = EmailCampaign::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('subject', 'like', "%{$this->search}%"))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->with('creator')
            ->latest()
            ->paginate(15);

        return view('livewire.admin.email-marketing.campaign-index', [
            'campaigns' => $campaigns,
            'statuses'  => CampaignStatus::cases(),
        ])->layout('admin.layouts.app', ['title' => 'Email Marketing']);
    }
}
