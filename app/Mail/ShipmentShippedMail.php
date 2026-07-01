<?php

namespace App\Mail;

use App\Models\OrderShipping;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShipmentShippedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly OrderShipping $shipping,
    ) {}

    public function envelope(): Envelope
    {
        $storeName = $this->shipping->expositor?->name ?? 'a loja';
        $reference = $this->shipping->order?->reference ?? '';

        return new Envelope(
            subject: "Seu pedido #{$reference} foi enviado por {$storeName}!",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shipment-shipped',
        );
    }
}
