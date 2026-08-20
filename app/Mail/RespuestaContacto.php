<?php

namespace App\Mail;

use App\Models\Contacto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RespuestaContacto extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Contacto $contacto,
        public string $respuesta,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Respuesta a tu mensaje');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.respuesta-contacto',
            with: [
                'nombre' => $this->contacto->name,
                'original' => $this->contacto->message,
                'respuesta' => $this->respuesta,
            ],
        );
    }
}
