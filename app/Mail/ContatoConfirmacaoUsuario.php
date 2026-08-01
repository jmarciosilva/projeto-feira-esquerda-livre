<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContatoConfirmacaoUsuario extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $dados,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recebemos sua mensagem - Feira Esquerda Livre',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contato-confirmacao-usuario',
        );
    }
}
