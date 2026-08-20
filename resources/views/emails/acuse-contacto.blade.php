<x-mail::message>
# Recibí tu mensaje

Hola {{ $nombre }},

Gracias por escribir. Este es el mensaje que me llegó:

<x-mail::panel>
{{ $mensaje }}
</x-mail::panel>

Lo reviso y te respondo en menos de 24 horas.

<x-mail::button :url="$sitio">
Ir al sitio
</x-mail::button>

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
