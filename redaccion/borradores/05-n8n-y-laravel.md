---
titulo: 'n8n y Laravel: recibir WhatsApp sin duplicar nada'
slug: n8n-y-laravel-recibir-whatsapp
tipo: post
estado: borrador
categoria: Integraciones
etiquetas: [n8n, laravel, whatsapp, api, automatizacion]
resumen: 'Los ciudadanos reportan baches por WhatsApp. n8n atiende la conversación, Laravel guarda el reporte. Lo difícil no fue conectarlos: fue que n8n reintenta, y un reintento no puede crear un bache de más.'
meta_descripcion: 'Integrar n8n con Laravel: endpoint de ingesta, autenticación con hash_equals, idempotencia frente a reintentos y por qué las notificaciones van después del commit.'
hace_dias: 32
importante: true
---

Un ciudadano manda una foto de un bache por WhatsApp. Diez segundos después
recibe un folio, y en el panel del ayuntamiento aparece el reporte geolocalizado,
con la calle resuelta y una estimación del tamaño del daño hecha por visión de
computadora.

En medio hay dos piezas: **n8n**, que atiende la conversación, y **Laravel**, que
es el dueño de los datos. Este artículo va de la costura entre las dos, que
resultó ser la parte interesante.

## Por qué n8n y no todo en Laravel

Podría haber hecho el bot con la API de WhatsApp directamente en Laravel. De
hecho es lo que haría hace tres años.

La conversación de un bot no es una petición HTTP, es una máquina de estados:
«hola» → pedir foto → pedir ubicación → confirmar → folio. Con reintentos,
mensajes fuera de orden, gente que manda la ubicación antes de la foto y gente
que abandona a la mitad. Modelar eso en un controlador es posible y es horrible:
acabas con una tabla de conversaciones, un `switch` gigante y ninguna manera de
ver en qué paso está nadie.

En n8n ese flujo es un diagrama. Se ve. Cuando el cliente pide «que también
pregunte la colonia», se arrastra un nodo y se conecta; no hay despliegue, no
hay migración.

**La división que quedó:** n8n se queda con la conversación, que cambia
seguido y es visual. Laravel se queda con los datos, las reglas de negocio y el
panel, que necesitan pruebas y control de versiones.

## Un solo endpoint

Cuando n8n terminó de hablar con la persona, hace un `POST` a Laravel con el
reporte ya armado. Un endpoint, no cinco:

```php
// routes/api.php
Route::middleware('ingest.token')->prefix('ingest')->group(function () {
    Route::post('reportes', [ReporteIngestController::class, 'store']);
    Route::post('reportes/{reporte}/confirmar', [ReporteIngestController::class, 'confirmar']);
});
```

Lo que llega ya viene procesado: la foto subida, la ubicación en coordenadas, el
teléfono normalizado. Laravel no habla con WhatsApp en ningún momento.

## La autenticación, con el detalle que casi nadie pone

n8n no tiene sesión ni usuario. Se autentica con un token compartido:

```php
// app/Http/Middleware/VerifyIngestToken.php
public function handle(Request $request, Closure $next): Response
{
    $expected = config('services.n8n.ingest_token');
    $provided = $request->bearerToken() ?: $request->header('X-Ingest-Token');

    if (empty($expected) || empty($provided) || ! hash_equals($expected, $provided)) {
        return response()->json([
            'ok' => false,
            'message' => 'Token de ingesta inválido.',
        ], 401);
    }

    return $next($request);
}
```

**`hash_equals` y no `===`.** Esta es la línea del artículo.

El operador `===` compara cadenas byte por byte y se detiene en la primera
diferencia. Eso significa que un token que falla en el primer carácter se
rechaza más rápido que uno que falla en el vigésimo. La diferencia son
nanosegundos, pero es medible con suficientes intentos, y permite reconstruir el
token carácter por carácter. Es un ataque de tiempo, y es real.

`hash_equals` compara siempre en el mismo tiempo, sin importar dónde esté la
diferencia. Está en PHP desde la versión 5.6 y no cuesta nada usarlo.

El `empty($expected)` tampoco es paranoia: si alguien despliega sin la variable
de entorno, sin esa comprobación un token vacío contra un esperado vacío pasaría
la validación y el endpoint quedaría abierto.

## El problema de verdad: n8n reintenta

Esto es lo que no aparece en los tutoriales de integración.

n8n, como cualquier orquestador serio, reintenta cuando no recibe respuesta. Y
«no recibe respuesta» incluye el caso en que **Laravel sí procesó todo y la
respuesta se perdió en el camino**: un tiempo de espera agotado, un reinicio del
servidor web, un corte de red del lado de n8n.

Desde afuera, «procesado pero sin respuesta» y «no procesado» se ven idénticos.
Así que n8n reintenta. Y si el endpoint es ingenuo, el ciudadano acaba con tres
reportes del mismo bache y el ayuntamiento manda tres cuadrillas.

La respuesta no es «que n8n no reintente». Los reintentos son lo que hace que el
sistema aguante que se caiga la red. La respuesta es que **el endpoint tolere
que lo llamen dos veces**.

En el controlador, el servicio de ingesta deduplica antes de crear:

```php
// app/Http/Controllers/Api/ReporteIngestController.php
public function store(StoreReporteRequest $request, ReportIngestionService $service, NotificacionService $notif): JsonResponse
{
    try {
        $result = $service->ingest($request->validated());
    } catch (FueraDeCoberturaException $e) {
        return response()->json([
            'ok' => false,
            'motivo' => 'fuera_de_cobertura',
            'mensaje' => $e->getMessage(),
            'municipios' => config('bacheo.cobertura_municipios'),
        ], 422);
    }

    $bache = $result['bache'];
    // ...
}
```

El servicio devuelve `['bache' => ..., 'created' => bool]`. Ese booleano es toda
la idempotencia: si el reporte ya existía, no se crea otro, y la respuesta al
reintento es la misma que la del intento original —mismo folio, mismo 200—. n8n
no se entera de que reintentó, y el ciudadano tampoco.

La deduplicación en este caso es doble: por el mensaje —dos veces el mismo
mensaje de WhatsApp es el mismo reporte— y por cercanía geográfica, porque dos
personas distintas reportando el mismo bache desde la misma esquina son un solo
bache con dos reportes. De ahí sale el `created: false` con `reporteRefuerza()`.

## Lo que va después del commit, no puede tumbar la respuesta

La segunda decisión, que está comentada en el propio código:

```php
// Notificación interna (respeta rol/municipio): bache nuevo o refuerzo.
// Va después del commit, así que un fallo aquí no puede tumbar la
// respuesta: el reporte ya está guardado y n8n reintentaría un POST que
// ya surtió efecto.
$this->sinRomperLaRespuesta(
    fn () => $result['created'] ? $notif->nuevoBache($bache) : $notif->reporteRefuerza($bache),
    'No se pudo notificar el alta del bache',
    $bache->id,
);
```

El razonamiento completo está en ese comentario. Si la notificación interna
lanza una excepción —el servidor de correo caído, un push que falla—, Laravel
devolvería un 500. n8n vería un 500 y reintentaría. Pero el reporte **ya está
guardado**: el reintento entra a la deduplicación, no crea nada, y sólo genera
ruido.

Peor: si la notificación estuviera dentro de la transacción, un fallo del
servidor de correo haría rollback y **perdería el reporte del ciudadano**. Una
foto de un bache no se pierde porque el SMTP esté saturado.

La regla que saqué de aquí: **todo lo que no sea la escritura principal va
después del commit y envuelto**. Notificaciones, webhooks salientes, cachés que
se invalidan, trabajos que se encolan. Lo que falle ahí se registra en el log y
se sigue.

El mismo criterio aplica al análisis por IA:

```php
if ($result['created'] && config('bacheo.ia.enabled') && ! empty($result['reporte']->foto_url)) {
    $this->sinRomperLaRespuesta(
        fn () => AnalizarBacheJob::dispatch($bache->id),
        // ...
    );
}
```

La estimación de tamaño con visión de computadora tarda segundos y cuesta
dinero. Va a una cola, sólo si el bache es nuevo, sólo si hay foto y sólo si la
función está encendida. El ciudadano recibe su folio sin esperar a que un modelo
mire la imagen.

## Rechazar bien

El endpoint tiene una excepción propia para lo que está fuera de cobertura:

```php
} catch (FueraDeCoberturaException $e) {
    return response()->json([
        'ok' => false,
        'motivo' => 'fuera_de_cobertura',
        'mensaje' => $e->getMessage(),
        'municipios' => config('bacheo.cobertura_municipios'),
    ], 422);
}
```

Fíjate en lo que devuelve: un `motivo` legible por máquina y la **lista de
municipios donde sí hay servicio**. n8n toma esa lista y le contesta a la
persona «este servicio cubre Pachuca, Mineral de la Reforma y Zempoala».

Es la diferencia entre una integración que funciona y una que se siente bien
hecha. El error de la API trae lo necesario para que el otro lado construya una
respuesta útil, en vez de un «solicitud inválida» que obliga a duplicar el
catálogo de municipios en el flujo de n8n.

## Lo que me llevo

n8n es una gran herramienta para la parte conversacional y una pésima base de
datos. Todo lo que sea estado duradero, reglas o reportes se queda en Laravel.

Y la lección que sirve para cualquier integración, no sólo para esta: **el que
te llama va a reintentar**. Da igual si es n8n, Stripe, Mercado Libre o el
webhook del SAT. Si tu endpoint no tolera que lo llamen dos veces con los mismos
datos, no está terminado.
