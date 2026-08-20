<x-mail::message>
# Nuevo mensaje de contacto

**{{ $contacto->name }}** ({{ $contacto->email }}) escribió desde el sitio:

<x-mail::panel>
{{ $contacto->message }}
</x-mail::panel>

IP: {{ $contacto->ip_address ?? 'no registrada' }}

<x-mail::button :url="$url">
Ver en la plataforma
</x-mail::button>
</x-mail::message>
