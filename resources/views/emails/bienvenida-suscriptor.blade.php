<x-mail::message>
# Suscripción confirmada

@if ($nombre)
Hola {{ $nombre }},
@else
Hola,
@endif

Ya estás en la lista. Te aviso cada vez que publique algo nuevo.

<x-mail::button :url="$sitio">
Ir al blog
</x-mail::button>

Si algún día quieres dejar de recibirlos, puedes [darte de baja]({{ $urlBaja }}).

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
