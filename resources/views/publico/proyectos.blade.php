<x-publico.layout
    seccion="proyectos"
    :titulo="$proyectos['titulo'].' — laravelconmanuel'"
    :descripcion="$proyectos['intro']"
    :noindex="true"
>
    <div class="contenedor">

        <section class="bloque">
            <header class="proyectos-intro">
                <p class="proyectos-intro__kicker">/proyectos</p>
                <h1 class="proyectos-intro__titulo">{{ $proyectos['titulo'] }}</h1>
                <p class="proyectos-intro__texto">{{ $proyectos['intro'] }}</p>
                <p class="proyectos-intro__nota">
                    <x-publico.icono nombre="escudo" />
                    {{ $proyectos['nota'] }}
                </p>
            </header>
        </section>

        @foreach ($proyectos['grupos'] as $grupo)
            <section class="bloque">
                <div class="proyectos-grupo">
                    <span class="aptitud__grupo">
                        <x-publico.icono :nombre="$grupo['icono']" class="aptitud__icono" />
                        {{ $grupo['grupo'] }}
                    </span>
                    @if (! empty($grupo['resumen']))
                        <p class="proyectos-grupo__resumen">{{ $grupo['resumen'] }}</p>
                    @endif
                </div>

                <div class="proyectos-lista">
                    @foreach ($grupo['proyectos'] as $proyecto)
                        <article @class(['proyecto', 'proyecto--acento' => ! empty($proyecto['acento'])])>
                            <header class="proyecto__cabecera">
                                <div class="proyecto__titulos">
                                    <h2 class="proyecto__nombre">{{ $proyecto['nombre'] }}</h2>
                                    <p class="proyecto__tipo">{{ $proyecto['tipo'] }}</p>
                                </div>

                                @if (! empty($proyecto['url']))
                                    <a class="proyecto__enlace" href="{{ $proyecto['url'] }}" target="_blank" rel="noopener noreferrer">
                                        {{ \Illuminate\Support\Str::after($proyecto['url'], '://') }}
                                        <x-publico.icono nombre="flecha" />
                                    </a>
                                @else
                                    <span class="proyecto__enlace proyecto__enlace--privado">
                                        <x-publico.icono nombre="escudo" /> red privada
                                    </span>
                                @endif
                            </header>

                            <p class="proyecto__resumen">{{ $proyecto['resumen'] }}</p>

                            @if (! empty($proyecto['detalles']))
                                <ul class="proyecto__detalles">
                                    @foreach ($proyecto['detalles'] as $detalle)
                                        <li>{{ $detalle }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="tecnologias proyecto__stack">
                                @foreach ($proyecto['stack'] as $tech)
                                    <x-publico.tecnologia :nombre="$tech" />
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach

    </div>
</x-publico.layout>
