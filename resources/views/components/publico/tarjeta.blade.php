@props(['publicacion'])

<article class="tarjeta">
    <a href="{{ $publicacion->urlPublica() }}" tabindex="-1" aria-hidden="true">
        <x-publico.imagen :publicacion="$publicacion" variante="16x10" />
    </a>

    @if ($publicacion->categoria)
        <a class="categoria" href="{{ route('publico.categoria', $publicacion->categoria->slug) }}">
            {{ mb_strtolower($publicacion->categoria->nombre) }}
        </a>
    @else
        <span class="categoria">{{ mb_strtolower($publicacion->tipo()->etiqueta()) }}</span>
    @endif

    <h3><a href="{{ $publicacion->urlPublica() }}">{{ $publicacion->titulo }}</a></h3>

    @if ($publicacion->resumen)
        <p>{{ $publicacion->resumen }}</p>
    @endif

    <span class="tarjeta__pie">
        {{ $publicacion->autor?->name ?? config('blog.sitio.autor.nombre') }} ·
        {{ $publicacion->tiempo_lectura }} min
    </span>
</article>
