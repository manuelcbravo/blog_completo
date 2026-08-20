@php
    $autor = config('blog.sitio.autor');
    $nombreCompleto = $ficha['nombre'].' '.$ficha['apellidos'];
@endphp

<x-publico.layout
    seccion="autor"
    :titulo="$nombreCompleto.' — Senior Full-Stack Developer'"
    :descripcion="$ficha['titular']"
>
    <div class="contenedor">

        <section class="perfil">
            <div class="perfil__retrato">
                @if ($autor['avatar'])
                    <div class="imagen-marco imagen-marco--retrato">
                        <img src="{{ $autor['avatar'] }}" alt="{{ $nombreCompleto }}">
                    </div>
                @else
                    <div class="marcador marcador--retrato" aria-hidden="true"><span>retrato 4:5</span></div>
                @endif

                @if ($ficha['disponible'])
                    <p class="disponible">
                        <span class="disponible__punto" aria-hidden="true"></span>
                        Disponible para proyectos
                    </p>
                @endif
            </div>

            <div class="perfil__cuerpo">
                <span class="rotulo">{{ $ficha['titulo'] }}</span>

                <h1 class="perfil__nombre">{{ $ficha['nombre'] }}<br>{{ $ficha['apellidos'] }}</h1>

                <p class="perfil__titular">{{ $ficha['titular'] }}</p>

                <p class="perfil__resumen">{{ $ficha['resumen'] }}</p>

                <dl class="ficha">
                    <div>
                        <dt><x-publico.icono nombre="experiencia" /> experiencia</dt>
                        <dd>{{ $ficha['experiencia'] }}</dd>
                    </div>
                    <div>
                        <dt><x-publico.icono nombre="modalidad" /> modalidad</dt>
                        <dd>{{ $ficha['modalidad'] }}</dd>
                    </div>
                    <div>
                        <dt><x-publico.icono nombre="escribo" /> escribo</dt>
                        <dd>{{ $totalPublicaciones }} publicaciones</dd>
                    </div>
                </dl>

                <div class="perfil__acciones">
                    <a class="boton boton--icono" href="#contacto">
                        Hablemos de tu proyecto <x-publico.icono nombre="flecha" />
                    </a>

                    <div class="enlaces">
                        @foreach ($ficha['enlaces'] as $enlace)
                            <a href="{{ $enlace['url'] }}" rel="me noopener" target="_blank">
                                <x-publico.icono :nombre="$enlace['icono']" /> {{ $enlace['etiqueta'] }}
                            </a>
                        @endforeach
                        <a href="mailto:{{ $autor['correo'] }}">
                            <x-publico.icono nombre="correo" /> Correo
                        </a>
                        <details class="menu-tel" data-menu>
                            <summary>
                                <x-publico.icono nombre="telefono" /> {{ $ficha['telefono'] }}
                                <x-publico.icono nombre="desplegar" class="menu-tel__flecha" />
                            </summary>

                            <div class="menu-tel__panel">
                                <a href="tel:{{ $ficha['telefono_e164'] }}">
                                    <x-publico.icono nombre="telefono" /> Llamar
                                </a>
                                <a href="https://wa.me/{{ ltrim($ficha['telefono_e164'], '+') }}" rel="noopener" target="_blank">
                                    <x-publico.icono nombre="whatsapp" /> Enviar un WhatsApp
                                </a>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        </section>

        <section class="bloque">
            <h2 class="titulo-seccion">Dónde he trabajado</h2>

            <div class="log">
                @foreach ($ficha['trayectoria'] as $indice => $puesto)
                    <article @class(['log__entrada', 'log__entrada--actual' => $indice === 0])>
                        <div class="log__rail">
                            <span class="log__punto" aria-hidden="true"></span>
                            <span class="log__periodo">{{ $puesto['periodo'] }}</span>
                            @if ($indice === 0)
                                <span class="log__marca">aquí estoy</span>
                            @endif
                        </div>

                        <div class="log__cuerpo">
                            <h3>{{ $puesto['puesto'] }}</h3>
                            <span class="categoria">{{ $puesto['empresa'] }} · {{ $puesto['lugar'] }}</span>
                            <p>{{ $puesto['resumen'] }}</p>
                            <ul class="logros">
                                @foreach ($puesto['logros'] as $logro)
                                    <li>{{ $logro }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="bloque">
            <h2 class="titulo-seccion">Con qué trabajo</h2>

            <p class="bloque__intro">
                Mi terreno diario es @foreach ($ficha['especialidad'] as $tecnologia)<strong>{{ $tecnologia }}</strong>{{ $loop->last ? '.' : ', ' }}@endforeach
                Lo demás lo uso según lo pida el proyecto.
            </p>

            <div class="aptitudes">
                @foreach ($ficha['aptitudes'] as $grupo)
                    <div class="aptitud">
                        <span class="aptitud__grupo">
                            <x-publico.icono :nombre="$grupo['icono']" class="aptitud__icono" />
                            {{ $grupo['grupo'] }}
                        </span>
                        <div class="tecnologias">
                            @foreach ($grupo['items'] as $item)
                                <x-publico.tecnologia
                                    :nombre="$item"
                                    :destacada="($grupo['destacado'] ?? false) || in_array($item, $ficha['destacadas'], true)"
                                    :acento="in_array($item, $ficha['acento'] ?? [], true)"
                                />
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="bloque">
            <div class="credenciales">
                <div>
                    <h2 class="titulo-seccion titulo-seccion--menor">Formación</h2>

                    @foreach ($ficha['educacion'] as $estudio)
                        <article class="tarjeta-plana">
                            <span class="meta">{{ $estudio['periodo'] }} · {{ $estudio['lugar'] }}</span>
                            <h3>{{ $estudio['titulo'] }}</h3>
                            <span class="categoria">{{ $estudio['institucion'] }}</span>
                        </article>
                    @endforeach

                    <article class="tarjeta-plana">
                        <span class="meta">Idiomas</span>
                        <h3>{{ $ficha['idiomas'] }}</h3>
                    </article>
                </div>

                <div>
                    <h2 class="titulo-seccion titulo-seccion--menor">Certificaciones</h2>

                    @foreach ($ficha['certificaciones'] as $certificacion)
                        <article class="tarjeta-plana tarjeta-plana--check">
                            <x-publico.icono nombre="check" class="tarjeta-plana__icono" />
                            <div>
                                <h3>{{ $certificacion['nombre'] }}</h3>
                                <span class="categoria">{{ $certificacion['emisor'] }}</span>
                                <p>{{ $certificacion['detalle'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="bloque">
            <h2 class="titulo-seccion">Además del código</h2>
            <div class="pildoras">
                @foreach ($ficha['habilidades'] as $habilidad)
                    <span>{{ $habilidad }}</span>
                @endforeach
            </div>
        </section>

        <section class="bloque bloque--final" id="contacto">
            <div class="contacto">
                <div>
                    <span class="rotulo rotulo--acento">Contacto</span>
                    <h2 class="contacto__titulo">¿Tienes un proyecto?</h2>
                    <p class="contacto__texto">
                        Cuéntame qué necesitas construir. Respondo en menos de 24 horas,
                        y si no soy la persona indicada te lo digo de inmediato.
                    </p>
                    <ul class="datos-contacto">
                        <li><x-publico.icono nombre="telefono" /> {{ $ficha['telefono'] }}</li>
                        <li><x-publico.icono nombre="correo" /> {{ $autor['correo'] }}</li>
                        <li><x-publico.icono nombre="ubicacion" /> {{ $ficha['ubicacion'] }}</li>
                    </ul>
                </div>

                @if (session('contacto'))
                    <p class="aviso">{{ session('contacto') }}</p>
                @else
                    <form class="formulario formulario--panel" method="POST" action="{{ route('publico.contactar') }}">
                        @csrf

                        <label class="trampa" aria-hidden="true">
                            Deja este campo vacío
                            <input type="text" name="sitio_web" tabindex="-1" autocomplete="off">
                        </label>

                        <div class="formulario__linea">
                            <input class="campo" type="text" name="nombre" value="{{ old('nombre') }}"
                                   placeholder="tu nombre" aria-label="Tu nombre" required>
                            <input class="campo" type="email" name="email" value="{{ old('email') }}"
                                   placeholder="tu@correo.com" aria-label="Tu correo" required>
                        </div>

                        <textarea class="campo" name="mensaje" rows="5" aria-label="Tu mensaje"
                                  placeholder="cuéntame del proyecto" required>{{ old('mensaje') }}</textarea>

                        @foreach (['nombre', 'email', 'mensaje', 'sitio_web'] as $campo)
                            @error($campo)
                                <span class="error">{{ $message }}</span>
                            @enderror
                        @endforeach

                        <button class="boton boton--icono" type="submit">
                            Enviar mensaje <x-publico.icono nombre="flecha" />
                        </button>
                    </form>
                @endif
            </div>
        </section>

    </div>
</x-publico.layout>
