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

                {{-- Un grupo con una sola tarjeta se centra: en la rejilla de dos
                     columnas se vería huérfano, con medio renglón vacío al lado. --}}
                <div @class(['proyectos-lista', 'proyectos-lista--unica' => count($grupo['proyectos']) === 1])>
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
                                @elseif (! empty($proyecto['prueba']))
                                    {{-- No es un sitio que se visite: se prueba conversando. El botón va abajo. --}}
                                    <span class="proyecto__enlace proyecto__enlace--demo">
                                        <x-publico.icono nombre="whatsapp" /> demo en vivo
                                    </span>
                                @elseif (! empty($proyecto['descarga']['url']))
                                    {{-- Tampoco es un sitio: se instala. El botón de descarga va abajo. --}}
                                    <span class="proyecto__enlace proyecto__enlace--demo">
                                        <x-publico.icono nombre="android" /> APK directo
                                    </span>
                                @else
                                    <span class="proyecto__enlace proyecto__enlace--privado">
                                        <x-publico.icono nombre="escudo" /> red privada
                                    </span>
                                @endif
                            </header>

                            <p class="proyecto__resumen">{{ $proyecto['resumen'] }}</p>

                            {{-- Cuenta de demostración de la plataforma. Los valores vienen
                                 del .env; si están vacíos, el bloque no se pinta. --}}
                            {{-- Ojo: aquí NO se puede usar el `@php(...)` de una línea. Blade
                                 empareja `@php ... @endphp` con una expresión perezosa, así que
                                 un `@php(` suelto antes del bloque de abajo se casa con SU
                                 `@endphp` y se traga todo lo que hay en medio como PHP crudo. --}}
                            @if (! empty($proyecto['acceso']['clave']))
                                <div class="proyecto__acceso">
                                    <x-publico.icono nombre="llave" class="proyecto__acceso-icono" />
                                    <div class="proyecto__acceso-cuerpo">
                                        <span class="proyecto__acceso-titulo">Entra a verlo</span>
                                        <dl class="proyecto__acceso-datos">
                                            <dt>usuario</dt>
                                            <dd>{{ $proyecto['acceso']['usuario'] }}</dd>
                                            <dt>clave</dt>
                                            <dd>{{ $proyecto['acceso']['clave'] }}</dd>
                                        </dl>
                                        <p class="proyecto__acceso-nota">{{ $proyecto['acceso']['nota'] }}</p>
                                    </div>
                                </div>
                            @endif

                            @if (! empty($proyecto['detalles']))
                                @php
                                    /*
                                     * Las fichas son largas a propósito, pero de golpe son un muro.
                                     * Se ven los primeros puntos y el resto queda en un <details>:
                                     * acordeón nativo, sin JavaScript, y el buscador sí lee dentro.
                                     * No se parte si lo que quedaría escondido es un solo punto.
                                     */
                                    $tope = config('proyectos.detalles_visibles', 3);
                                    $detalles = collect($proyecto['detalles']);
                                    $parte = $detalles->count() > $tope + 1;
                                    $visibles = $parte ? $detalles->take($tope) : $detalles;
                                    $ocultos = $parte ? $detalles->slice($tope) : collect();
                                @endphp

                                <ul class="proyecto__detalles">
                                    @foreach ($visibles as $detalle)
                                        <li>{{ $detalle }}</li>
                                    @endforeach
                                </ul>

                                @if ($ocultos->isNotEmpty())
                                    <details class="proyecto__mas">
                                        <summary class="proyecto__mas-boton">
                                            <x-publico.icono nombre="desplegar" class="proyecto__mas-icono" />
                                            <span class="proyecto__mas-abrir">Ver {{ $ocultos->count() }} detalles más</span>
                                            <span class="proyecto__mas-cerrar">Ver menos</span>
                                        </summary>

                                        <ul class="proyecto__detalles proyecto__detalles--extra">
                                            @foreach ($ocultos as $detalle)
                                                <li>{{ $detalle }}</li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif
                            @endif

                            @if (! empty($proyecto['prueba']))
                                @php($prueba = $proyecto['prueba'])
                                <a
                                    class="proyecto__accion"
                                    href="https://wa.me/{{ $prueba['e164'] }}?text={{ rawurlencode($prueba['texto']) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <x-publico.icono nombre="whatsapp" class="proyecto__accion-icono" />
                                    <span class="proyecto__accion-texto">
                                        <strong>{{ $prueba['etiqueta'] }}</strong>
                                        <small>{{ $prueba['numero'] }} · {{ $prueba['nota'] }}</small>
                                    </span>
                                    <x-publico.icono nombre="flecha" class="proyecto__accion-flecha" />
                                </a>
                            @endif

                            @if (! empty($proyecto['descarga']['url']))
                                @php($descarga = $proyecto['descarga'])
                                <a
                                    class="proyecto__accion proyecto__accion--descarga"
                                    href="{{ $descarga['url'] }}"
                                    download
                                    rel="noopener noreferrer"
                                >
                                    <x-publico.icono nombre="descargar" class="proyecto__accion-icono" />
                                    <span class="proyecto__accion-texto">
                                        <strong>{{ $descarga['etiqueta'] }}</strong>
                                        <small>{{ $descarga['nota'] }}</small>
                                    </span>
                                    <x-publico.icono nombre="flecha" class="proyecto__accion-flecha" />
                                </a>
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
