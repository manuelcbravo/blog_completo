<?php

/*
|--------------------------------------------------------------------------
| Proyectos en producción (página /proyectos)
|--------------------------------------------------------------------------
|
| Catálogo de lo que corre en el servidor: plataformas, bots y la capa de
| infraestructura. Vive aquí (no en base de datos) porque es una vitrina
| técnica que cambia poco y no necesita CRUD. Detalle técnico a propósito,
| pero SIN secretos: nada de tokens, IPs internas, puertos ni contraseñas.
|
| Regla de honestidad: aquí solo se lista lo que está implementado y en uso.
| Nada de dependencias instaladas "por si acaso" ni funciones planeadas.
|
*/

/*
 * Cuenta de demostración. Es la misma en POS, Atlas Electoral y Territorio, a
 * propósito: quien venga a ver el trabajo teclea una sola credencial.
 *
 * Se muestra en la página, pero los valores viven en el `.env` y no en este
 * archivo, que sí va a git: una cosa es enseñarla en la vitrina y otra dejarla
 * en el historial del repositorio para siempre.
 *
 * Está pensada para publicarse, así que el rol `demo` de cada plataforma va
 * recortado: en POS y Electoral solo puede consultar, y en ninguna de las tres
 * llega a la administración de usuarios ni a la configuración. Si eso cambia en
 * el servidor, hay que revisar lo que dice aquí.
 *
 * Es temporal, mientras la vitrina esté arriba. Para apagar el bloque en las
 * tres tarjetas de un solo movimiento, basta vaciar `DEMO_ACCESO_CLAVE`.
 */
$demo = [
    'usuario' => env('DEMO_ACCESO_USUARIO'),
    'clave' => env('DEMO_ACCESO_CLAVE'),
];

return [

    'titulo' => 'Proyectos en producción',

    'intro' => 'Todo esto corre en un solo VPS (AlmaLinux 9) con cerca de 25 contenedores Docker. Cada aplicación y su base de datos viven en su propio stack de docker compose, publicados solo en loopback y expuestos a internet por un reverse proxy nginx con certificado TLS por subdominio. Los paneles internos se alcanzan por una red privada Tailscale, sin abrir puertos extra.',

    'nota' => 'Ficha técnica de arquitectura y stack. Se omiten a propósito credenciales, tokens, IPs internas y puertos.',

    /*
     * Cuántos puntos de "detalles" se ven sin desplegar. El resto se guarda en
     * un acordeón. Si lo que quedaría escondido es un solo punto, no se parte:
     * no vale la pena un "ver más" para una línea.
     */
    'detalles_visibles' => 3,

    'grupos' => [

        [
            'grupo' => 'Plataformas',
            'icono' => 'servidor',
            'resumen' => 'Aplicaciones web de negocio, cada una con su base de datos, colas y despliegue independientes.',
            'proyectos' => [

                [
                    'nombre' => 'POS / ERP',
                    'url' => 'https://pos.laravelconmanuel.dev',
                    'tipo' => 'Punto de venta, ERP y facturación electrónica',
                    'acceso' => $demo + ['nota' => 'Cuenta de solo lectura: recorre los módulos, no modifica nada.'],
                    'resumen' => 'Sistema comercial completo sobre Laravel 12 con Inertia y React: venta de mostrador, inventario, compras, producción, cobranza y facturación CFDI 4.0 timbrada ante el SAT. Además hace de backend para el bot de pedidos.',
                    'detalles' => [
                        'Facturación electrónica CFDI 4.0: timbrado y cancelación contra un PAC, factura global de los tickets del periodo, autofacturación pública para que el cliente facture su ticket, catálogos oficiales del SAT (uso de CFDI, régimen fiscal, forma de pago, claves de unidad y de servicio), validación fiscal del receptor, PDF de la factura y envío por correo.',
                        'Roles y permisos auto-administrables: los roles se crean desde la propia aplicación y se les asignan permisos sin tocar código, con bitácora de auditoría (quién cambió qué y cuándo) y segundo factor de autenticación.',
                        'Multiempresa y multisucursal: cada usuario tiene su alcance de empresas y sucursales, con series y folios propios por documento.',
                        'Inventario con existencias por almacén, ajustes, conteos físicos, transferencias, reservas y kardex de movimientos.',
                        'Compras: órdenes, recepciones y carga del XML del proveedor para dar de alta los conceptos sin recapturar.',
                        'Producción: recetas versionadas, órdenes de producción y de trabajo, consumos, mermas e incidencias.',
                        'Caja y cobranza: apertura y corte de caja, movimientos, cuentas por cobrar y por pagar con sus pagos, devoluciones, garantías y promociones por reglas.',
                        'Portal de pedidos para clientes restaurante: cada cliente entra con PIN a su propia URL pública, pide contra su catálogo y su lista de precios pactada, y el pedido se acepta o rechaza desde el sistema.',
                        'Gateway propio de WhatsApp en Node (Baileys, vinculación por QR) para notificar al cliente el estado de su pedido, con reintentos en cola.',
                        'Contenedores separados para la app (PHP-FPM), el servidor web, el worker de colas y el scheduler: cada rol escala y se reinicia por su cuenta.',
                        'API interna protegida por llave para que el bot de pedidos (Pycos) registre órdenes; valida además empresa activa.',
                        'Reportes con tableros de Highcharts, calendario de operación, exportación a Excel y documentos PDF (tickets y facturas) con el importe en letra.',
                        'PostgreSQL dedicado; sesiones, caché y colas sobre el driver de base de datos. Archivos en almacenamiento S3 (MinIO) vía Flysystem.',
                    ],
                    'stack' => ['Laravel', 'PHP', 'Inertia.js', 'React', 'TypeScript', 'Tailwind CSS', 'shadcn/ui', 'PostgreSQL', 'SAT / CFDI', 'Roles y permisos (RBAC)', 'Bitácora de auditoría', 'Autenticación en dos pasos', 'Node.js', 'WhatsApp (Baileys)', 'Highcharts', 'Excel / Exportaciones', 'PDF / Documentos', 'Colas', 'API REST', 'MinIO (S3)', 'Docker', 'Nginx'],
                ],

                [
                    'nombre' => 'Atlas Electoral',
                    'url' => 'https://electoral.laravelconmanuel.dev',
                    'tipo' => 'SaaS de inteligencia electoral geoespacial',
                    'acceso' => $demo + ['nota' => 'Acceso completo a los módulos —tableros, mapas, panorama, gestión, directorio y catálogos—, salvo la administración de usuarios.'],
                    'resumen' => 'Ingesta y análisis territorial de reportes de campo; cruza la información con capas geográficas usando PostGIS y la pinta sobre mapas vectoriales. Recibe gestiones en tiempo real desde el bot de WhatsApp "pipe".',
                    'detalles' => [
                        'Laravel 13 con Inertia + React 19 y Vite; TypeScript en el front.',
                        'PostgreSQL con extensión PostGIS y capas geográficas importadas desde shapefiles del marco geoelectoral del INE (secciones, localidades, colonias).',
                        'Mapas vectoriales con MapLibre GL sobre GeoJSON servido por la propia API: resultados por sección, cruces entre elecciones y capa socioeconómica.',
                        'Panorama Electoral: resultados históricos por casilla de varios procesos (2006-2024), leyendo tanto las tablas heredadas —descubriendo por introspección las columnas de partido, que cambian en cada elección— como el modelo normalizado nuevo.',
                        'Módulo de Obra pública: cruce del informe de gobierno contra el marco geográfico del INE.',
                        'Gestión de solicitudes ciudadanas con folio, seguimiento, anotaciones y archivos adjuntos.',
                        'Roles y permisos auto-administrables desde la propia aplicación, más acceso sin contraseña con llaves de acceso (passkeys / WebAuthn) y segundo factor.',
                        'Endpoint de ingesta autenticado por token (Bearer) para recibir los registros estructurados del bot; sin token no acepta nada, porque una ruta de alta abierta es un formulario público para llenar el padrón.',
                        'Tableros con Highcharts y reportes exportables en PDF.',
                        'Imagen Docker multi-etapa (vendor → assets → runtime FPM) detrás del reverse proxy con TLS.',
                    ],
                    'stack' => ['Laravel', 'PHP', 'Inertia.js', 'React', 'TypeScript', 'Tailwind CSS', 'PostgreSQL', 'PostGIS', 'MapLibre GL', 'GeoJSON', 'Shapefiles / INE', 'Highcharts', 'Roles y permisos (RBAC)', 'Passkeys / WebAuthn', 'Autenticación en dos pasos', 'PDF / Documentos', 'Vite', 'API REST', 'Docker', 'Nginx'],
                ],

                [
                    'nombre' => 'Territorio',
                    'url' => 'https://territorio.laravelconmanuel.dev',
                    'tipo' => 'Gestión territorial + backend de app móvil',
                    'acceso' => $demo + ['nota' => 'Cuenta de demostración sobre datos anonimizados.'],
                    'resumen' => 'Plataforma gemela de Electoral, orientada a redes y promoción territorial. Es también el backend de la app móvil de campo, con una API pensada para trabajar sin señal y sincronizar después.',
                    'detalles' => [
                        'Mismo patrón Laravel 13 + Inertia/React, con contenedores dedicados y su propia base PostGIS.',
                        'API v1 para la app móvil con autenticación por token: arranque, catálogos y descarga incremental de promovidos, promotores y solicitudes; la jornada completa sube en un solo lote.',
                        'Cada endpoint de la API exige su propio permiso —un promotor levanta la solicitud de su gente y después la verifica, pero no decide a qué dependencia se turna ni cuándo se cierra— y el login va limitado por IP y por correo, porque la app se instala en teléfonos ajenos.',
                        'Gestión de solicitudes con turnado a dependencias, seguimiento y verificación en campo.',
                        'Tableros estatal y municipal con mapas de coropletas y gráficas de Highcharts; la geometría viaja aparte en GeoJSON para que el navegador la cachee una sola vez.',
                        'Roles y permisos auto-administrables, bitácora de auditoría sobre los modelos del negocio, borrado suave, llaves de acceso (passkeys) y segundo factor.',
                        'Exportaciones a Excel por streaming, para sacar padrones grandes sin tumbar la memoria.',
                        'Datos de demostración anonimizados; sin información real.',
                    ],
                    'stack' => ['Laravel', 'PHP', 'Inertia.js', 'React', 'TypeScript', 'Tailwind CSS', 'PostgreSQL', 'PostGIS', 'GeoJSON', 'Highcharts', 'API REST', 'Autenticación por token', 'Roles y permisos (RBAC)', 'Bitácora de auditoría', 'Passkeys / WebAuthn', 'Excel / Exportaciones', 'Docker', 'Nginx'],
                ],

                [
                    'nombre' => 'Territorio App',
                    'url' => null,
                    'tipo' => 'App móvil de campo (Android), offline-first',
                    'resumen' => 'La app que usan coordinadores y promotores en la calle. Trabaja sin señal: guarda todo en una base local del teléfono y sincroniza contra Territorio cuando vuelve la conexión.',

                    /*
                     * El APK se sirve desde el S3 propio (MinIO). No está en Google
                     * Play a propósito: es un proyecto de demostración y publicar en
                     * la tienda pide cuenta de desarrollador, revisión y una política
                     * de privacidad que aquí no aplica.
                     *
                     * Si el archivo cambia de lugar o de versión, se ajusta aquí y en
                     * ningún otro sitio. Con 'url' vacío el botón no se pinta.
                     */
                    'descarga' => [
                        'url' => 'https://s3.laravelconmanuel.dev/descargas/territorio-app-1.0.0.apk',
                        'etiqueta' => 'Descargar el APK (Android)',
                        // 179 MB en decimal, que es como lo va a reportar el navegador
                        // al descargar (el Explorador de Windows dirá 170, en MiB).
                        'nota' => 'v1.0.0 · 179 MB · No está en Google Play: es un proyecto de demostración. Android pedirá permiso para instalar desde fuera de la tienda.',
                    ],

                    'detalles' => [
                        'React Native con Expo y TypeScript; navegación de drawer + tabs y sistema de diseño propio.',
                        'Offline-first sobre SQLite en el teléfono, con migraciones propias probadas contra una base real: la captura nunca depende de la red.',
                        'Cola de sincronización que sube la jornada por lotes y detecta el estado de la conexión para reintentar sola.',
                        'Lectura de la credencial de elector con la cámara y OCR nativo (ML Kit); si el binario no trae el módulo, la pantalla lo dice y ofrece captura manual en vez de dejar una cámara que no reconoce nada.',
                        'El token se guarda en el almacén seguro del sistema, no en almacenamiento común.',
                        'Validación de formularios por esquema, estado global ligero y peticiones con caché y reintentos.',
                        'Se distribuye como APK / development build de Android.',
                    ],
                    'stack' => ['React Native', 'Expo', 'TypeScript', 'SQLite', 'Offline-first', 'OCR (ML Kit)', 'Android / APK', 'API REST', 'Autenticación por token'],
                ],

                [
                    'nombre' => 'SB-demo · Territorio-sync',
                    'url' => 'https://sb.laravelconmanuel.dev',
                    'tipo' => 'Microservicio API en Java / Spring Boot',
                    'resumen' => 'Una "rebanada" de la API móvil de Territorio (login + promovidos) reescrita en Spring Boot, sobre la misma base que la plataforma Territorio y sin tocar su esquema. Existe para ejercitar Java con datos y reglas de verdad, no con un CRUD de juguete.',
                    'detalles' => [
                        'Java 17 + Spring Boot 4; build con Maven e imagen Docker multi-etapa (JDK para compilar → JRE para ejecutar).',
                        'Spring Security verificando contraseñas bcrypt contra los mismos hashes que emite Laravel, y emisión de token propio.',
                        'Spring Data JPA con consultas dinámicas por Specification, paginación y alcance de datos por usuario; permisos evaluados por fila.',
                        'Bean Validation en la entrada y alta idempotente por UUID: reintentar la misma petición no duplica el registro.',
                        'Se conecta por JDBC a la base de Territorio y se une a la red interna de ese stack; no modifica el esquema (ddl-auto en none).',
                        'Trae su colección de Postman, con scripts que capturan el token solos.',
                        'Publicada por el reverse proxy con TLS. Demuestra convivencia poliglota: un servicio Java leyendo la misma base que las apps Laravel.',
                    ],
                    'stack' => ['Java / Spring Boot', 'Spring Security', 'JPA / Hibernate', 'Bean Validation', 'Maven', 'JDBC', 'PostgreSQL', 'API REST', 'Autenticación por token', 'Docker', 'Nginx'],
                ],

            ],
        ],

        [
            'grupo' => 'Bots conversacionales',
            'icono' => 'chispa',
            'resumen' => 'Chatbot de WhatsApp construido como máquina de estados sobre n8n, con su propio Postgres e integrado al POS por API. Este sí se puede probar en vivo.',
            'proyectos' => [

                [
                    'nombre' => 'Bot de pedidos del POS',
                    'url' => null,
                    'tipo' => 'Chatbot de WhatsApp (FSM) integrado al POS',
                    'resumen' => 'Bot de WhatsApp que levanta un pedido completo dentro de la conversación —producto, decoración, fecha de entrega y comprobante de pago— y lo registra en el POS a través de su API interna, consultando el catálogo y los precios en vivo.',

                    /*
                     * Demo en vivo. El número es de pruebas (cuenta demo de Meta
                     * por Dualhook), no un número personal: por eso se puede
                     * publicar. Si algún día se pasa al número oficial, hay que
                     * cambiarlo aquí y en el WABA.
                     */
                    'prueba' => [
                        'numero' => '+1 816-851-0831',
                        'e164' => '18168510831',
                        'texto' => 'Hola, quiero hacer un pedido',
                        'etiqueta' => 'Pruébalo en WhatsApp',
                        'nota' => 'Número de pruebas: contesta el bot, no una persona. El pedido queda en el sistema de demostración.',
                    ],

                    'detalles' => [
                        'n8n + Postgres en Docker; tres workflows: verificación del webhook, entrada de mensajes y la máquina de estados del pedido.',
                        'La conversación es una máquina de estados: cada turno lee el estado en la base, decide y lo vuelve a guardar, así que el bot no guarda nada en memoria y se puede reiniciar sin perder charlas.',
                        'Consulta el catálogo y resuelve al cliente contra el POS en vivo, y crea el pedido en la sucursal física que eligió la persona (no en el canal web), con una llave de servicio dedicada.',
                        'El comprobante de pago que manda el cliente se descarga de WhatsApp y se sube al POS, que lo guarda en el almacenamiento S3.',
                        'Manda imágenes del catálogo dentro de la conversación cuando el cliente pide ver opciones.',
                        'Entra por la Cloud API de Meta a través del gateway Dualhook. La verificación (GET) y los mensajes entrantes (POST) usan una sola URL de webhook: el ruteo por método se resuelve en el reverse proxy nginx.',
                    ],
                    'stack' => ['n8n', 'Chatbots (WhatsApp / FSM)', 'WhatsApp Cloud API', 'Dualhook', 'API REST', 'PostgreSQL', 'MinIO (S3)', 'Webhooks', 'Docker', 'Nginx'],
                ],

            ],
        ],

        [
            'grupo' => 'Infraestructura, datos y almacenamiento',
            'icono' => 'escudo',
            'resumen' => 'La base sobre la que corre todo lo demás: orquestación, almacenamiento, monitoreo y endurecimiento.',
            'proyectos' => [

                [
                    'nombre' => 'MinIO · Almacenamiento S3',
                    'url' => 'https://s3.laravelconmanuel.dev',
                    'tipo' => 'Almacenamiento de objetos S3-compatible, self-hosted',
                    'resumen' => 'S3 propio para los archivos de las aplicaciones (imágenes, adjuntos, comprobantes). Integrado con Laravel vía Flysystem; la consola de administración solo se alcanza por la red privada.',
                    'detalles' => [
                        'Contenedor MinIO con volumen dedicado para los datos y versión fija, no "latest".',
                        'API S3 publicada por TLS detrás de nginx, con subidas grandes por streaming: sin buffering ni límite de cuerpo, o las cargas se cortan al primer megabyte.',
                        'Buckets con llaves de servicio propias y políticas por bucket (lectura pública controlada para assets, privado por defecto); las llaves root no se usan en las aplicaciones.',
                        'Direccionamiento por ruta (dominio/bucket) en vez de un subdominio por bucket, que es como espera AWS.',
                    ],
                    'stack' => ['MinIO (S3)', 'S3 API', "Let's Encrypt / TLS", 'Docker', 'Nginx'],
                    'acento' => true,
                ],

                [
                    'nombre' => 'Netdata · Monitoreo',
                    'url' => null,
                    'tipo' => 'Observabilidad en tiempo real',
                    'resumen' => 'Métricas del host y de los contenedores en vivo. Accesible solo por la red privada Tailscale; no se expone a internet.',
                    'detalles' => [
                        'Recolección en tiempo real de CPU, memoria, disco, red y estado de contenedores.',
                        'Aislado del exterior: se consulta por Tailscale, incluso desde el celular.',
                    ],
                    'stack' => ['Monitoreo / Netdata', 'Tailscale', 'Docker'],
                ],

                [
                    'nombre' => 'Capa de plataforma',
                    'url' => null,
                    'tipo' => 'VPS, orquestación y endurecimiento',
                    'resumen' => 'El pegamento de todo: cómo conviven ~25 contenedores en un mismo servidor de forma ordenada y segura.',
                    'detalles' => [
                        'Cada app y su base en su propio stack de docker compose, publicadas solo en loopback.',
                        'Reverse proxy nginx con un certificado Let\'s Encrypt por subdominio y redirección HTTP→HTTPS.',
                        'Paneles internos por Tailscale, sin abrir puertos a internet.',
                        'Endurecimiento: SSH solo por llave con passphrase, SELinux en enforcing, firewall, fail2ban y gestión de secretos con permisos estrictos.',
                        'Imágenes con versión fija en vez de "latest", para que un despliegue no cambie de versión sin avisar.',
                        'Auditoría periódica del servidor desde la óptica de un atacante: escaneo del perímetro desde fuera, revisión de los permisos de los secretos, de la configuración de SSH y de la superficie web publicada.',
                        'Despliegue por Git con deploy keys dedicadas por repositorio y restauración de bases con pg_restore.',
                    ],
                    'stack' => ['Linux (AlmaLinux/RHEL)', 'Docker', 'Docker Compose', 'Nginx', "Let's Encrypt / TLS", 'Tailscale', 'SELinux', 'firewalld', 'fail2ban', 'Endurecimiento SSH', 'Backups / pg_restore', 'Git'],
                ],

            ],
        ],

    ],

];
