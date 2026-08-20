<?php

namespace App\Mail;

use App\Models\Suscriptor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BienvenidaSuscriptor extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Suscriptor $suscriptor) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Suscripción confirmada');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bienvenida-suscriptor',
            with: [
                'nombre' => $this->suscriptor->nombre,
                'sitio' => rtrim((string) config('blog.sitio_url'), '/'),
                'urlBaja' => route('suscripcion.baja', ['token' => $this->suscriptor->token]),
            ],
        );
    }
}
