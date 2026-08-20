<x-publico.layout
    :titulo="$publicacion->meta_titulo ?: $publicacion->titulo"
    :descripcion="$publicacion->meta_descripcion ?: $publicacion->resumen"
    og-tipo="article"
    :og-imagen="$publicacion->imagenUrl()"
    :publicacion="$publicacion"
    :migas="array_values(array_filter([
        ['nombre' => 'Inicio', 'url' => route('home')],
        $publicacion->categoria
            ? ['nombre' => $publicacion->categoria->nombre, 'url' => route('publico.categoria', $publicacion->categoria->slug)]
            : null,
        ['nombre' => $publicacion->titulo, 'url' => url()->current()],
    ]))"
>

    <div class="contenedor">
        <article class="articulo">
            <div class="articulo__migas">
                @if ($publicacion->categoria)
                    <a style="color: var(--accent);" href="{{ route('publico.categoria', $publicacion->categoria->slug) }}">
                        {{ mb_strtolower($publicacion->categoria->nombre) }}
                    </a>
                    <span>/</span>
                @endif
                <time datetime="{{ $publicacion->fecha_publicacion?->toDateString() }}">
                    {{ $publicacion->fecha_publicacion?->toDateString() }}
                </time>
                <span>· {{ $publicacion->tiempo_lectura }} min</span>
            </div>

            <h1>{{ $publicacion->titulo }}</h1>

            @if ($publicacion->resumen)
                <p class="articulo__entradilla">{{ $publicacion->resumen }}</p>
            @endif

            <div class="firma">
                <span class="firma__avatar"></span>
                <a href="{{ route('publico.autor') }}" style="color: var(--fg);">
                    {{ $publicacion->autor?->name ?? config('blog.sitio.autor.nombre') }}
                </a>
                <span>· {{ config('blog.sitio.autor.oficio') }}</span>
            </div>

            <x-publico.imagen :publicacion="$publicacion" variante="articulo" />

            @php
                [$cuerpoAntes, $cuerpoDespues] = \App\Support\Publico\CuerpoConAnuncio::partir($publicacion->contenido);
            @endphp

            <div class="prosa">
                {!! $cuerpoAntes !!}
            </div>

            @if ($cuerpoDespues !== '')
                <x-publico.anuncio formato="en-texto" class="anuncio--en-texto" />

                <div class="prosa">
                    {!! $cuerpoDespues !!}
                </div>
            @endif

            @if ($tipo->tieneDetalles() && $publicacion->detalles->isNotEmpty())
                <section style="display: flex; flex-direction: column; gap: 14px;">
                    <span class="rotulo rotulo--acento">Descargas</span>
                    <div class="descargas">
                        @foreach ($publicacion->detalles as $detalle)
                            <div class="descargas__fila">
                                <span>{{ $detalle->detalle }}</span>
                                @if ($detalle->recurso_url)
                                    <a class="meta" style="color: var(--accent);"
                                       href="{{ Storage::disk(config('blog.disco'))->url($detalle->recurso_url) }}"
                                       target="_blank" rel="noopener">descargar →</a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <x-publico.anuncio formato="articulo" />

            @if ($publicacion->etiquetas->isNotEmpty())
                <div class="etiquetas">
                    @foreach ($publicacion->etiquetas as $etiqueta)
                        <span>#{{ $etiqueta->nombre }}</span>
                    @endforeach
                </div>
            @endif
        </article>

        @include('publico.partials.comentarios')

        @if ($relacionadas->isNotEmpty())
            <div class="franja">
                <h2>Seguir leyendo</h2>
            </div>

            <x-publico.rejilla :items="$relacionadas">
                @foreach ($relacionadas as $relacionada)
                    <x-publico.tarjeta :publicacion="$relacionada" />
                @endforeach
            </x-publico.rejilla>
        @endif
    </div>

</x-publico.layout>
