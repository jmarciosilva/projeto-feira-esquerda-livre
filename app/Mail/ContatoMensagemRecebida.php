<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContatoMensagemRecebida extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $dados,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [$this->dados['email']],
            subject: 'Nova mensagem de contato - Feira Esquerda Livre',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contato-mensagem-recebida',
        );
    }
}
