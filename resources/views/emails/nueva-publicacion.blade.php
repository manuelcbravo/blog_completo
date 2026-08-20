<x-mail::message>
# {{ $titulo }}

@if ($nombre)
Hola {{ $nombre }},
@else
Hola,
@endif

Publicamos un nuevo {{ mb_strtolower($tipo) }} en el blog.

@if ($resumen)
<x-mail::panel>
{{ $resumen }}
</x-mail::panel>
@endif

<x-mail::button :url="$url">
Leer la publicación
</x-mail::button>

Si ya no quieres recibir estos avisos, puedes [darte de baja]({{ $urlBaja }}).
</x-mail::message>
