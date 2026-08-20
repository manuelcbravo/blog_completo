<x-publico.layout titulo="Sobre el blog">
    <div class="contenedor">
        <div class="columna">
            <span class="rotulo rotulo--acento">Sobre el blog</span>
            <h1 style="font-size: clamp(34px, 4.6vw, 58px); line-height: 1.05;">
                Laravel explicado desde proyectos que están en producción
            </h1>
            <p style="margin: 0; font-size: 19px; line-height: 1.7; color: var(--fg2); text-wrap: pretty;">
                Aquí no hay tutoriales de «hola mundo». Cada artículo sale de un problema que tuve
                que resolver con una fecha de entrega encima, y cuenta también lo que salió mal.
            </p>

            <div class="manifiesto">
                <div>
                    <span class="categoria">01</span>
                    <h3>Código que corre</h3>
                    <p>Los ejemplos salen de proyectos reales, no de un editor en blanco.</p>
                </div>
                <div>
                    <span class="categoria">02</span>
                    <h3>Sin relleno</h3>
                    <p>Un artículo por semana como mucho, y sólo si tengo algo que contar.</p>
                </div>
                <div>
                    <span class="categoria">03</span>
                    <h3>Se corrige</h3>
                    <p>Si algo está mal, se edita y se dice. Los comentarios están abiertos.</p>
                </div>
            </div>

            @if (config('blog.sitio.autor.correo'))
                <p style="margin: 0; font-size: 17px; line-height: 1.7; color: var(--fg2);">
                    ¿Tienes una idea, una corrección o un proyecto? Escribe a
                    <a style="color: var(--accent); border-bottom: 1px solid currentColor;"
                       href="mailto:{{ config('blog.sitio.autor.correo') }}">{{ config('blog.sitio.autor.correo') }}</a>.
                </p>
            @endif
        </div>
    </div>
</x-publico.layout>
