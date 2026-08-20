<?php

/*
|--------------------------------------------------------------------------
| Iconos de las tecnologías
|--------------------------------------------------------------------------
|
| Cada elemento de "Con qué trabajo" lleva icono. Hay dos fuentes:
|
| - marcas: logotipos en public/assets/svg/stack/<archivo>.svg. Se pintan con
|   máscara CSS, así que heredan el color del tema. Para agregar uno nuevo,
|   descarga el SVG de simpleicons.org o devicon.dev, guárdalo ahí y añade
|   la línea aquí.
|
| - glifos: conceptos que no son una marca. Salen de Bootstrap Icons, que ya
|   está en el proyecto (nombre sin el prefijo "bi-").
|
| Lo que no aparezca en ninguno de los dos mapas se muestra sin icono.
|
*/

return [

    'marcas' => [
        'PHP' => 'php',
        'Laravel' => 'laravel',
        'Livewire' => 'livewire',
        'React' => 'react',
        'React Native' => 'react',
        'Inertia.js' => 'inertia',
        'Vue.js' => 'vuedotjs',
        'TypeScript' => 'typescript',
        'HTML5' => 'html5',
        'Tailwind CSS' => 'tailwindcss',
        'PostgreSQL' => 'postgresql',
        'Git' => 'git',
        'GitHub' => 'github',
        'Cloudflare' => 'cloudflare',
        'n8n' => 'n8n',
        'WhatsApp Cloud API' => 'whatsapp',
        'Google Maps API' => 'googlemaps',
        'Expo' => 'expo',
        'Docker' => 'docker',
        'Redis' => 'redis',
    ],

    'glifos' => [
        'API REST' => 'diagram-3',
        'PostGIS' => 'geo-alt',
        'Mapas dinámicos' => 'pin-map',
        'KML / GeoJSON' => 'filetype-xml',
        'Mapas SVG' => 'vector-pen',
        'Datos del INE' => 'people-fill',
        'Datos del INEGI' => 'bank',
        'Tratamiento de datos' => 'funnel',
        'Roles y permisos (RBAC)' => 'person-badge',
        'Optimización de consultas' => 'speedometer2',
        'Caché' => 'lightning-charge',
        'Colas' => 'stack',
        'OOP' => 'box',
        'Patrones de diseño' => 'diagram-2',
        'MVC' => 'layers',
        'Sistemas escalables' => 'arrows-angle-expand',
        'SAT / CFDI' => 'receipt',
        'Mercado Libre' => 'shop',
        'Pasarelas de pago' => 'credit-card',
        'IA generativa' => 'stars',
        'Visión por computadora' => 'eye',
        'VPS' => 'hdd-rack',
        'Despliegue continuo' => 'arrow-repeat',
        'Business Intelligence' => 'bar-chart-line',
        'Dashboards' => 'speedometer2',
        'Scrum' => 'kanban',
        'PMBOK' => 'clipboard-check',
        'Mentoría técnica' => 'people',
        'Docker Compose' => 'boxes',
        'Linux (AlmaLinux/RHEL)' => 'terminal',
        'Nginx' => 'hdd-network',
        "Let's Encrypt / TLS" => 'shield-lock',
        'SELinux' => 'shield-check',
        'firewalld' => 'shield-shaded',
        'fail2ban' => 'shield-exclamation',
        'Tailscale' => 'router',
        'MinIO (S3)' => 'bucket',
        'Endurecimiento SSH' => 'key',
        'Monitoreo / Netdata' => 'activity',
        'Chatbots (WhatsApp / FSM)' => 'chat-dots',
        'Java / Spring Boot' => 'cup-hot',
        'Backups / pg_restore' => 'database-check',
        'Vite' => 'lightning-charge-fill',
        'Webhooks' => 'diagram-3',
        'Maven' => 'box-seam',
        'JDBC' => 'plug-fill',
        'S3 API' => 'bucket',

        // Front y UI
        'shadcn/ui' => 'palette',
        'Highcharts' => 'graph-up',

        // Seguridad y control de acceso
        // (RBAC ya existe arriba; ver 'Roles y permisos (RBAC)')
        'Bitácora de auditoría' => 'journal-text',
        'Autenticación en dos pasos' => 'shield-lock-fill',
        'Passkeys / WebAuthn' => 'fingerprint',
        'Autenticación por token' => 'key-fill',

        // Datos, documentos y geografía
        'Excel / Exportaciones' => 'file-earmark-spreadsheet',
        'PDF / Documentos' => 'file-earmark-pdf',
        'MapLibre GL' => 'map',
        'GeoJSON' => 'globe-americas',
        'Shapefiles / INE' => 'pin-map',
        'SQLite' => 'database',

        // Móvil
        'Offline-first' => 'arrow-down-up',
        'OCR (ML Kit)' => 'card-text',
        'Android / APK' => 'phone',

        // Backend y servicios
        'Node.js' => 'hexagon',
        'WhatsApp (Baileys)' => 'whatsapp',
        'Dualhook' => 'plug',
        'Spring Security' => 'shield-check',
        'JPA / Hibernate' => 'diagram-2',
        'Bean Validation' => 'check2-square',
        'Auditoría de servidores' => 'search',
        'Salidas estructuradas (JSON Schema)' => 'braces',
    ],

];
