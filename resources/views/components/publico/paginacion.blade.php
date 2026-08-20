@props(['paginador'])

@if ($paginador->hasPages())
    <nav class="paginacion" aria-label="Paginación">
        @if ($paginador->onFirstPage())
            <span aria-disabled="true">← anterior</span>
        @else
            <a href="{{ $paginador->previousPageUrl() }}" rel="prev">← anterior</a>
        @endif

        <span class="activa">{{ $paginador->currentPage() }} / {{ $paginador->lastPage() }}</span>

        @if ($paginador->hasMorePages())
            <a href="{{ $paginador->nextPageUrl() }}" rel="next">siguiente →</a>
        @else
            <span aria-disabled="true">siguiente →</span>
        @endif
    </nav>
@endif
