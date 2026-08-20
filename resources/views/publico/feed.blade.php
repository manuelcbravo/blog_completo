<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ config('blog.sitio.marca') }}</title>
        <link>{{ route('home') }}</link>
        <description>{{ config('blog.sitio.descripcion') }}</description>
        <language>es</language>
        <atom:link href="{{ route('feed') }}" rel="self" type="application/rss+xml"/>
        @foreach ($posts as $post)
        <item>
            <title>{{ $post->titulo }}</title>
            <link>{{ $post->urlPublica() }}</link>
            <guid isPermaLink="true">{{ $post->urlPublica() }}</guid>
            <description>{{ $post->resumen }}</description>
            @if ($post->categoria)<category>{{ $post->categoria->nombre }}</category>@endif
            <pubDate>{{ $post->fecha_publicacion?->toRfc2822String() }}</pubDate>
        </item>
        @endforeach
    </channel>
</rss>
