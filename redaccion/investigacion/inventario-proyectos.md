# Inventario de proyectos · notas de campo

Inspección de los proyectos de `C:\laragon\www` con primer commit en 2026.
Todo lo que sigue salió de leer `composer.json`, migraciones, modelos y
controladores reales, no de memoria. Es la materia prima de los borradores.

Fecha de la inspección: agosto de 2026.

---

## 1. Hay un stack, y es muy consistente

Veintiocho proyectos nacieron este año. Trece son aplicaciones completas con
backend Laravel. De esos trece, **todos sin excepción** traen:

| Paquete | En cuántos | Para qué |
| --- | --- | --- |
| `spatie/laravel-permission` | 13 / 13 | Roles y permisos |
| `owen-it/laravel-auditing` | 13 / 13 | Auditoría de modelos |
| `orangehill/iseed` | 13 / 13 | Generar seeders desde la base |
| `league/flysystem-aws-s3-v3` | 13 / 13 | Archivos en S3 / MinIO |
| `laravel/fortify` | 12 / 13 | Autenticación |
| `inertiajs/inertia-laravel` | 12 / 13 | Inertia + React |
| `maatwebsite/excel` | 12 / 13 | Exportación a Excel |
| `codedge/laravel-fpdf` | 12 / 13 | Generación de PDF |
| `laravel/wayfinder` | 12 / 13 | Rutas tipadas en el frontend |

La excepción de Inertia y Fortify es `nodos_livewire`, que es el único de este
año construido con Livewire + Flux.

**Esto no es casualidad, es un kit.** Y el kit tiene versiones: los proyectos
de febrero a junio corren Laravel 12 con PHP 8.2, y los de julio en adelante
Laravel 13.17 con PHP 8.3.

### El corte de Laravel 13

Comparando las dos generaciones aparecen tres cambios claros:

- **Ziggy desaparece.** `tightenco/ziggy` está en los ocho proyectos de
  Laravel 12 y en ninguno de los cinco de Laravel 13. Wayfinder lo reemplazó.
- **`wildside/userstamps` desaparece** por el mismo corte.
- **Entran `laravel/ai`, `laravel/chisel`, `laravel-lang/common` y
  `spatie/laravel-medialibrary`** en los cinco de Laravel 13.

→ *Material para el artículo de Wayfinder.*

---

## 2. Dos linajes del kit base

Nueve modelos aparecen calcados en varios proyectos: `CatUsoCfdi`,
`CatFormaPagoSat`, `CatRegimenFiscale`, `CatSatClaveUnidade`, `CatSatServicio`,
`CatEstado`, `CatMunicipio`, `Seguimiento`, `File`.

Conteo real de los nueve:

| Proyecto | Kit | Qué es |
| --- | --- | --- |
| AutoVal | 9/9 | Avalúo, inventario y venta de vehículos |
| POS | 9/9 | Punto de venta y ERP |
| agendix | 9/9 | Agenda clínica multi-inquilino |
| best_life | 9/9 | — |
| electoral_bacheo | 9/9 | Reportes ciudadanos + inteligencia electoral |
| motor_erp | 9/9 | Taller y publicación en Mercado Libre |
| tickets | 9/9 | Mesa de ayuda con IA |
| suite_fiel | 3/9 | Plataforma de gestión gubernamental |
| electoral-new | 2/9 | Inteligencia electoral |
| territorio | 1/9 | Gestión territorial y social |
| agentes_seguros | 1/9 | Agentes de seguros |
| taxis | 0/9 | Despacho de taxis |
| miguel_hidalgo | 0/9 | — |

**El kit se bifurcó.** La rama comercial —la que factura— arrastra los
catálogos del SAT completos porque los necesita para timbrar. La rama de
gobierno y territorio los soltó: ahí no se factura, y cargar veinte catálogos
fiscales que nadie va a usar sólo estorba.

→ *Material para el artículo del kit base.* El ángulo honesto no es «reutiliza
todo», es «reutiliza y sabe cuándo soltar».

---

## 3. UUID: el mismo patrón, dos técnicas

Tres migraciones de este año agregan UUID a tablas que ya existían.

**`AutoVal/database/migrations/2026_06_15_100000_add_id_uuid_to_empresas_table.php`**
y la de `inventario_reparaciones` lo hacen desde PHP:

```php
$table->uuid('id_uuid')->nullable()->unique()->after('id');

DB::table('empresas')->orderBy('id')->each(function ($empresa) {
    DB::table('empresas')->where('id', $empresa->id)
        ->update(['id_uuid' => Str::uuid()->toString()]);
});

$table->uuid('id_uuid')->nullable(false)->change();
```

**`taxis/database/migrations/2026_07_28_120000_add_uuid_a_servicios.php`** lo
hace desde PostgreSQL:

```php
DB::statement('UPDATE servicios SET uuid = gen_random_uuid() WHERE uuid IS NULL');
DB::statement('ALTER TABLE servicios ALTER COLUMN uuid SET DEFAULT gen_random_uuid()');
DB::statement('ALTER TABLE servicios ALTER COLUMN uuid SET NOT NULL');
```

Las dos son la misma jugada: **UUID como identificador público, `id` como
llave interna**. Ninguna de las dos cambia la llave primaria.

La segunda es mejor y vale la pena decir por qué: con `DEFAULT gen_random_uuid()`
la base garantiza el UUID aunque el registro se inserte desde un seeder, desde
psql o desde otro servicio. Con la primera, un `INSERT` que no pase por el
modelo deja el campo vacío.

Detalle nada menor: la de tres pasos —columna nula, rellenar, poner NOT NULL—
es lo que permite hacerlo sobre una tabla con datos sin bloquearla ni fallar.

→ *Material para el artículo de UUID.*

---

## 4. Auditoría: instalada en trece, usada en cuatro

`owen-it/laravel-auditing` está en el `composer.json` de los trece proyectos.
Los modelos que realmente implementan `AuditableContract`:

- `AutoVal`: `Empresa`, `EmpresaCatalogoConfig`, `Solicitud`, `User` → **4 de 64 modelos.**
- `POS`, `taxis`, `territorio`: ninguno.

El `config/audit.php` sí está publicado y configurado, con los resolvers de
IP, URL, user agent y usuario, y `AUDITING_ENABLED` por entorno.

**Este es el hallazgo más incómodo de la inspección y el mejor material.**
Instalar el paquete no audita nada: hay que ir modelo por modelo. Y los cuatro
que sí lo tienen en AutoVal son exactamente los correctos —la empresa, su
configuración, la solicitud y el usuario—, así que la decisión fue consciente,
no olvido. Pero en POS, que mueve dinero, no hay ninguno.

→ *Material para el artículo de Laravel Auditing.* El título honesto es
«instalarlo no es auditar».

---

## 5. PDF: FPDF por coordenadas, no HTML

Contra lo que uno esperaría, no hay una sola plantilla Blade convertida a PDF.
Todo se dibuja con FPDF extendiendo la clase.

**`AutoVal/app/Support/Pdf/VehiculoFichaPdf.php`** es el ejemplo bueno:

```php
class VehiculoFichaPdf extends Fpdf
{
    private const ROJO  = [187, 10, 33];
    private const TINTA = [15, 23, 42];
    private const MARGIN = 18.0;

    public function __construct()
    {
        parent::__construct('P', 'mm', 'A4');
        $this->SetAutoPageBreak(true, 24);
        $this->SetMargins(self::MARGIN, 38, self::MARGIN);
        $this->AliasNbPages();
    }

    public function Header(): void { /* franja negra, logo, datos */ }
}
```

Su propio comentario lo dice: «dibujada por coordenadas con FPDF. Sin
HTML/CSS/Blade».

Otros generadores, todos con el mismo enfoque:
`POS/app/Actions/Inventario/GenerarPdfTransferenciaInventarioAction.php`,
`GenerarPdfPedidoAction`, `GenerarPdfOrdenProduccionAction`,
`POS/app/Services/Facturacion/FacturaPdfRenderer.php`,
`taxis/.../FacturaPdfRenderer.php`, `AutoVal/app/Http/Controllers/SolicitudPdfController.php`.

Hay incluso un `AutoVal/app/Support/Pdf/PdfImageResolver.php`, porque FPDF
necesita rutas locales y las imágenes viven en S3.

→ *Material para el artículo de PDF.* El ángulo: por qué un documento fiscal
no se maqueta con HTML.

---

## 6. Excel: exports pequeños y declarativos

`AutoVal/app/Exports/` tiene cinco: `VentasPipelineExport`,
`InventarioMovimientosExport`, `MercadoLibrePerformanceExport`,
`ClientesContactosExport`, `SolicitudesInventarioResumenExport`.

El patrón, de `VentasPipelineExport`:

```php
class VentasPipelineExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(private readonly int $empresaId) {}

    public function collection(): Collection
    {
        return Venta::query()->with(['evaluacion', 'cliente', 'vendedor'])
            ->whereHas('evaluacion', fn ($q) => $q->where('id_empresa', $this->empresaId))
            ->orderByDesc('created_at')->get()
            ->map(fn (Venta $venta) => [ /* columnas */ ]);
    }
}
```

Dos cosas destacan: el `empresaId` viaja por el constructor —el export no
puede filtrarse mal por accidente— y el `->with()` evita el N+1 que en un
export de miles de filas sí se siente.

`POS` tiene uno solo, `ReporteFilasExport`, genérico. Y `territorio` es el
único que además trae `openspout/openspout`, que es la vía cuando el archivo
ya no cabe en memoria.

→ *Material para el artículo de Excel.*

---

## 7. n8n como front de WhatsApp

`electoral_bacheo` es el caso más interesante de integración. El flujo real:
WhatsApp → n8n → un endpoint de Laravel.

**`app/Http/Middleware/VerifyIngestToken.php`**:

```php
$expected = config('services.n8n.ingest_token');
$provided = $request->bearerToken() ?: $request->header('X-Ingest-Token');

if (empty($expected) || empty($provided) || ! hash_equals($expected, $provided)) {
    return response()->json(['ok' => false, 'message' => 'Token de ingesta inválido.'], 401);
}
```

`hash_equals` y no `===`, que es el detalle que casi nadie pone.

**`app/Http/Controllers/Api/ReporteIngestController.php`** recibe el reporte ya
procesado, deduplica, geocodifica y registra. Con dos decisiones que valen un
artículo entero:

```php
// Notificación interna. Va después del commit, así que un fallo aquí no
// puede tumbar la respuesta: el reporte ya está guardado y n8n reintentaría
// un POST que ya surtió efecto.
$this->sinRomperLaRespuesta(...);
```

Y si el bache es nuevo y trae foto, dispara `AnalizarBacheJob` — la estimación
por visión de computadora — en segundo plano.

También hay `FueraDeCoberturaException` devolviendo 422 con la lista de
municipios cubiertos, un `GeocodificarBaches` como comando de consola, y
`ShortLink` para las ligas que se mandan por WhatsApp.

→ *Material para el artículo de n8n.* El tema real no es n8n, es **la
idempotencia cuando el que llama reintenta**.

---

## 8. Permisos: enum + seeder automático

`taxis/app/Enums/Permiso.php` tiene 22 casos con formato `modulo.accion`. Su
propio docblock explica la convención:

> `ver` es consulta; `gestionar` incluye alta, edición y baja (los
> controladores operan por upsert, así que no tiene sentido separar crear de
> editar). La operación diaria sí se desglosa por acción, porque un checador
> puede formar taxis pero no despachar ni cancelar un servicio.
> El RoleSeeder crea automáticamente cada caso que se agregue aquí.

Ese último renglón es la clave: **el enum es la fuente de verdad y el seeder
lo sigue**. No hay una lista de permisos en la base que se pueda desincronizar
del código.

→ *Material para el artículo de spatie/laravel-permission.*

---

## 9. PostGIS de verdad

`taxis` trae `clickbar/laravel-magellan`, más `endroid/qr-code` y
`picqer/php-barcode-generator`. Modelos: `Servicio`, `Operador`, `Taxi`,
`Turno`, `Corte`, `CorteLiquidacion`, `Tarifa`, `ZonaTarifa`, `Terminal`.

`electoral_bacheo` cruza `InegiSeccion`, `AgebNse`, `Manzana`, `Colonia`,
`ResultadoElectoral` — cartografía del INE y del INEGI sobre PostGIS.

→ *Reserva para una segunda tanda; el tema es grande y da para varias piezas.*

---

## 10. Lo que NO pude verificar

**Bagisto no está en la máquina.** No hay carpeta, no hay `composer.json`, no
hay rastro. Cualquier cosa que escriba sobre Bagisto sale de documentación, no
de haberlo instalado. El borrador queda en `revision` y con una nota, hasta
probarlo de verdad.

**Laracollab sí está**, en `C:\laragon\www\lara-collab`, con tres commits de
septiembre de 2025 — o sea clonado y mirado, no operado. Laravel 11, con
`laraveldaily/laravel-invoices`, `pusher/pusher-php-server`,
`spatie/eloquent-sortable`, `overtrue/laravel-favorite`,
`lacodix/laravel-model-filter` e `itsgoingd/clockwork`. Da para una reseña
honesta de lectura de código, no para un caso de uso en producción. Se dice en
el artículo.
