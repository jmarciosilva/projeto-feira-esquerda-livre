<?php

namespace App\Mail;

use App\Models\EmailCampaign;
use App\Models\EmailCampaignSend;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly EmailCampaign $campaign,
        public readonly EmailCampaignSend $send,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                $this->campaign->from_email,
                $this->campaign->from_name,
            ),
            subject: $this->campaign->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign',
            with: [
                'bodyHtml'       => $this->personalizeBody($this->campaign->body_html),
                'pixelToken'     => $this->send->tracking_pixel_token,
                'unsubscribeUrl' => $this->unsubscribeUrl(),
            ],
        );
    }

    private function personalizeBody(string $html): string
    {
        $name  = $this->send->name ?? 'amig@';
        $email = $this->send->email;

        return str_replace(['{{nome}}', '{{email}}'], [$name, $email], $html);
    }

    private function unsubscribeUrl(): string
    {
        $secret = config('app.marketing_unsubscribe_secret', config('app.key'));
        $token  = hash_hmac('sha256', $this->send->email . '|' . $this->campaign->id, $secret);

        return url('/newsletter/descadastro/' . $token . '?email=' . urlencode($this->send->email) . '&campaign=' . $this->campaign->id);
    }
}
