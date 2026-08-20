<x-mail::message>
# Prueba de correo

Si estás leyendo esto, la configuración de correo funciona.

- Driver: **{{ $mailer }}**
- Remitente: **{{ $remitente }}**
- Fecha: **{{ now()->format('d/m/Y H:i') }}**

{{ config('app.name') }}
</x-mail::message>
