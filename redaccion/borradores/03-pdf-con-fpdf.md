---
titulo: Por qué genero los PDF con FPDF y no con HTML
slug: pdf-en-laravel-con-fpdf
tipo: post
estado: borrador
categoria: Documentos
etiquetas: [laravel, pdf, fpdf, facturacion]
resumen: Convertir una plantilla Blade a PDF es lo cómodo. Después llega la factura que debe salir idéntica siempre, y el navegador headless que se come 300 MB por documento. Llevo doce proyectos dibujando por coordenadas.
meta_descripcion: 'Generar PDF en Laravel con codedge/laravel-fpdf en lugar de HTML a PDF: cuándo conviene cada uno, cómo estructurar la clase y cómo resolver imágenes que viven en S3.'
hace_dias: 18
---

Hay dos maneras de generar un PDF en Laravel y llevan siete años discutiéndose.

La primera es maquetar en Blade y convertir: `barryvdh/laravel-dompdf`,
`spatie/laravel-pdf` con Browsershot, wkhtmltopdf. Escribes HTML y CSS, que ya
sabes, y sale un PDF.

La segunda es dibujar el documento por coordenadas con FPDF. Escribes «pon este
texto a 18 milímetros del margen izquierdo y 42 del superior».

Revisando mis proyectos de este año me encontré con que en los doce que generan
PDF —facturas, órdenes de trabajo, transferencias de inventario, fichas técnicas,
pedidos— no hay **una sola** plantilla Blade convertida. Todo es FPDF. No fue
una decisión de arquitectura tomada en una reunión; fue el resultado de que la
otra vía se cayó tres veces.

## Por qué se cayó la otra vía

**El navegador headless pesa.** Browsershot y wkhtmltopdf levantan un Chromium
por documento. En un VPS modesto, generar cincuenta facturas en un lote significa
cincuenta arranques de navegador, y el pico de memoria tumba el proceso. Se
puede resolver con una cola y un pool, pero acabas manteniendo infraestructura
de navegador para imprimir un papel.

**El HTML a PDF sin navegador miente.** DomPDF no es un navegador: su soporte de
CSS se queda en una versión de 2011. Flexbox no existe, grid no existe, y el
posicionamiento absoluto funciona a ratos. Terminas maquetando con tablas
anidadas como en 2003, y aun así el resultado se mueve entre versiones.

**Y lo que rompió el empate: una factura no puede moverse.** El documento fiscal
tiene un tamaño de papel, unos márgenes y una posición para cada dato. Si el
cliente tiene un nombre largo y el bloque empuja tres milímetros hacia abajo, el
sello se encima con el pie. Con HTML, lo que controlas es una sugerencia que el
motor interpreta. Con coordenadas, lo que controlas es dónde queda la tinta.

## Cómo queda el código

Se instala `codedge/laravel-fpdf`, que es FPDF empaquetado para Laravel, y se
extiende la clase.

Esta es la estructura de una ficha técnica de vehículo, de una plataforma de
avalúos:

```php
// app/Support/Pdf/VehiculoFichaPdf.php
namespace App\Support\Pdf;

use Codedge\Fpdf\Fpdf\Fpdf;

class VehiculoFichaPdf extends Fpdf
{
    private const ROJO  = [187, 10, 33];    // acento de marca
    private const TINTA = [15, 23, 42];     // texto principal
    private const GRIS  = [100, 116, 139];  // texto secundario
    private const BORDE = [226, 232, 240];  // líneas
    private const MARGIN = 18.0;

    public function __construct()
    {
        parent::__construct('P', 'mm', 'A4');
        $this->SetAutoPageBreak(true, 24);
        $this->SetMargins(self::MARGIN, 38, self::MARGIN);
        $this->AliasNbPages();
    }

    public function Header(): void
    {
        // Franja superior, logo, datos de la agencia.
    }

    public function Footer(): void
    {
        // Folio, fecha de generación, «Página 3 de 7».
    }
}
```

Cuatro decisiones que valen la pena señalar:

**La paleta va en constantes.** FPDF pide los colores como tres enteros RGB
sueltos: `$this->SetTextColor(15, 23, 42)`. Repetir esos números por todo el
archivo es la vía rápida a un documento con cuatro grises distintos. Con
constantes y un `SetTextColor(...self::TINTA)`, la marca es una sola.

**Las unidades son milímetros y el papel es A4.** Se declara una vez en el
constructor y todo el resto del archivo habla en milímetros, que es como está
especificado el papel. Deja de haber conversión mental de píxeles a nada.

**`Header()` y `Footer()` los llama FPDF solo.** No hay que invocarlos: se
ejecutan en cada `AddPage()` y al cerrar cada página. Es la manera limpia de que
un documento de siete hojas tenga el mismo encabezado en las siete sin repetir
una línea.

**`AliasNbPages()` habilita el «de N».** FPDF no sabe cuántas páginas va a tener
hasta que termina, así que reserva un marcador y lo sustituye al final. Sin esa
llamada, el `{nb}` sale literal en el papel.

Y el uso, desde un controlador:

```php
// app/Http/Controllers/VehiculoFichaPdfController.php
$pdf = new VehiculoFichaPdf();
$pdf->setEmpresa($empresa->nombre, $empresa->telefono, $empresa->email, $empresa->direccion, $logo);
$pdf->setGeneradoEn(now()->translatedFormat('d \d\e F \d\e Y, H:i'));
$pdf->AddPage();
$pdf->ficha($vehiculo);

return response($pdf->Output('S'), 200, [
    'Content-Type' => 'application/pdf',
    'Content-Disposition' => 'inline; filename="ficha-'.$vehiculo->vin.'.pdf"',
]);
```

`Output('S')` devuelve el PDF como cadena en lugar de mandarlo al navegador, que
es lo que hace falta para controlar los encabezados desde Laravel, adjuntarlo a
un correo o guardarlo en S3.

## El problema de las imágenes

Aquí está el detalle que no aparece en ningún tutorial y que te cuesta una tarde.

FPDF lee imágenes del sistema de archivos local. Le pasas una ruta y él la abre.
Pero en cualquier aplicación seria las imágenes —el logo del cliente, las fotos
del vehículo— viven en S3 o en MinIO, no en el disco del servidor web.

Terminé escribiendo una clase sólo para eso, `app/Support/Pdf/PdfImageResolver.php`,
que hace tres cosas: baja el archivo del disco remoto a un temporal, verifica que
sea una imagen de verdad y devuelve la ruta local; y al final del proceso limpia
los temporales.

La versión mínima de la idea:

```php
public function local(?string $ruta): ?string
{
    if ($ruta === null || ! Storage::disk(config('blog.disco'))->exists($ruta)) {
        return null;
    }

    $temporal = tempnam(sys_get_temp_dir(), 'pdf_').'.'.pathinfo($ruta, PATHINFO_EXTENSION);
    file_put_contents($temporal, Storage::disk(config('blog.disco'))->get($ruta));

    $this->temporales[] = $temporal;

    return $temporal;
}
```

Y el `null` importa: si el logo no está, el documento debe salir sin logo, no
reventar. Un PDF sin logo es un inconveniente; una factura que no se genera es
una venta detenida.

## Cuándo sí uso HTML a PDF

No soy dogmático. Hay un caso donde HTML gana con claridad: **documentos de
texto corrido y largo**, donde el contenido manda y el diseño se acomoda. Un
reporte de diez páginas con párrafos, subtítulos y tablas que fluyen es
miserable de dibujar por coordenadas y trivial en HTML, porque el salto de
página automático hace todo el trabajo.

La línea que uso:

| El documento | La herramienta |
| --- | --- |
| Formato fijo: factura, ficha, orden, ticket, etiqueta | FPDF |
| Texto corrido de largo variable: reportes, contratos | HTML a PDF |
| Alto volumen en lote | FPDF, sin dudarlo |

## Lo que cuesta

Ser justo con la alternativa: **FPDF es más lento de escribir**. Una ficha bien
armada son trescientas o cuatrocientas líneas de PHP, y la primera versión
siempre queda con algo tres milímetros fuera de lugar. Se itera generando el PDF
y mirándolo, no hay vista previa en vivo.

Tampoco es para que lo toque un diseñador. Si el cliente quiere mover un bloque,
lo mueves tú, en el código.

A cambio: no hay navegador, no hay dependencia de sistema, el consumo de memoria
es de kilobytes, y el documento sale idéntico hoy y dentro de tres años. Para lo
que imprimo —papel que alguien firma o que va al SAT— ese trato me sirve.
