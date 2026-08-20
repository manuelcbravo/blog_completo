@props([
    'publicacion' => null,
    'variante' => '16x10',
    'etiqueta' => null,
])

@php
    $url = $publicacion?->imagenUrl();
    $medidas = ['16x10' => 'imagen 16:10', 'hero' => 'imagen 1200×620', 'articulo' => 'imagen 1440×720', '1x1' => 'retrato 1:1'];
    $categoria = $publicacion?->categoria?->nombre;
    $texto = $etiqueta ?? trim(($medidas[$variante] ?? 'imagen').($categoria ? ' — '.mb_strtolower($categoria) : ''));
@endphp

@if ($url)
    <div {{ $attributes->class(['imagen-marco', 'imagen-marco--'.$variante]) }}>
        <img src="{{ $url }}" alt="{{ $publicacion?->titulo }}" loading="lazy" decoding="async">
    </div>
@else
    <div {{ $attributes->class(['marcador', 'marcador--'.$variante]) }} aria-hidden="true">
        <span>{{ $texto }}</span>
    </div>
@endif
