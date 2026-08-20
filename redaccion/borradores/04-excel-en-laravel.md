---
titulo: Exportar a Excel en Laravel sin tumbar el servidor
slug: exportar-excel-en-laravel
tipo: post
estado: publicado
categoria: Documentos
etiquetas: [laravel, excel, rendimiento, eloquent]
resumen: maatwebsite/excel resuelve el 90% de los casos con cuatro líneas. El otro 10% es el reporte que alguien pide con dos años de movimientos y deja al servidor sin memoria. Cómo distingo uno del otro.
meta_descripcion: Exportar a Excel en Laravel con maatwebsite/excel, evitar el N+1 en exports, usar FromQuery y colas, y cuándo cambiar a openspout para archivos grandes.
hace_dias: 25
---

En doce de mis trece proyectos de este año está `maatwebsite/excel`. Es de esos
paquetes que instalas sin pensarlo. Y con razón: la exportación más común se
resuelve en un archivo de veinte líneas.

El problema aparece más tarde, cuando alguien de contabilidad pide «el reporte
completo» y el completo son cuatrocientas mil filas.

## El caso normal

Así se ve un export real, de una plataforma de venta de vehículos:

```php
// app/Exports/VentasPipelineExport.php
namespace App\Exports;

use App\Models\Venta;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VentasPipelineExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(private readonly int $empresaId) {}

    public function collection(): Collection
    {
        return Venta::query()
            ->with(['evaluacion', 'cliente', 'vendedor'])
            ->whereHas('evaluacion', fn ($query) => $query->where('id_empresa', $this->empresaId))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Venta $venta) => [
                'VIN' => $venta->evaluacion?->vin,
                'Cliente' => $venta->cliente?->nombre,
                'Vendedor' => $venta->vendedor?->name,
                'Precio de venta' => $venta->precio_venta,
                'Saldo' => $venta->saldo,
                'Fecha de venta' => optional($venta->fecha_venta)->toDateString(),
            ]);
    }

    public function headings(): array
    {
        return ['VIN', 'Cliente', 'Vendedor', 'Precio de venta', 'Saldo', 'Fecha de venta'];
    }
}
```

Y se descarga con una línea:

```php
return Excel::download(new VentasPipelineExport($empresa->id), 'pipeline.xlsx');
```

Dos detalles que no son adorno.

**El `empresaId` entra por el constructor, tipado y `readonly`.** En una
aplicación multi-empresa, un export que se le olvide filtrar es una fuga de
datos entre clientes. Recibirlo como dependencia obligatoria significa que no
existe la manera de construir el objeto sin decidir de quién son los datos.

**El `->with()` evita el N+1.** Sin esa línea, cada fila dispara tres consultas
—evaluación, cliente, vendedor—. En diez filas ni se nota; en diez mil son
treinta mil consultas y el export tarda minutos. Es el error más común que veo
en exports, porque en desarrollo con datos de prueba nunca se manifiesta.

## Dónde se rompe

`FromCollection` hace exactamente lo que dice: trae **toda** la colección a
memoria, la convierte en objetos de Eloquent y después arma el archivo. Para
cinco mil filas está bien. Para cuatrocientas mil, PHP se queda sin memoria y
lo que ve el usuario es una pantalla en blanco o un 500.

El primer arreglo es no traer objetos:

```php
use Maatwebsite\Excel\Concerns\FromQuery;

class VentasPipelineExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function query()
    {
        return Venta::query()
            ->with(['evaluacion', 'cliente', 'vendedor'])
            ->whereHas('evaluacion', fn ($q) => $q->where('id_empresa', $this->empresaId))
            ->orderByDesc('created_at');
    }

    public function map($venta): array
    {
        return [ /* las mismas columnas */ ];
    }
}
```

Con `FromQuery` el paquete pagina la consulta solo, en lotes, y va escribiendo.
El pico de memoria deja de crecer con el número de filas.

Ojo con una trampa: `FromQuery` **necesita un `orderBy`**. Sin orden explícito,
la base no garantiza que la página 2 no repita filas de la página 1, y acabas
con un archivo que tiene registros duplicados y otros ausentes. Es un error
silencioso, de los peores.

Y quitar `ShouldAutoSize` en exports grandes. Ajustar el ancho de las columnas
obliga a la librería a medir el contenido de todas las celdas antes de cerrar el
archivo, lo que anula buena parte de lo que ganaste. En un reporte de trabajo,
que las columnas salgan angostas es un inconveniente menor.

## El reporte que de plano no cabe

Cuando ni así alcanza, hay que dejar de hacerlo en la petición HTTP. Un usuario
no va a esperar cuatro minutos con la pestaña abierta, y el servidor web
probablemente lo corte antes por tiempo de espera.

```php
use Maatwebsite\Excel\Concerns\Exportable;

(new VentasPipelineExport($empresa->id))
    ->queue('reportes/pipeline-'.now()->timestamp.'.xlsx', 's3')
    ->chain([
        new AvisarReporteListo($usuario, $ruta),
    ]);
```

`queue()` parte el export en trabajos por lote, los manda a la cola y al final
encadena lo que quieras: un correo con el enlace, una notificación en el panel.
El usuario pide el reporte, recibe «te aviso cuando esté» y sigue trabajando.

Este es el patrón que uso para cualquier export que en desarrollo pase de los
diez segundos. La regla que aplico: si no sé cuántas filas puede traer, va a la
cola.

## Cuando el problema ya no es Laravel

`maatwebsite/excel` es una capa sobre PhpSpreadsheet, y PhpSpreadsheet construye
un modelo del libro completo en memoria: cada celda es un objeto con su valor,
su formato, su estilo. Eso es lo que te da poder escribir fórmulas, combinar
celdas y pintar encabezados. Y es lo que hace que cada celda cueste bytes.

Cuando el archivo es enorme y **no necesita formato**, el modelo entero sobra.
Ahí entra `openspout/openspout`, que escribe el XLSX como flujo: fila por fila,
sin guardar nada. En uno de mis proyectos de gestión territorial, el de plantillas
de importación con decenas de miles de registros, es la única dependencia de
Excel que hay.

```php
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;

$writer = new Writer();
$writer->openToFile($ruta);
$writer->addRow(Row::fromValues(['VIN', 'Cliente', 'Precio']));

Venta::query()->orderBy('id')->lazy()->each(function (Venta $venta) use ($writer) {
    $writer->addRow(Row::fromValues([$venta->vin, $venta->cliente?->nombre, $venta->precio_venta]));
});

$writer->close();
```

El `lazy()` de Eloquent y el escritor de flujo se complementan: ninguno de los
dos junta el conjunto completo. El consumo de memoria es plano, sin importar si
son mil filas o un millón.

Lo que pierdes es todo el formato. Sin estilos, sin anchos, sin fórmulas. Para
un archivo que alguien va a abrir y filtrar, sirve. Para un reporte que se
presenta a un director, no.

## Lo que decidí después de revisar mis proyectos

Tenía cinco exports en un proyecto y uno solo en otro que es cuatro veces más
grande. Eso último no es virtud, es deuda: el proyecto grande resuelve las
exportaciones con un `ReporteFilasExport` genérico al que le pasan arreglos ya
armados desde el controlador. Funciona, pero la lógica del reporte quedó
regada en los controladores en vez de vivir en una clase con nombre.

El criterio con el que estoy trabajando ahora:

| Filas esperadas | Cómo |
| --- | --- |
| Hasta ~5 000 | `FromCollection` con `->with()`, descarga directa |
| 5 000 – 100 000 | `FromQuery` con `orderBy`, sin `ShouldAutoSize` |
| Más, o desconocido | `->queue()` a S3 y avisar por correo |
| Cientos de miles sin formato | `openspout` con `lazy()` |

Y una clase por reporte, con su nombre, aunque sean veinte líneas. El día que
alguien pregunte por qué el pipeline no cuadra, quieres un archivo que se llame
`VentasPipelineExport` y no buscar en un controlador de seiscientas líneas.
