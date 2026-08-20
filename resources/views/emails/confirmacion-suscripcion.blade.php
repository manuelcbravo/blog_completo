<x-mail::message>
# Confirma tu suscripción

@if ($nombre)
Hola {{ $nombre }},
@else
Hola,
@endif

Recibí una solicitud para suscribir este correo a las publicaciones del blog.
Confirma con el botón para empezar a recibirlas.

<x-mail::button :url="$url">
Confirmar suscripción
</x-mail::button>

Si no fuiste tú, ignora este correo y no pasará nada.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
