<?php

namespace App\Mail;

use App\Models\LojistasSolicitacao;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LojistaSolicitacaoRecebida extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly LojistasSolicitacao $solicitacao,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recebemos sua solicitação — Feira Esquerda Livre',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lojista-solicitacao-recebida',
        );
    }
}
