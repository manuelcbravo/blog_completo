@props(['publicacion'])

<article class="fila">
    <span class="meta">
        <time datetime="{{ $publicacion->fecha_publicacion?->toDateString() }}">
            {{ $publicacion->fecha_publicacion?->toDateString() }}
        </time>
    </span>

    <div class="fila__cuerpo">
        @if ($publicacion->categoria)
            <a class="categoria" href="{{ route('publico.categoria', $publicacion->categoria->slug) }}">
                {{ mb_strtolower($publicacion->categoria->nombre) }}
            </a>
        @endif

        <h3><a href="{{ $publicacion->urlPublica() }}">{{ $publicacion->titulo }}</a></h3>

        @if ($publicacion->resumen)
            <p>{{ $publicacion->resumen }}</p>
        @endif
    </div>

    <span class="fila__derecha">
        {{ $publicacion->tiempo_lectura }} min<br>
        {{ $publicacion->visitas }} {{ $publicacion->visitas === 1 ? 'lectura' : 'lecturas' }}
    </span>
</article>
