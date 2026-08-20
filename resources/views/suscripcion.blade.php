<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $titulo }} · {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light dark; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #f8fafc;
            color: #0f172a;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            padding: 1.5rem;
        }
        .tarjeta {
            max-width: 32rem;
            width: 100%;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 2.5rem;
            box-shadow: 0 10px 30px -20px rgba(15, 23, 42, .5);
        }
        h1 { font-size: 1.5rem; margin: 0 0 .75rem; }
        p { margin: 0 0 1.5rem; line-height: 1.6; color: #475569; }
        a {
            display: inline-block;
            background: #0f172a;
            color: #ffffff;
            text-decoration: none;
            padding: .65rem 1.25rem;
            border-radius: .6rem;
            font-weight: 600;
        }
        @media (prefers-color-scheme: dark) {
            body { background: #020617; color: #e2e8f0; }
            .tarjeta { background: #0f172a; border-color: #1e293b; }
            p { color: #94a3b8; }
            a { background: #e2e8f0; color: #0f172a; }
        }
    </style>
</head>
<body>
    <main class="tarjeta">
        <h1>{{ $titulo }}</h1>
        <p>{{ $mensaje }}</p>
        <a href="{{ rtrim(config('blog.sitio_url'), '/') }}">Ir al blog</a>
    </main>
</body>
</html>
