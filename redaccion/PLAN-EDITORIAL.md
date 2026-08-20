# Plan editorial

Primera tanda, agosto de 2026. Trece piezas, todas nacidas de la inspección en
`investigacion/inventario-proyectos.md`.

## El criterio

Nada de contenido genérico. Cada pieza se sostiene en algo que existe en un
proyecto real de `C:\laragon\www`, con archivo y línea localizables. Si un tema
no tiene evidencia detrás, se queda en `revision` con la nota de por qué.

Tres tipos, tres funciones distintas:

- **Artículo** — una opinión defendida con código propio. Es lo que un
  reclutador lee para saber cómo piensas.
- **Tutorial** — pasos reproducibles. Es lo que trae tráfico de búsqueda.
- **Recurso** — algo descargable o copiable.

## La tanda

| # | Título | Tipo | Estado | Evidencia |
| --- | --- | --- | --- | --- |
| 1 | UUID en Laravel: por qué usarlos y qué beneficios traen | Artículo | publicado | AutoVal y taxis, 3 migraciones |
| 2 | Laravel Auditing: instalarlo no es auditar | Artículo | publicado | 13 proyectos con el paquete, 4 modelos auditados |
| 3 | Por qué genero los PDF con FPDF y no con HTML | Artículo | publicado | `VehiculoFichaPdf`, 6 generadores más |
| 4 | Exportar a Excel en Laravel sin tumbar el servidor | Artículo | publicado | `app/Exports/` de AutoVal, openspout en territorio |
| 5 | n8n y Laravel: recibir WhatsApp sin duplicar nada | Artículo | publicado | `ReporteIngestController`, `VerifyIngestToken` |
| 6 | Wayfinder: el año que dejé de usar Ziggy | Artículo | publicado | Corte Laravel 12 → 13 en 13 proyectos |
| 7 | El kit que reutilizo en cada proyecto (y cuándo lo suelto) | Artículo | publicado | Conteo 9/9 en 7 proyectos, 0/9 en otros |
| 8 | Instalar Laravel desde cero en Windows con Laragon | Tutorial | publicado | Entorno real de trabajo |
| 9 | Laravel con React e Inertia, del cero al primer CRUD | Tutorial | publicado | El starter propio |
| 10 | Roles y permisos con spatie/laravel-permission | Tutorial | publicado | `Permiso` enum de taxis y de este blog |
| 11 | Laracollab: gestión de proyectos en Laravel y React | Artículo | publicado | Lectura de código de `lara-collab` |
| 12 | Bagisto: el framework de e-commerce en Laravel y Vue | Artículo | **revisión** | Sin instalación local. Ver abajo. |
| 13 | Checklist de arranque de un proyecto Laravel | Recurso | publicado | Los descuidos que encontró el inventario |

## Lo que queda pendiente y por qué

**Bagisto (#12) no se publica todavía.** No está instalado en la máquina y no
tengo horas de uso con él. El borrador está escrito con lo que se puede
sostener desde la documentación y el repositorio público, y dice explícitamente
que es una evaluación previa, no experiencia. Para pasarlo a `publicado` hay
que levantarlo, montar una tienda de prueba y volver a escribir la mitad.

**Segunda tanda, ya con material identificado:**

- PostGIS con `clickbar/laravel-magellan`, sobre `taxis`.
- Cruzar cartografía del INE y del INEGI, sobre `electoral_bacheo`.
- Multi-inquilino en `agendix`, con expedientes clínicos, consentimientos y
  bitácora de acceso — hay material de sobra sobre datos sensibles.
- Visión por computadora para clasificar fotos de baches (`AnalizarBacheJob`).
- Facturación CFDI: `ServicioFacturacionSw`, timbrado y cancelación.
- `orangehill/iseed`: generar seeders desde una base en producción.

## Notas de estilo

- Primera persona del singular. El sitio es de una persona, no de una agencia.
- El código va con la ruta del archivo del que salió.
- Nada de «en este artículo veremos». Se entra directo.
- Los números se dicen: 13 proyectos, 4 modelos, 22 permisos.
- Si algo salió mal, se cuenta. Es lo que hace creíble el resto.

---

# Segunda tanda · agosto de 2026

Doce piezas más, salidas de `investigacion/inventario-node.md`: la inspección de
`C:\Users\chain\OneDrive\Desktop\node`, donde viven los bots de n8n y las
aplicaciones de React Native.

Es material distinto al de la primera tanda —ahí todo era Laravel— y abre tres
categorías nuevas: **Móvil**, **Geoespacial** y más peso en **Seguridad**.

| # | Título | Tipo | Estado | Evidencia |
| --- | --- | --- | --- | --- |
| 14 | Leer la credencial del INE: el código del reverso ya no sirve | Artículo | publicado | `territorio-app/docs/PLAN.md`, medición sobre 3,000 claves |
| 15 | Offline-first en React Native: la pregunta es para quién | Artículo | publicado | La tabla de roles y volumen, 235,613 registros |
| 16 | Un motor de sincronización que no pierde capturas | Artículo | publicado | Los cinco desenlaces de la cola de salida |
| 17 | Dónde vive el estado de un bot de WhatsApp | Artículo | publicado | `pipe-dualhook/sql/01_conversaciones.sql` |
| 18 | El consentimiento como barrera de verdad | Artículo | publicado | `PIPE_AVISO_VERSION`, la baja por palabra |
| 19 | Diseñar un bot que no canse | Artículo | publicado | Las tres decisiones que quitaron cuatro preguntas |
| 20 | Geocodificación inversa con PostGIS | Tutorial | publicado | `reporta-bache/sql/02_geocodificacion.sql` |
| 21 | Deduplicar por cercanía: ST_DWithin | Tutorial | publicado | `bache_cercano()`, geography contra geometry |
| 22 | PostGIS en Laravel sin escribir SQL a mano | Tutorial | publicado | Migraciones de `taxis` con magellan |
| 23 | Salir de Expo Go tiene un precio | Artículo | publicado | ML Kit, development build, degradación |
| 24 | Datos sensibles: hay que saber para qué | Artículo | publicado | `ClinicalRecordAccessLog` de agendix |
| 25 | Cómo probar un bot de WhatsApp sin WhatsApp | Tutorial | publicado | Los tres proyectos de n8n |

## Lo que sigue teniendo material sin escribir

- Visión por computadora para clasificar fotos (`AnalizarBacheJob`).
- Facturación CFDI: timbrado, cancelación y factura global.
- `orangehill/iseed`: generar seeders desde una base en producción.
- Multi-inquilino a fondo, más allá del expediente clínico.
- `konfido-erp-front`: el único frontend sin Inertia del inventario. Cuándo un
  frontend separado sí se justifica. Necesita una lectura de código que no hice.
- Las dos apps `agentis_*`: mismo patrón offline-first, pero **sin documentación**.
  Habría que leerlas a fondo antes de escribir nada.
