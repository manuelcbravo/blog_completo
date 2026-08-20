<?php

namespace App\Mail;

use App\Models\Publicacion;
use App\Models\Suscriptor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NuevaPublicacion extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Publicacion $publicacion,
        public Suscriptor $suscriptor,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->publicacion->titulo);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.nueva-publicacion',
            with: [
                'titulo' => $this->publicacion->titulo,
                'resumen' => $this->publicacion->resumen,
                'tipo' => $this->publicacion->tipo()->etiqueta(),
                'url' => $this->publicacion->urlPublica(),
                'nombre' => $this->suscriptor->nombre,
                'urlBaja' => route('suscripcion.baja', ['token' => $this->suscriptor->token]),
            ],
        );
    }
}
