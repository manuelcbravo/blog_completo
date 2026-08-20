<x-mail::message>
# Nuevo comentario

**{{ $comentario->nombre }}** ({{ $comentario->correo }}) comentó en *{{ $titulo }}*:

<x-mail::panel>
{{ $comentario->contenido }}
</x-mail::panel>

Estado actual: **{{ $comentario->estado->label() }}**

<x-mail::button :url="$url">
Moderar comentarios
</x-mail::button>
</x-mail::message>
