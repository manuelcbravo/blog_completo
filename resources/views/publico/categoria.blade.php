<x-publico.layout
    :titulo="$categoria->nombre"
    :descripcion="$categoria->descripcion"
>
    <div class="contenedor">
        <section class="seccion">
            <span class="rotulo rotulo--acento">Categoría</span>
            <h1>{{ $categoria->nombre }}</h1>
            <p>
                {{ $categoria->descripcion ?: 'Todo lo publicado bajo esta categoría.' }}
                {{ $publicaciones->total() }} {{ $publicaciones->total() === 1 ? 'artículo' : 'artículos' }}.
            </p>
        </section>

        @if ($publicaciones->isEmpty())
            <p class="vacio">Todavía no hay artículos en esta categoría.</p>
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
