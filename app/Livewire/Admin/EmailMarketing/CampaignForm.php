<?php

namespace App\Livewire\Admin\EmailMarketing;

use App\Enums\CampaignStatus;
use App\Enums\RecipientType;
use App\Livewire\Admin\Concerns\AuthorizesAdminActions;
use App\Mail\CampaignMail;
use App\Models\CustomerProfile;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignSend;
use App\Models\NewsletterSubscriber;
use App\Models\SiteSetting;
use Illuminate\View\View;
use Livewire\Component;
use Illuminate\Support\Facades\Mail;

class CampaignForm extends Component
{
    use AuthorizesAdminActions;

    public ?EmailCampaign $campaign = null;

    public string $name                   = '';
    public string $subject                = '';
    public string $fromName               = '';
    public string $fromEmail              = '';
    public string $bodyHtml               = '';
    public string $recipientType          = 'all_subscribers';
    public string $recipientEmailsManual  = '';
    public string $templateKey            = '';
    public string $scheduledAt            = '';

    public int $estimatedRecipients = 0;

    public function mount(?int $id = null): void
    {
        $this->authorizeAdminAction('email-marketing.gerenciar');

        $settings = SiteSetting::first();
        $this->fromName  = $settings?->mail_from_name  ?? config('mail.from.name', 'Feira Esquerda Livre');
        $this->fromEmail = $settings?->mail_from_email ?? config('mail.from.address', 'noreply@feiraesquerdalivre.com.br');

        if ($id) {
            $this->campaign = EmailCampaign::findOrFail($id);
            abort_unless($this->campaign->status->isEditable(), 403, 'Esta campanha não pode ser editada.');

            $this->name                  = $this->campaign->name;
            $this->subject               = $this->campaign->subject;
            $this->fromName              = $this->campaign->from_name;
            $this->fromEmail             = $this->campaign->from_email;
            $this->bodyHtml              = $this->campaign->body_html;
            $this->recipientType         = $this->campaign->recipient_type->value;
            $this->recipientEmailsManual = implode("\n", $this->campaign->recipient_emails_manual ?? []);
            $this->templateKey           = $this->campaign->template_key ?? '';
            $this->scheduledAt           = $this->campaign->scheduled_at?->format('Y-m-d\TH:i') ?? '';
        }

        $this->refreshEstimate();
    }

    public function updatedRecipientType(): void
    {
        $this->refreshEstimate();
    }

    public function updatedRecipientEmailsManual(): void
    {
        $this->refreshEstimate();
    }

    public function applyTemplate(string $key): void
    {
        $this->templateKey = $key;
        $this->bodyHtml    = $this->templates()[$key]['html'] ?? $this->bodyHtml;
    }

    public function save(): void
    {
        $this->authorizeAdminAction('email-marketing.gerenciar');

        $data = $this->validate([
            'name'          => 'required|string|max:255',
            'subject'       => 'required|string|max:255',
            'fromName'      => 'required|string|max:100',
            'fromEmail'     => 'required|email|max:255',
            'bodyHtml'      => 'required|string',
            'recipientType' => 'required|in:' . implode(',', array_column(RecipientType::cases(), 'value')),
            'scheduledAt'   => 'nullable|date|after:now',
        ]);

        $manualEmails = $this->recipientType === 'segment_manual'
            ? array_filter(array_map('trim', explode("\n", $this->recipientEmailsManual)))
            : null;

        $payload = [
            'name'                   => $data['name'],
            'subject'                => $data['subject'],
            'from_name'              => $data['fromName'],
            'from_email'             => $data['fromEmail'],
            'body_html'              => $data['bodyHtml'],
            'body_text'              => strip_tags($data['bodyHtml']),
            'recipient_type'         => $data['recipientType'],
            'recipient_emails_manual'=> $manualEmails,
            'template_key'           => $this->templateKey ?: null,
            'scheduled_at'           => $data['scheduledAt'] ?: null,
            'status'                 => $data['scheduledAt'] ? CampaignStatus::Scheduled : CampaignStatus::Draft,
            'created_by'             => $this->campaign?->created_by ?? auth()->id(),
        ];

        if ($this->campaign) {
            $this->campaign->update($payload);
        } else {
            EmailCampaign::create($payload);
        }

        session()->flash('success', 'Campanha salva com sucesso.');
        $this->redirect(route('admin.email-marketing.index'));
    }

    public function sendTestEmail(): void
    {
        $this->authorizeAdminAction('email-marketing.gerenciar');

        $this->validate([
            'subject'   => 'required|string',
            'fromName'  => 'required|string',
            'fromEmail' => 'required|email',
            'bodyHtml'  => 'required|string',
        ]);

        $user    = auth()->user();
        $tempSend = new EmailCampaignSend([
            'campaign_id'          => $this->campaign?->id ?? 0,
            'email'                => $user->email,
            'name'                 => $user->name,
            'tracking_pixel_token' => \Illuminate\Support\Str::uuid()->toString(),
        ]);

        $tempCampaign = new EmailCampaign([
            'subject'    => '[TESTE] ' . $this->subject,
            'from_name'  => $this->fromName,
            'from_email' => $this->fromEmail,
            'body_html'  => $this->bodyHtml,
        ]);
        $tempCampaign->id = $this->campaign?->id ?? 0;

        Mail::to($user->email, $user->name)->send(new CampaignMail($tempCampaign, $tempSend));

        session()->flash('success', 'E-mail de teste enviado para ' . $user->email);
    }

    public function refreshEstimate(): void
    {
        $this->estimatedRecipients = match ($this->recipientType) {
            'all_subscribers'  => NewsletterSubscriber::where('is_active', true)->count(),
            'customers'        => CustomerProfile::where('marketing_opt_in', true)->count(),
            'customers_active' => CustomerProfile::where('marketing_opt_in', true)
                ->whereHas('user.orders', fn ($q) => $q->where('created_at', '>=', now()->subDays(60)))
                ->count(),
            'segment_manual'   => count(array_filter(array_map('trim', explode("\n", $this->recipientEmailsManual)))),
            default            => 0,
        };
    }

    public function templates(): array
    {
        $green = '#1a472a';
        $gold  = '#F4E294';

        return [
            'newsletter' => [
                'label' => 'Newsletter Mensal',
                'html'  => "<h2 style=\"color:{$green}; margin-top:0;\">Novidades da Feira</h2><p>Olá, {{nome}}!</p><p>Confira as novidades deste mês:</p><ul><li>Item 1</li><li>Item 2</li></ul><p><a href=\"" . url('/') . "\" style=\"background:{$green};color:{$gold};padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:700;\">Visitar a Feira</a></p>",
            ],
            'evento' => [
                'label' => 'Novo Evento',
                'html'  => "<h2 style=\"color:{$green}; margin-top:0;\">🎪 Novo Evento!</h2><p>Olá, {{nome}}!</p><p>Temos um novo evento chegando. Marque na agenda:</p><p><strong>Data:</strong> [data]<br><strong>Local:</strong> [local]</p><p><a href=\"" . url('/agenda') . "\" style=\"background:{$green};color:{$gold};padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:700;\">Ver Detalhes</a></p>",
            ],
            'promocao' => [
                'label' => 'Promoção de Produto',
                'html'  => "<h2 style=\"color:{$green}; margin-top:0;\">✨ Produto em Destaque</h2><p>Olá, {{nome}}!</p><p>[Descrição do produto]</p><p><strong>Por apenas R$ [preço]</strong></p><p><a href=\"" . url('/produtos') . "\" style=\"background:{$green};color:{$gold};padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:700;\">Ver Produto</a></p>",
            ],
            'boas_vindas' => [
                'label' => 'Boas-Vindas',
                'html'  => "<h2 style=\"color:{$green}; margin-top:0;\">Bem-vind@ à Feira! 🌱</h2><p>Olá, {{nome}}!</p><p>Que alegria ter você aqui. A Feira Esquerda Livre é um espaço de arte, cultura e economia solidária.</p><p><a href=\"" . url('/') . "\" style=\"background:{$green};color:{$gold};padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:700;\">Explorar a Feira</a></p>",
            ],
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.email-marketing.campaign-form', [
            'recipientTypes' => RecipientType::cases(),
            'templates'      => $this->templates(),
        ])->layout('admin.layouts.app', [
            'title' => $this->campaign ? 'Editar Campanha' : 'Nova Campanha',
        ]);
    }
}
