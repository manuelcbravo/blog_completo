<x-mail::message>
# Respondí tu comentario

Hola {{ $nombre }},

Tu comentario:

<x-mail::panel>
{{ $original }}
</x-mail::panel>

Mi respuesta:

<x-mail::panel>
{{ $respuesta }}
</x-mail::panel>

<x-mail::button :url="$sitio">
Ir al blog
</x-mail::button>

Gracias por participar,<br>
{{ config('app.name') }}
</x-mail::message>
