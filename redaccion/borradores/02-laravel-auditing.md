---
titulo: 'Laravel Auditing: instalarlo no es auditar'
slug: laravel-auditing-auditoria-de-modelos
tipo: post
estado: borrador
categoria: Arquitectura
etiquetas: [laravel, auditoria, seguridad, eloquent]
resumen: Revisé mis trece proyectos de este año. Los trece tienen owen-it/laravel-auditing en el composer.json. Cuatro modelos, en total, lo implementan. Esto es lo que aprendí revisando mi propio descuido.
meta_descripcion: Cómo auditar modelos en Laravel con owen-it/laravel-auditing, qué configurar, qué campos excluir y por qué instalar el paquete no audita absolutamente nada.
hace_dias: 11
importante: true
---

Hice un inventario de los trece proyectos Laravel que arranqué este año.
`owen-it/laravel-auditing` aparece en el `composer.json` de los trece. Sin
excepción. Es parte de mi kit de arranque desde hace años.

Después conté los modelos que realmente implementan la interfaz. Cuatro. Todos
en el mismo proyecto. En el punto de venta —el que mueve dinero, inventario y
facturas— no hay ninguno.

Este artículo es lo que saqué de revisar mi propio descuido, y de darme cuenta
de que el descuido tiene una explicación que vale la pena entender.

## Qué hace el paquete

`owen-it/laravel-auditing` guarda, por cada cambio en un modelo, un renglón con
qué cambió, quién lo cambió, desde dónde y cuándo. La tabla `audits` queda así:

| Columna | Contenido |
| --- | --- |
| `event` | `created`, `updated`, `deleted`, `restored` |
| `auditable_type` / `auditable_id` | Qué modelo y cuál registro |
| `old_values` / `new_values` | JSON con **sólo** lo que cambió |
| `user_type` / `user_id` | Quién |
| `url`, `ip_address`, `user_agent` | Desde dónde |
| `tags` | Etiqueta libre para agrupar |

Lo importante de `old_values` y `new_values` es que no guardan el registro
entero, sólo los campos que realmente cambiaron. Cambias el precio de un
artículo y el renglón de auditoría pesa dos campos, no cuarenta.

## La instalación es la parte fácil

```bash
composer require owen-it/laravel-auditing
php artisan vendor:publish --provider="OwenIt\Auditing\AuditingServiceProvider" --tag="config"
php artisan vendor:publish --provider="OwenIt\Auditing\AuditingServiceProvider" --tag="migrations"
php artisan migrate
```

Y aquí es donde todo el mundo cree que terminó. **No se audita nada todavía.**
La tabla existe, la configuración existe, el paquete está cargado, y no se
guarda un solo renglón.

## Lo que de verdad activa la auditoría

Modelo por modelo:

```php
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Empresa extends Model implements AuditableContract
{
    use AuditableTrait;
}
```

Dos líneas: implementar el contrato y usar el trait. Eso es todo, y eso es
exactamente lo que a mí se me quedó sin hacer en nueve proyectos.

La trampa es que no hay ninguna señal de que falte. No hay error, no hay
advertencia, no hay comando que te diga «tienes el paquete instalado y cero
modelos auditables». Todo se ve bien hasta el día en que alguien pregunta quién
cambió un precio y descubres que la tabla `audits` lleva dos años vacía.

## Qué auditar, y por qué no todo

Cuando encontré los cuatro modelos auditados en la plataforma de avalúos, mi
primera reacción fue que faltaban sesenta. Al ver cuáles eran, cambié de
opinión: `Empresa`, `EmpresaCatalogoConfig`, `Solicitud` y `User`.

O sea: **el inquilino, su configuración, el documento que genera dinero y la
identidad**. No fue olvido, fue criterio. Alguien decidió eso.

Auditar los sesenta y cuatro modelos habría sido peor. Los catálogos —marcas,
modelos, colores, tipos de combustible— no cambian nunca, y cuando cambian no le
importa a nadie. Auditarlos llena la tabla de ruido y hace más lento cada
`seed`. Y la tabla `audits` crece rápido: en una aplicación con movimiento, es
de las primeras que se vuelve la más pesada de la base.

Mi regla, después de este inventario:

**Se audita lo que alguien podría negar haber hecho.** Precios, saldos,
permisos, estados de un documento, datos fiscales, cualquier cosa que dispare
un pago. Si el cambio puede terminar en una discusión con un cliente o con el
SAT, se audita.

**No se audita lo que se regenera.** Catálogos, cachés, tablas pivote de
etiquetas, contadores de visitas.

Con ese criterio, el punto de venta debería tener auditados `Articulo` —por el
precio—, `CuentaPorCobrar`, `CorteCaja` y `DevolucionVenta`. Cuatro o cinco de
ciento dos. Sigue siendo poco, pero son los correctos.

## Afinar qué campos se guardan

Auditar un modelo entero también trae basura. El `User` es el ejemplo clásico:
no quieres el hash de la contraseña ni el `remember_token` en una tabla que
después vas a mostrar en un panel.

```php
class User extends Authenticatable implements AuditableContract
{
    use AuditableTrait;

    /**
     * Lo que nunca entra a la auditoría.
     *
     * @var array<int, string>
     */
    protected $auditExclude = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Sólo estos eventos: un login no es un cambio de datos.
     *
     * @var array<int, string>
     */
    protected $auditEvents = ['created', 'updated', 'deleted'];
}
```

También existe `$auditInclude`, la lista blanca. Prefiero `$auditExclude` para
casi todo, salvo en modelos con muchísimas columnas donde sólo dos importan;
ahí la lista blanca es más honesta y no se rompe cuando alguien agrega una
columna nueva.

Y un límite que conviene poner desde el principio:

```php
protected $auditThreshold = 100;
```

Guarda los últimos cien renglones por registro y descarta los viejos. Sin eso,
un modelo que se actualiza por un job cada cinco minutos genera cien mil
renglones al año él solo.

## La configuración que sí hay que tocar

El `config/audit.php` publicado trae los resolvers de usuario, IP, URL y user
agent, y esto:

```php
'enabled' => env('AUDITING_ENABLED', true),
```

Ese interruptor sirve más de lo que parece. En las pruebas lo apago: no quiero
que cada factory ensucie la tabla ni que la suite tarde más por escribir
auditorías que nadie va a leer.

```dotenv
# .env.testing
AUDITING_ENABLED=false
```

Con la salvedad obvia: si tienes una prueba que verifica que algo se audita
—y deberías tenerla—, esa prueba debe encender el interruptor a mano.

Otro campo que vale la pena revisar es `user.guards`. Viene con `web` y `api`.
Si tu aplicación recibe llamadas de un servicio externo con un token propio,
esas escrituras se van a guardar con `user_id` nulo. No está mal, pero conviene
etiquetarlas para distinguirlas:

```php
$bache->auditAttach ?? null; // no
// mejor:
Audit::disableAuditing();    // cuando el proceso no es de un humano
```

O más limpio: dejar la auditoría encendida y usar `tags` para marcar el origen,
de modo que en el panel puedas filtrar «cambios hechos por la integración» de
«cambios hechos por una persona».

## Mostrarlo en algún lado

Una auditoría que nadie puede consultar sirve tanto como no tenerla. La
consulta base es directa, porque el trait agrega la relación:

```php
$empresa->audits()
    ->with('user')
    ->latest()
    ->paginate(20);
```

Y para pintar un renglón legible:

```php
foreach ($audit->getModified() as $campo => $valores) {
    // ['old' => ..., 'new' => ...]
    echo "{$campo}: {$valores['old']} → {$valores['new']}";
}
```

`getModified()` es mejor que leer `old_values` y `new_values` por separado
porque ya viene emparejado y con los casts del modelo aplicados: una fecha sale
como `Carbon`, no como cadena cruda.

## Lo que me llevo

El paquete es bueno y la integración es de dos líneas. Precisamente por eso es
tan fácil dejarlo a medias: instalarlo se siente como haberlo resuelto.

Lo que voy a cambiar en mi kit de arranque es esto: el paquete deja de venir
preinstalado sin uso. O el proyecto tiene al menos un modelo auditado desde el
primer día, o el paquete no entra. Un `composer.json` que promete auditoría y
una tabla `audits` vacía es peor que no tener nada, porque te hace creer que
estás cubierto.
