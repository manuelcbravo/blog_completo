<?php

namespace App\Mail;

use App\Models\Comentario;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NuevoComentario extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Comentario $comentario,
        public string $tituloPublicacion,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nuevo comentario en '.$this->tituloPublicacion);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.nuevo-comentario',
            with: [
                'comentario' => $this->comentario,
                'titulo' => $this->tituloPublicacion,
                'url' => route('blog.comentarios.index'),
            ],
        );
    }
}
