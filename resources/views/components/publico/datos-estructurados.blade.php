@props(['publicacion' => null, 'migas' => []])

@php
    /*
     * JSON-LD para Google. Es lo que convierte un resultado de búsqueda normal
     * en uno con fecha, autor y miga de pan visibles.
     *
     * Se emiten hasta tres grafos: el sitio con su caja de búsqueda, la persona
     * detrás de la marca y, en una publicación, el artículo. Los tres van en un
     * solo bloque `@graph` porque Google prefiere leer uno y no cuatro sueltos.
     */
    $sitio = config('blog.sitio');
    $ficha = config('autor');
    $autor = $sitio['autor'];
    $base = rtrim(config('app.url'), '/');

    $grafo = [
        [
            '@type' => 'WebSite',
            '@id' => $base.'/#sitio',
            'url' => $base,
            'name' => $sitio['marca'],
            'description' => $sitio['descripcion'],
            'inLanguage' => 'es-MX',
            'publisher' => ['@id' => $base.'/#persona'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $base.'/buscar?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ],
        [
            '@type' => 'Person',
            '@id' => $base.'/#persona',
            'name' => $ficha['nombre'].' '.$ficha['apellidos'],
            'url' => $base.'/manuel',
            'jobTitle' => $ficha['titulo'],
            'description' => $ficha['titular'],
            'email' => $autor['correo'] ?? null,
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $ficha['ubicacion'],
                'addressCountry' => 'MX',
            ],
            'sameAs' => array_values(array_filter(
                array_map(fn (array $enlace): ?string => $enlace['url'] ?? null, $ficha['enlaces'] ?? []),
            )),
        ],
    ];

    if ($publicacion !== null) {
        $grafo[] = array_filter([
            '@type' => 'BlogPosting',
            '@id' => url()->current().'#articulo',
            'headline' => $publicacion->titulo,
            'description' => $publicacion->meta_descripcion ?: $publicacion->resumen,
            'url' => url()->current(),
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => url()->current()],
            'datePublished' => $publicacion->fecha_publicacion?->toIso8601String(),
            'dateModified' => $publicacion->updated_at?->toIso8601String(),
            'image' => $publicacion->imagenUrl(),
            'articleSection' => $publicacion->categoria?->nombre,
            'keywords' => $publicacion->etiquetas->pluck('nombre')->implode(', ') ?: $publicacion->tags_seo,
            'wordCount' => str_word_count(strip_tags((string) $publicacion->contenido)),
            'timeRequired' => 'PT'.$publicacion->tiempo_lectura.'M',
            'inLanguage' => 'es-MX',
            'author' => ['@id' => $base.'/#persona'],
            'publisher' => ['@id' => $base.'/#persona'],
            'isPartOf' => ['@id' => $base.'/#sitio'],
        ], fn (mixed $valor): bool => $valor !== null && $valor !== '' && $valor !== []);
    }

    if ($migas !== []) {
        $grafo[] = [
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_values(array_map(
                fn (int $indice, array $miga): array => [
                    '@type' => 'ListItem',
                    'position' => $indice + 1,
                    'name' => $miga['nombre'],
                    'item' => $miga['url'],
                ],
                array_keys($migas),
                $migas,
            )),
        ];
    }

    $json = json_encode(
        ['@context' => 'https://schema.org', '@graph' => $grafo],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
    );
@endphp

<script type="application/ld+json">{!! $json !!}</script>
