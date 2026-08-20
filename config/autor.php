<?php

/*
|--------------------------------------------------------------------------
| Ficha del autor
|--------------------------------------------------------------------------
|
| Contenido de la página pública /manuel, tomado del CV. Vive aquí y no en
| base de datos porque es una hoja de vida: cambia dos veces al año y no
| necesita un CRUD. Todo está escrito en primera persona.
|
*/

return [

    'disponible' => (bool) env('BLOG_AUTOR_DISPONIBLE', true),

    'nombre' => 'Manuel',
    'apellidos' => 'Cerda Bravo',
    'titulo' => 'Senior Full-Stack Developer · Laravel & React · Tech Lead',
    'ubicacion' => 'Pachuca, Hidalgo',

    'titular' => 'Llevo diez años construyendo el software con el que otras empresas facturan, venden y deciden.',

    'resumen' => 'Diseño arquitecturas escalables, APIs REST y bases PostgreSQL para soluciones SaaS, ERP, POS y Business Intelligence. Dirijo un equipo de cinco desarrolladores, y me tocan por igual el patrón de diseño y la llamada del cliente cuando algo se cae en producción.',

    'experiencia' => '+10 años',
    'equipo' => '5 personas',
    'modalidad' => 'Remoto · Pachuca, Hgo',
    'telefono' => '771 131 8736',
    // El mismo número en E.164: es lo que entienden tel: y wa.me.
    'telefono_e164' => '+527711318736',
    'idiomas' => 'Español nativo · Inglés técnico',

    'enlaces' => [
        ['etiqueta' => 'GitHub', 'icono' => 'github', 'url' => env('BLOG_AUTOR_GITHUB', 'https://github.com/')],
        ['etiqueta' => 'LinkedIn', 'icono' => 'linkedin', 'url' => env('BLOG_AUTOR_LINKEDIN', 'https://www.linkedin.com/in/manuelcerdabravo')],
    ],

    'trayectoria' => [
        [
            'puesto' => 'Tech Lead · Jefe de Proyectos',
            'periodo' => '2017 – Actual',
            'empresa' => 'Fielgroup',
            'lugar' => 'Pachuca, Hidalgo',
            'resumen' => 'Dirijo a cinco desarrolladores construyendo ERP, POS, Business Intelligence y aplicaciones híbridas, con la escalabilidad como criterio de diseño y no como parche posterior.',
            'logros' => [
                'Dirijo el roadmap técnico y la entrega de varias soluciones SaaS en Laravel y PostgreSQL con componentes en React.',
                'Implementé las integraciones con SAT (CFDI) y Mercado Libre que automatizan ventas y conciliación de datos.',
                'Llevé la facturación electrónica de punta a punta: timbrado y cancelación con PAC, factura global del periodo y un portal de autofacturación para que el cliente facture su propio ticket.',
                'Monté y endurecí la plataforma donde vive todo: un VPS con cerca de 25 contenedores Docker, un stack por aplicación, TLS por subdominio, red privada, almacenamiento S3 propio y monitoreo; la audito periódicamente desde la óptica de un atacante.',
                'Entregué la app de campo offline-first: captura sin señal sobre SQLite, lectura de la credencial con OCR y sincronización por lotes en cuanto vuelve la conexión.',
                'Automaticé el diagnóstico de reportes ciudadanos con IA de visión en dos niveles —una pasada barata que valida la foto y otra que estima medidas y material— con salida estructurada y respaldo cuando el servicio falla.',
                'Establecí code review y estándares de calidad: bajó la deuda técnica y subió la mantenibilidad.',
                'Diseñé arquitecturas de alto rendimiento con optimización de consultas, caché y colas.',
            ],
        ],
        [
            'puesto' => 'Desarrollador de Software · Soporte y Evolución',
            'periodo' => '2020 – 2022',
            'empresa' => 'Gobierno del Estado de Hidalgo',
            'lugar' => 'Pachuca, Hidalgo',
            'resumen' => 'Mantuve y evolucioné la plataforma estatal de gestión de auditorías, añadiendo módulos y tableros sobre un sistema en uso.',
            'logros' => [
                'Di soporte de segundo nivel y refactoricé de forma continua, mejorando rendimiento y usabilidad.',
                'Mejoré el logging y la trazabilidad para auditorías y análisis post-incidente.',
                'Integré los datos con herramientas de BI para reportes ejecutivos y seguimiento de cumplimiento.',
            ],
        ],
        [
            'puesto' => 'Jefe de Área de Desarrollo',
            'periodo' => '2013 – 2016',
            'empresa' => 'Gobierno del Estado de Hidalgo',
            'lugar' => 'Pachuca, Hidalgo',
            'resumen' => 'Lideré a cuatro desarrolladores en plataformas de planeación, inteligencia electoral y visualización geoespacial con mapas SVG.',
            'logros' => [
                'Entregué plataformas de gestión con automatización de procesos y reporting ejecutivo.',
                'Introduje nuevas tecnologías y buenas prácticas: estandarización y trazabilidad de los desarrollos.',
                'Coordiné entregables con stakeholders de alto nivel y peticiones de data mining.',
            ],
        ],
    ],

    /*
     * OJO: hoy la vista `publico/autor.blade.php` NO pinta este bloque — los
     * proyectos viven en su propia página, `config/proyectos.php` (/proyectos).
     * Se conserva actualizado por si algún día se vuelve a mostrar aquí un
     * resumen corto; si se decide que no, se puede borrar sin romper nada.
     */
    'proyectos' => [
        [
            'nombre' => 'Atlas Electoral',
            'tipo' => 'Plataforma SaaS de inteligencia electoral',
            'resumen' => 'Diagnóstico territorial, gestión de campaña y operación de jornada para consultores políticos. Ingiere datos del INE y los cruza geoespacialmente con PostGIS, sobre mapas vectoriales.',
            'stack' => ['Laravel 13', 'PostgreSQL 17', 'PostGIS', 'Inertia', 'React', 'TypeScript', 'MapLibre GL', 'Highcharts'],
        ],
        [
            'nombre' => 'POS / ERP',
            'tipo' => 'Punto de venta, ERP y facturación electrónica',
            'resumen' => 'Ventas, inventario, compras, producción y cobranza en una sola plataforma multiempresa, con facturación CFDI 4.0 timbrada, factura global y autofacturación para el cliente final.',
            'stack' => ['Laravel', 'PostgreSQL', 'Inertia', 'React', 'API REST', 'SAT / CFDI', 'MinIO (S3)'],
        ],
        [
            'nombre' => 'Territorio',
            'tipo' => 'Gestión territorial + app móvil de campo',
            'resumen' => 'Plataforma de redes y promoción territorial con su app Android offline-first: se captura sin señal sobre SQLite, se lee la credencial con OCR y la jornada sube por lotes al recuperar la conexión.',
            'stack' => ['Laravel 13', 'PostGIS', 'React Native', 'Expo', 'SQLite', 'OCR (ML Kit)', 'API REST'],
        ],
        [
            'nombre' => 'Ameo Motion',
            'tipo' => 'SaaS automotriz',
            'resumen' => 'Avalúo, inventario y venta de vehículos, con aplicación móvil nativa sobre un backend Laravel.',
            'stack' => ['React Native', 'Expo', 'Laravel', 'PostgreSQL'],
        ],
        [
            'nombre' => 'Reporta un Bache',
            'tipo' => 'Civic tech por WhatsApp',
            'resumen' => 'Los ciudadanos reportan baches por WhatsApp; el bot los geolocaliza contra las capas de la ciudad y la plataforma analiza la foto con IA de visión en dos niveles: primero valida que sea un bache y luego estima medidas y material.',
            'stack' => ['WhatsApp Cloud API', 'n8n', 'PostGIS', 'Visión por computadora', 'Salidas estructuradas (JSON Schema)', 'VPS'],
        ],
        [
            'nombre' => 'Territorio-sync',
            'tipo' => 'Microservicio API en Java / Spring Boot',
            'resumen' => 'Una rebanada de la API móvil de Territorio reescrita en Spring Boot sobre la misma base, con Spring Security leyendo los mismos hashes que emite Laravel y altas idempotentes por UUID.',
            'stack' => ['Java / Spring Boot', 'JPA / Hibernate', 'PostgreSQL', 'Maven', 'Docker'],
        ],
    ],

    'aptitudes' => [
        ['grupo' => 'Backend', 'icono' => 'servidor', 'items' => ['PHP', 'Laravel', 'Livewire', 'Node.js', 'API REST', 'Webhooks', 'Colas', 'Java / Spring Boot', 'JPA / Hibernate']],
        ['grupo' => 'Frontend', 'icono' => 'ventana', 'items' => ['React', 'Inertia.js', 'Vue.js', 'TypeScript', 'HTML5', 'Tailwind CSS', 'shadcn/ui', 'Vite', 'Highcharts']],
        ['grupo' => 'Móvil', 'icono' => 'telefono', 'items' => ['React Native', 'Expo', 'Offline-first', 'SQLite', 'OCR (ML Kit)', 'Android / APK']],
        ['grupo' => 'Datos', 'icono' => 'base-datos', 'items' => ['PostgreSQL', 'Tratamiento de datos', 'Optimización de consultas', 'Caché', 'Excel / Exportaciones', 'PDF / Documentos', 'Backups / pg_restore']],
        ['grupo' => 'Geoespacial y datos públicos', 'icono' => 'mapa', 'items' => ['Google Maps API', 'MapLibre GL', 'Mapas dinámicos', 'KML / GeoJSON', 'Mapas SVG', 'PostGIS', 'Shapefiles / INE', 'Datos del INE', 'Datos del INEGI']],
        ['grupo' => 'Arquitectura', 'icono' => 'capas', 'items' => ['OOP', 'Patrones de diseño', 'MVC', 'Sistemas escalables']],
        ['grupo' => 'Autenticación y control de acceso', 'icono' => 'escudo', 'items' => ['Roles y permisos (RBAC)', 'Autenticación por token', 'Passkeys / WebAuthn', 'Autenticación en dos pasos', 'Bitácora de auditoría']],
        ['grupo' => 'Integraciones', 'icono' => 'enchufe', 'destacado' => true, 'items' => ['SAT / CFDI', 'Mercado Libre', 'WhatsApp Cloud API', 'Dualhook', 'Pasarelas de pago', 'S3 API']],
        ['grupo' => 'IA y automatización', 'icono' => 'chispa', 'items' => ['IA generativa', 'Visión por computadora', 'Salidas estructuradas (JSON Schema)', 'n8n', 'Chatbots (WhatsApp / FSM)']],
        ['grupo' => 'Infraestructura', 'icono' => 'nube', 'items' => ['Git', 'GitHub', 'VPS', 'Cloudflare', 'Despliegue continuo']],
        ['grupo' => 'DevOps / Seguridad', 'icono' => 'escudo', 'items' => ['Docker', 'Docker Compose', 'Linux (AlmaLinux/RHEL)', "Let's Encrypt / TLS", 'SELinux', 'Tailscale', 'MinIO (S3)', 'Endurecimiento SSH', 'Auditoría de servidores', 'Monitoreo / Netdata']],
        ['grupo' => 'Negocio', 'icono' => 'grafica', 'items' => ['Business Intelligence', 'Dashboards', 'Scrum', 'PMBOK', 'Mentoría técnica']],
    ],

    // Las cuatro de la frase de arriba.
    'especialidad' => ['Laravel', 'PHP', 'React', 'PostgreSQL'],

    // Lo que va resaltado en el listado, además del grupo marcado como destacado.
    'destacadas' => ['Laravel', 'PHP', 'React', 'PostgreSQL', 'React Native', 'PMBOK'],

    // Items con acento naranja (destaque especial, aparte del resaltado normal).
    'acento' => ['MinIO (S3)'],

    'educacion' => [
        [
            'periodo' => '2008 – 2013',
            'titulo' => 'Ingeniería en Cibernética y Sistemas Computacionales',
            'institucion' => 'Universidad La Salle Pachuca',
            'lugar' => 'Pachuca, Hidalgo',
        ],
    ],

    'certificaciones' => [
        [
            'nombre' => 'PMBOK',
            'emisor' => 'La Salle Pachuca',
            'detalle' => 'Gestión de proyectos: alcance, tiempo, costo y riesgos.',
        ],
        [
            'nombre' => 'Cybersecurity Audit (UCA)',
            'emisor' => 'Utel · Outstanding',
            'detalle' => 'Auditoría ISO 27001/NIST, controles y remediación.',
        ],
        [
            'nombre' => 'CyberSecurity Fundamentals (UCSF)',
            'emisor' => 'Utel · Outstanding',
            'detalle' => 'CIA triad, redes, criptografía y respuesta a incidentes.',
        ],
    ],

    'habilidades' => [
        'Liderazgo técnico',
        'Comunicación clara',
        'Gestión del tiempo (PMBOK)',
        'Resolución de problemas',
        'Mentoría de equipos',
        'Resiliencia bajo presión',
    ],

];
