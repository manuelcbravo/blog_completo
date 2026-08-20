<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PruebaCorreo extends Mailable
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Prueba de correo de '.config('app.name'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.prueba',
            with: [
                'mailer' => config('mail.default'),
                'remitente' => config('mail.from.address'),
            ],
        );
    }
}
