<?php

namespace App\Mail;

use App\Models\Contacto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AcuseContacto extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Contacto $contacto) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Recibí tu mensaje');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.acuse-contacto',
            with: [
                'nombre' => $this->contacto->name,
                'mensaje' => $this->contacto->message,
                'sitio' => rtrim((string) config('blog.sitio_url'), '/'),
            ],
        );
    }
}
