<x-publico.layout titulo="Buscar">
    <div class="contenedor">
        <div class="buscador">
            <form class="buscador__campo" method="GET" action="{{ route('publico.buscar') }}" role="search">
                <span class="buscador__prefijo" aria-hidden="true">/</span>
                <input
                    class="buscador__input"
                    type="search"
                    name="q"
                    value="{{ $termino }}"
                    placeholder="busca por título, resumen o slug…"
                    aria-label="Buscar en el blog"
                    data-buscador
                >
                <span class="meta">
                    @if ($termino !== '')
                        {{ $resultados->count() }} {{ $resultados->count() === 1 ? 'resultado' : 'resultados' }}
                    @else
                        escribe y pulsa enter
                    @endif
                </span>
            </form>

            @if ($sugerencias->isNotEmpty())
                <div class="pildoras">
                    @foreach ($sugerencias as $sugerencia)
                        <a href="{{ route('publico.buscar', ['q' => $sugerencia->nombre]) }}">{{ $sugerencia->nombre }}</a>
                    @endforeach
                </div>
            @endif

            @foreach ($resultados as $resultado)
                <a class="resultado" href="{{ $resultado->urlPublica() }}">
                    <span class="categoria">
                        {{ $resultado->tipo()->etiqueta() }}
                        @if ($resultado->categoria) · {{ $resultado->categoria->nombre }} @endif
                        · {{ $resultado->fecha_publicacion?->toDateString() }}
                    </span>
                    <span class="resultado__titulo">{{ $resultado->titulo }}</span>
                    @if ($resultado->resumen)
                        <p>{{ $resultado->resumen }}</p>
                    @endif
                </a>
            @endforeach

            @if ($termino !== '' && $resultados->isEmpty())
                <p class="vacio">Sin resultados para «{{ $termino }}». Prueba con otra palabra.</p>
            @endif
        </div>
    </div>
</x-publico.layout>
