@props(['nombre'])

@php
    /*
     * Bootstrap Icons, el mismo juego que usa laravelconmanuel.dev.
     * El mapa traduce el nombre semántico que usan las vistas al de la fuente,
     * para que un cambio de icono se haga aquí y no repartido por el markup.
     */
    $iconos = [
        'experiencia' => 'briefcase',
        'equipo' => 'people-fill',
        'modalidad' => 'geo-alt',
        'escribo' => 'pencil-square',
        'ubicacion' => 'geo-alt',
        'telefono' => 'telephone',
        'whatsapp' => 'whatsapp',
        'desplegar' => 'chevron-down',
        'correo' => 'envelope',
        'github' => 'github',
        'linkedin' => 'linkedin',
        'check' => 'patch-check-fill',
        'flecha' => 'arrow-right',
        'enviar' => 'send',
        'servidor' => 'hdd-stack',
        'ventana' => 'window-stack',
        'base-datos' => 'hdd-rack',
        'capas' => 'layers',
        'enchufe' => 'plug',
        'chispa' => 'stars',
        'nube' => 'cloud-check',
        'grafica' => 'bar-chart-line',
        'mapa' => 'map',
        'escudo' => 'shield-lock',
    ];
@endphp

<i {{ $attributes->class(['icono', 'bi', 'bi-'.($iconos[$nombre] ?? $nombre)]) }} aria-hidden="true"></i>
