<x-mail::message>
# Respuesta a tu mensaje

Hola {{ $nombre }},

Sobre lo que nos escribiste:

<x-mail::panel>
{{ $original }}
</x-mail::panel>

Mi respuesta:

<x-mail::panel>
{{ $respuesta }}
</x-mail::panel>

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
