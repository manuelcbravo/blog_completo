<x-publico.layout seccion="inicio" :og-imagen="$destacada?->imagenUrl()">

    <div class="contenedor">

        @if ($destacada)
            <section class="hero">
                <div class="hero__cuerpo">
                    <div class="hero__meta">
                        @if ($destacada->categoria)
                            <a class="hero__categoria" href="{{ route('publico.categoria', $destacada->categoria->slug) }}">
                                {{ mb_strtolower($destacada->categoria->nombre) }}
                            </a>
                        @endif
                        <span style="color: var(--fg3);">
                            <time datetime="{{ $destacada->fecha_publicacion?->toDateString() }}">
                                {{ $destacada->fecha_publicacion?->toDateString() }}
                            </time>
                            · {{ $destacada->tiempo_lectura }} min
                        </span>
                    </div>

                    <h1 class="hero__titulo">
                        <a href="{{ $destacada->urlPublica() }}">{{ $destacada->titulo }}</a>
                    </h1>

                    @if ($destacada->resumen)
                        <p class="hero__entradilla">{{ $destacada->resumen }}</p>
                    @endif

                    <a href="{{ $destacada->urlPublica() }}" tabindex="-1" aria-hidden="true">
                        <x-publico.imagen :publicacion="$destacada" variante="hero" />
                    </a>
                </div>

                <aside class="aside">
                    <span class="rotulo" style="padding-bottom: 12px;">Más leído</span>

                    <div class="mas-leido">
                        @forelse ($masLeidas as $indice => $post)
                            <a href="{{ $post->urlPublica() }}">
                                <span class="mas-leido__n">{{ str_pad($indice + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                <span>{{ $post->titulo }}</span>
                            </a>
                        @empty
                            <p class="meta" style="padding: 13px 0; border-top: 1px solid var(--line);">
                                Aún no hay lecturas registradas.
                            </p>
                        @endforelse
                    </div>

                    <x-publico.anuncio formato="sidebar" style="margin-top: 28px;" />

                    <div class="caja">
                        <span class="rotulo rotulo--acento">Newsletter</span>
                        <p>Un correo cada viernes con lo que aprendí escribiendo código esa semana.</p>
                        <a class="boton boton--fino" href="{{ route('publico.newsletter') }}"
                           style="color: var(--on-accent);">Suscribirme →</a>
                    </div>
                </aside>
            </section>
        @else
            <section class="seccion">
                <span class="rotulo rotulo--acento">Bienvenido</span>
                <h1>Todavía no hay nada publicado</h1>
                <p>En cuanto salga el primer artículo aparecerá aquí.</p>
            </section>
        @endif

        @if ($recientes->isNotEmpty())
            <div class="franja">
                <h2>Últimos artículos</h2>
                <a href="{{ route('publico.articulos') }}">ver todos →</a>
            </div>

            <x-publico.rejilla :items="$recientes">
                @foreach ($recientes as $post)
                    <x-publico.tarjeta :publicacion="$post" />
                @endforeach
            </x-publico.rejilla>
        @endif

        @if ($ultimosTutoriales->isNotEmpty())
            <div class="franja">
                <h2>Tutoriales</h2>
                <a href="{{ route('publico.tutoriales') }}">ver todos →</a>
            </div>

            <x-publico.rejilla :items="$ultimosTutoriales">
                @foreach ($ultimosTutoriales as $tutorial)
                    <x-publico.tarjeta :publicacion="$tutorial" />
                @endforeach
            </x-publico.rejilla>
        @endif

        @if ($ultimosRecursos->isNotEmpty())
            <div class="franja">
                <h2>Recursos</h2>
                <a href="{{ route('publico.recursos') }}">ver todos →</a>
            </div>

            <x-publico.rejilla :items="$ultimosRecursos">
                @foreach ($ultimosRecursos as $recurso)
                    <x-publico.tarjeta :publicacion="$recurso" />
                @endforeach
            </x-publico.rejilla>
        @endif

    </div>

</x-publico.layout>
