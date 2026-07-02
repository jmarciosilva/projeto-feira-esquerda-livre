<?php

namespace App\Mail;

use App\Models\Ava\AvaEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class AvaCertificateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly AvaEnrollment $enrollment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Parabéns! Seu certificado está aqui — ' . $this->enrollment->course->product->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ava.certificate',
        );
    }

    public function attachments(): array
    {
        if (! $this->enrollment->certificate_path) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('local', $this->enrollment->certificate_path)
                ->as('Certificado — ' . $this->enrollment->course->product->name . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
