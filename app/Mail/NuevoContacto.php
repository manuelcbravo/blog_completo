<?php

namespace App\Mail;

use App\Models\Contacto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NuevoContacto extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Contacto $contacto) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuevo mensaje de contacto: '.$this->contacto->name,
            replyTo: [new Address($this->contacto->email, $this->contacto->name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.nuevo-contacto',
            with: [
                'contacto' => $this->contacto,
                'url' => route('blog.contactos.index'),
            ],
        );
    }
}
