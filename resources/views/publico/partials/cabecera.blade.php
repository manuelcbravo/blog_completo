@php
    $enlaces = [
        'inicio' => ['url' => route('home'), 'clave' => 'inicio'],
        'artículos' => ['url' => route('publico.articulos'), 'clave' => 'articulos'],
        'tutoriales' => ['url' => route('publico.tutoriales'), 'clave' => 'tutoriales'],
        'recursos' => ['url' => route('publico.recursos'), 'clave' => 'recursos'],
        'manuel' => ['url' => route('publico.autor'), 'clave' => 'autor'],
        'newsletter' => ['url' => route('publico.newsletter'), 'clave' => 'newsletter'],
    ];
@endphp

<header class="cabecera">
    <div class="cabecera__fila">
        <a class="marca" href="{{ route('home') }}" aria-label="{{ $marca }}">
            <img
                class="marca__lockup marca__lockup--oscuro"
                src="{{ asset('assets/img/logos/logo_dark.png') }}"
                alt=""
                width="800"
                height="122"
            >
            <img
                class="marca__lockup marca__lockup--claro"
                src="{{ asset('assets/img/logos/logo_light.png') }}"
                alt=""
                width="800"
                height="122"
            >
            <img
                class="marca__iso"
                src="{{ asset('assets/img/logos/logo_isotipo.png') }}"
                alt=""
                width="256"
                height="256"
            >
        </a>

        <nav class="nav" aria-label="Secciones">
            @foreach ($enlaces as $texto => $enlace)
                <a href="{{ $enlace['url'] }}"
                   @if (($seccion ?? null) === $enlace['clave']) aria-current="page" @endif>/{{ $texto }}</a>
            @endforeach
        </nav>

        <div class="acciones">
            <a class="btn-buscar" href="{{ route('publico.buscar') }}" data-ir-buscar>
                <span>buscar</span>
                <span class="kbd">⌘K</span>
            </a>
            <button type="button" class="btn-tema" data-tema-toggle aria-label="Cambiar tema">☀</button>
        </div>
    </div>
</header>
