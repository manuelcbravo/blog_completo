<x-publico.layout seccion="newsletter" titulo="Newsletter"
    descripcion="Un correo a la semana con código que ya probé.">
    <div class="contenedor">
        <section class="dos-columnas">
            <div style="display: flex; flex-direction: column; gap: 22px;">
                <span class="rotulo rotulo--acento">Newsletter · viernes</span>
                <h1 style="font-size: clamp(36px, 4.8vw, 62px); line-height: 1.03;">
                    Un correo a la semana con código que ya probé
                </h1>
                <p style="margin: 0; max-width: 52ch; font-size: 18px; line-height: 1.65; color: var(--fg2); text-wrap: pretty;">
                    Sin resúmenes de noticias ni enlaces de relleno: un problema real de Laravel,
                    cómo lo resolví y el commit.
                </p>

                <x-publico.newsletter-form origen="página newsletter" />

                <span class="meta">Sin spam · un clic para darte de baja</span>
            </div>

            @if ($ultima)
                <div class="caja" style="margin-top: 0; background: var(--panel); padding: 26px 24px;">
                    <span class="rotulo">Último artículo</span>
                    <a href="{{ $ultima->urlPublica() }}"
                       style="font-family: var(--sans); font-size: 22px; font-weight: 600; letter-spacing: -0.02em; line-height: 1.25;">
                        {{ $ultima->titulo }}
                    </a>
                    @if ($ultima->resumen)
                        <p style="font-size: 15px;">{{ $ultima->resumen }}</p>
                    @endif
                    <div style="height: 1px; background: var(--line);"></div>
                    <a href="{{ route('publico.articulos') }}" style="font-family: var(--mono); font-size: 13px; color: var(--accent);">
                        Ver todo el archivo →
                    </a>
                </div>
            @endif
        </section>
    </div>
</x-publico.layout>
