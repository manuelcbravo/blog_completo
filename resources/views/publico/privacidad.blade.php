@php
    $autor = config('blog.sitio.autor');
    $ficha = config('autor');
    $responsable = $ficha['nombre'].' '.$ficha['apellidos'];
@endphp

<x-publico.layout
    seccion="privacidad"
    titulo="Aviso de privacidad"
    descripcion="Qué datos registra este sitio, para qué se usan y cómo pedir que se borren."
>
    <div class="contenedor">
        <section class="bloque">
            <h1 class="titulo-seccion">Aviso de privacidad</h1>

            <p class="bloque__intro">
                Este sitio es personal y lo administra {{ $responsable }}, en
                {{ $ficha['ubicacion'] }}. Aquí está, sin rodeos, qué se registra
                cuando lo visitas.
            </p>

            <h2 class="titulo-seccion titulo-seccion--menor">Qué se registra al navegar</h2>

            <p>
                Cada vez que abres un artículo, un tutorial, un recurso, mi ficha o
                la página de proyectos, se guarda un renglón con la dirección IP
                desde la que entraste, el navegador y sistema que usas, la página
                que abriste, el sitio desde el que llegaste y la fecha. Se guarda
                una sola vez por sesión y por página.
            </p>

            <p>
                Sirve para saber qué contenido le interesa a la gente y desde dónde
                llega. No se usa para identificarte ni se cruza con ninguna otra
                base de datos, y no hay publicidad ni rastreadores de terceros en
                estas páginas.
            </p>

            <h2 class="titulo-seccion titulo-seccion--menor">Qué se registra si tú lo escribes</h2>

            <p>
                Si te suscribes al newsletter se guarda tu correo, y sólo para
                enviarte los avisos de publicaciones nuevas; cada correo lleva su
                liga para darte de baja. Si me escribes por el formulario de
                contacto o dejas un comentario, se guarda lo que escribiste junto
                con tu nombre, tu correo y la IP desde la que lo enviaste, para
                poder responderte y para frenar el spam.
            </p>

            <h2 class="titulo-seccion titulo-seccion--menor">Con quién se comparte</h2>

            <p>
                Con nadie. Los datos viven en el servidor de este sitio y no se
                venden, ceden ni transfieren. Los correos salen a través de un
                proveedor de envío, que los procesa únicamente para entregarlos.
            </p>

            <h2 class="titulo-seccion titulo-seccion--menor">Cómo pedir que se borren</h2>

            <p>
                Escríbeme a
                <a href="mailto:{{ $autor['correo'] }}">{{ $autor['correo'] }}</a>
                y dime qué quieres consultar, corregir o eliminar. Puedes pedir el
                acceso, la rectificación, la cancelación o la oposición al uso de
                tus datos —los derechos ARCO de la Ley Federal de Protección de
                Datos Personales en Posesión de los Particulares—, y respondo en un
                plazo máximo de veinte días hábiles.
            </p>

            <p class="meta">
                Última actualización: {{ now()->translatedFormat('d \d\e F \d\e Y') }}.
            </p>
        </section>
    </div>
</x-publico.layout>
