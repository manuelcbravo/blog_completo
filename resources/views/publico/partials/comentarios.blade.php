<section class="comentarios" id="comentarios">
    <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 16px;">
        <span class="rotulo rotulo--acento">Comentarios</span>
        <span class="meta">{{ $comentarios->count() }}</span>
    </div>

    @forelse ($comentarios as $comentario)
        <article class="comentario">
            <span class="meta">
                <strong style="color: var(--fg); font-weight: 500;">{{ $comentario->nombre }}</strong>
                · {{ $comentario->created_at?->translatedFormat('d M Y') }}
            </span>
            <p>{{ $comentario->contenido }}</p>

            @if ($comentario->respuestas->isNotEmpty())
                <div class="comentario__respuestas">
                    @foreach ($comentario->respuestas as $respuesta)
                        <div>
                            <span class="meta">
                                <strong style="color: var(--accent); font-weight: 500;">{{ $respuesta->nombre }}</strong>
                                · {{ $respuesta->created_at?->translatedFormat('d M Y') }}
                            </span>
                            <p>{{ $respuesta->contenido }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>
    @empty
        <p class="meta" style="border-top: 1px solid var(--line); padding-top: 18px;">
            Nadie ha comentado todavía. Estrena la sección.
        </p>
    @endforelse

    @if (session('comentario'))
        <p class="aviso">{{ session('comentario') }}</p>
    @else
        <form class="formulario" method="POST" action="{{ route('publico.comentar') }}"
              style="border-top: 1px solid var(--line); padding-top: 24px;">
            @csrf
            <input type="hidden" name="tipo" value="{{ $publicacion->tipo()->value }}">
            <input type="hidden" name="post_id" value="{{ $publicacion->id }}">

            <label class="trampa" aria-hidden="true">
                Deja este campo vacío
                <input type="text" name="sitio_web" tabindex="-1" autocomplete="off">
            </label>

            <span class="rotulo">Deja tu comentario</span>

            <div class="formulario__linea">
                <input class="campo" type="text" name="nombre" value="{{ old('nombre') }}"
                       placeholder="tu nombre" aria-label="Tu nombre" required>
                <input class="campo" type="email" name="correo" value="{{ old('correo') }}"
                       placeholder="tu@correo.com" aria-label="Tu correo" required>
            </div>

            <textarea class="campo" name="contenido" rows="4" aria-label="Tu comentario"
                      placeholder="¿Qué te pareció?" required>{{ old('contenido') }}</textarea>

            @foreach (['nombre', 'correo', 'contenido', 'sitio_web'] as $campo)
                @error($campo)
                    <span class="error">{{ $message }}</span>
                @enderror
            @endforeach

            <button class="boton" type="submit">Publicar comentario</button>

            <span class="meta">
                Tu correo no se publica. Reviso los comentarios antes de publicarlos.
            </span>
        </form>
    @endif
</section>
