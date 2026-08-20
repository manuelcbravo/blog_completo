<?php

namespace App\Mail;

use App\Models\Comentario;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RespuestaComentario extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Comentario $comentario,
        public string $respuesta,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Respondí a tu comentario');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.respuesta-comentario',
            with: [
                'nombre' => $this->comentario->nombre,
                'original' => $this->comentario->contenido,
                'respuesta' => $this->respuesta,
                'sitio' => rtrim((string) config('blog.sitio_url'), '/'),
            ],
        );
    }
}
