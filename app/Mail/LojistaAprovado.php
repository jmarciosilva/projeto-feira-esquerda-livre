<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LojistaAprovado extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User   $user,
        public readonly string $senha,
        public readonly string $nomeLoja,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sua solicitação foi aprovada — Feira Esquerda Livre',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lojista-aprovado',
        );
    }
}
