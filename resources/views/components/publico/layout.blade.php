@props([
    'titulo' => null,
    'descripcion' => null,
    'ogTipo' => 'website',
    'ogImagen' => null,
    'seccion' => null,
    'noindex' => false,
])

@php
    $sitio = config('blog.sitio');
    $marca = $sitio['marca'];
    $tituloPagina = $titulo === null ? $marca : $titulo.' · '.$marca;
    $descripcionPagina = $descripcion ?: $sitio['descripcion'];
@endphp

<!DOCTYPE html>
<html lang="es" data-tema="oscuro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $tituloPagina }}</title>
    <meta name="description" content="{{ $descripcionPagina }}">
    @if ($noindex)
        <meta name="robots" content="noindex, nofollow">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="{{ $ogTipo }}">
    <meta property="og:title" content="{{ $tituloPagina }}">
    <meta property="og:description" content="{{ $descripcionPagina }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ $marca }}">
    @if ($ogImagen)
        <meta property="og:image" content="{{ $ogImagen }}">
    @endif
    <meta name="twitter:card" content="{{ $ogImagen ? 'summary_large_image' : 'summary' }}">

    <link rel="icon" href="{{ asset('assets/img/logos/logo_isotipo.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/logos/logo_isotipo.png') }}">

    <link rel="alternate" type="application/rss+xml" title="{{ $marca }}" href="{{ route('feed') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons/font/bootstrap-icons.css') }}">

    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/publico.css', 'resources/js/publico.js'])
</head>
<body>
    <a class="saltar" href="#contenido">Saltar al contenido</a>

    @include('publico.partials.cabecera', ['seccion' => $seccion, 'marca' => $marca])

    <main id="contenido">
        {{ $slot }}
    </main>

    @include('publico.partials.pie', ['marca' => $marca])
</body>
</html>
