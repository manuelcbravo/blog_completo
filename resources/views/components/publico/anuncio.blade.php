@props(['formato' => 'sidebar'])

@php
    /*
     * Un espacio de publicidad. Tiene tres estados:
     *
     *   apagado   sin `BLOG_ANUNCIOS`, no se pinta nada
     *   maqueta   encendido pero sin cuenta de AdSense: el hueco rayado, para
     *             ver dónde cae el anuncio sin cargar nada de Google
     *   real      con cliente y bloque configurados, el <ins> de AdSense
     *
     * El estado de maqueta es el que quieres en local y en una demostración:
     * cargar el script real fuera de producción ensucia tus métricas con
     * impresiones que nadie vio y puede costarte la cuenta.
     */
    $formatos = [
        'leaderboard' => ['medida' => '970 × 90', 'alto' => 'height: 100px;', 'rotulo' => 'Publicidad'],
        'sidebar' => ['medida' => '300 × 250', 'alto' => 'aspect-ratio: 300/250;', 'rotulo' => 'Publicidad'],
        'en-texto' => ['medida' => '336 × 280', 'alto' => 'height: 280px;', 'rotulo' => 'Publicidad'],
        'articulo' => ['medida' => '728 × 90', 'alto' => 'height: 120px;', 'rotulo' => 'Patrocinado'],
    ];

    $config = $formatos[$formato] ?? $formatos['sidebar'];
    $cliente = config('blog.anuncios.cliente');
    $bloque = config('blog.anuncios.bloques.'.$formato);
    $real = filled($cliente) && filled($bloque);
@endphp

@if (config('blog.anuncios.activos'))
    <div {{ $attributes->class('anuncio') }}>
        {{-- La ley y las políticas de AdSense piden que se distinga del contenido. --}}
        <span class="rotulo">{{ $config['rotulo'] }}</span>

        @if ($real)
            <ins class="adsbygoogle"
                 style="display: block;"
                 data-ad-client="{{ $cliente }}"
                 data-ad-slot="{{ $bloque }}"
                 data-ad-format="auto"
                 data-full-width-responsive="true"></ins>
            <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
        @else
            <div class="anuncio__hueco" style="{{ $config['alto'] }}">
                <span>{{ $config['medida'] }}</span>
            </div>
        @endif
    </div>
@endif
