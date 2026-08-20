<?php

namespace App\Mail;

use App\Models\Suscriptor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConfirmacionSuscripcion extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Suscriptor $suscriptor) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirma tu suscripción');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.confirmacion-suscripcion',
            with: [
                'nombre' => $this->suscriptor->nombre,
                'url' => route('suscripcion.confirmar', ['token' => $this->suscriptor->token]),
            ],
        );
    }
}
