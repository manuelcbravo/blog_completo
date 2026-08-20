<x-publico.layout
    :seccion="$tipo->segmento()"
    :titulo="$tipo->etiquetaSitio()"
    :descripcion="$tipo->descripcion()"
>
    <div class="contenedor">
        <section class="seccion">
            <span class="rotulo rotulo--acento">{{ $tipo->etiquetaSitio() }}</span>
            <h1>{{ $tipo->etiquetaSitio() }}</h1>
            <p>{{ $tipo->descripcion() }} {{ $publicaciones->total() }} {{ $publicaciones->total() === 1 ? 'publicada' : 'publicadas' }}.</p>
        </section>

        <x-publico.anuncio formato="leaderboard" style="padding: 24px 0 4px;" />

        @if ($publicaciones->isEmpty())
            <p class="vacio">Aún no hay nada publicado en esta sección.</p>
        @else
            <x-publico.rejilla :items="$publicaciones->getCollection()">
                @foreach ($publicaciones as $publicacion)
                    <x-publico.tarjeta :publicacion="$publicacion" />
                @endforeach
            </x-publico.rejilla>
        @endif

        <x-publico.paginacion :paginador="$publicaciones" />
    </div>
</x-publico.layout>
