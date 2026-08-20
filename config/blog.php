<?php

return [

    'admin_email' => env('BLOG_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS')),

    'sitio_url' => env('BLOG_SITIO_URL', env('APP_URL')),

    'newsletter_activo' => (bool) env('BLOG_NEWSLETTER_ACTIVO', true),

    'comentarios_moderacion' => (bool) env('BLOG_COMENTARIOS_MODERACION', true),

    'newsletter_lote' => (int) env('BLOG_NEWSLETTER_LOTE', 100),

    'disco' => env('BLOG_DISCO', 's3'),

    'palabras_por_minuto' => (int) env('BLOG_PALABRAS_POR_MINUTO', 200),

    'sitio' => [

        'marca' => env('BLOG_MARCA', 'laravelconmanuel'),

        'descripcion' => env(
            'BLOG_DESCRIPCION',
            'Artículos de Laravel salidos de proyectos que están en producción.',
        ),

        'anuncios' => (bool) env('BLOG_ANUNCIOS', false),

        'autor' => [
            'nombre' => env('BLOG_AUTOR', 'Manuel Cerda Bravo'),
            'oficio' => env('BLOG_AUTOR_OFICIO', 'Desarrollador Laravel'),
            'bio' => env(
                'BLOG_AUTOR_BIO',
                'Escribo sobre Laravel desde 2018. Antes trabajé en dos startups y una consultora; hoy mantengo tres proyectos propios y ayudo a equipos a arreglar lo que la prisa dejó a medias.',
            ),
            'desde' => (int) env('BLOG_AUTOR_DESDE', 2018),
            'avatar' => env('BLOG_AUTOR_AVATAR'),
            'correo' => env('BLOG_AUTOR_CORREO', env('BLOG_ADMIN_EMAIL')),
            'github' => env('BLOG_AUTOR_GITHUB'),
            'linkedin' => env('BLOG_AUTOR_LINKEDIN'),
        ],

    ],

];
