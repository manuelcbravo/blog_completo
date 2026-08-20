@props(['formato' => 'sidebar'])

@php
    $formatos = [
        'sidebar' => ['medida' => '300 × 250', 'alto' => 'aspect-ratio: 300/250;', 'rotulo' => 'Publicidad'],
        'articulo' => ['medida' => '728 × 90', 'alto' => 'height: 120px;', 'rotulo' => 'Patrocinado'],
        'leaderboard' => ['medida' => '970 × 90', 'alto' => 'height: 100px;', 'rotulo' => 'Publicidad'],
    ];
    $config = $formatos[$formato] ?? $formatos['sidebar'];
@endphp

@if (config('blog.sitio.anuncios'))
    <div {{ $attributes->class('anuncio') }}>
        <span class="rotulo">{{ $config['rotulo'] }}</span>
        <div class="anuncio__hueco" style="{{ $config['alto'] }}">
            <span>{{ $config['medida'] }}</span>
        </div>
    </div>
@endif
