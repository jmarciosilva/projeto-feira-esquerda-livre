<?php

namespace App\Mail;

use App\Models\Ava\AvaEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AvaEnrollmentConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly AvaEnrollment $enrollment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sua matrícula foi confirmada — ' . $this->enrollment->course->product->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ava.enrollment-confirmed',
        );
    }
}
