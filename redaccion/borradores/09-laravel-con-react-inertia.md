---
titulo: Laravel con React e Inertia, del cero al primer CRUD
slug: laravel-con-react-e-inertia-primer-crud
tipo: tutorial
estado: borrador
categoria: Frontend
etiquetas: [laravel, react, inertia, typescript, crud]
resumen: Inertia te da React sin construir una API. Aquí está el camino completo, desde la instalación hasta un CRUD que lista, crea, edita y borra, con el detalle de los Resources que me costó una tarde entender.
meta_descripcion: 'Tutorial de Laravel con React e Inertia: instalación, primera página, formularios con useForm, validación y un CRUD completo con paginación.'
hace_dias: 61
importante: true
---

Inertia resuelve un problema concreto: quieres React en el frontend, pero no
quieres construir y mantener una API REST sólo para hablar con tu propio
backend.

Sin Inertia, agregar un campo a un formulario significa tocar la migración, el
modelo, el controlador de API, el Resource, el tipo de TypeScript, el cliente
HTTP del frontend y el componente. Con Inertia el controlador devuelve datos y
el componente los recibe como props. Se siente como Blade, pero con React.

Este tutorial llega hasta un CRUD funcionando. Es el mismo camino que sigo al
arrancar cualquier proyecto.

## Instalación

Sobre un Laravel nuevo, la vía corta es el kit de arranque oficial:

```bash
laravel new mi-proyecto --react
```

Eso deja Inertia, React, TypeScript, Tailwind y la autenticación ya montados.

Si el proyecto ya existe:

```bash
composer require inertiajs/inertia-laravel
php artisan inertia:middleware
npm install @inertiajs/react react react-dom
npm install -D @vitejs/plugin-react typescript @types/react @types/react-dom
```

El middleware se registra en `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
    ]);
})
```

Y la plantilla raíz, `resources/views/app.blade.php`:

```blade
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @viteReactRefresh
    @vite(['resources/js/app.tsx'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
```

El punto de entrada, `resources/js/app.tsx`:

```tsx
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.tsx', { eager: true });
        return pages[`./pages/${name}.tsx`];
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
```

Ese `resolve` es el que conecta el nombre que manda el controlador con el
archivo del componente. `Inertia::render('productos/index')` busca
`resources/js/pages/productos/index.tsx`.

## La primera página

Controlador:

```php
// app/Http/Controllers/ProductoController.php
public function index(): Response
{
    return Inertia::render('productos/index', [
        'productos' => Producto::query()->latest()->paginate(10),
    ]);
}
```

Componente:

```tsx
// resources/js/pages/productos/index.tsx
import { Head } from '@inertiajs/react';

export default function ProductosIndex({ productos }: { productos: { data: Producto[] } }) {
    return (
        <>
            <Head title="Productos" />
            <ul>
                {productos.data.map((producto) => (
                    <li key={producto.id}>{producto.nombre}</li>
                ))}
            </ul>
        </>
    );
}
```

Sin `fetch`, sin `useEffect`, sin estado de carga. El segundo argumento de
`Inertia::render()` son las props del componente.

## El error que me costó una tarde

Cuando el listado crece, la buena práctica es no armar el arreglo a mano en el
controlador sino usar un API Resource:

```php
return Inertia::render('productos/index', [
    'productos' => ProductoResource::collection($productos),
]);
```

Y ahí la página revienta con algo como `productos.map is not a function`.

La razón: `JsonResource` implementa `Responsable`, no `Arrayable`. Inertia, al
serializar las props, lo resuelve llamando `toResponse()`, y eso devuelve la
respuesta JSON completa de la API — o sea, envuelta en `{"data": [...]}`. El
componente recibe un objeto donde el tipo prometía un arreglo.

La solución es `->resolve()`:

```php
'productos' => ProductoResource::collection($productos->getCollection())->resolve(),
```

`resolve()` devuelve el arreglo plano, sin envoltura.

**Y aplica igual a los Resources anidados**, que es donde vuelve a morderte. Si
un `ProductoResource` incluye sus etiquetas, esto falla:

```php
'etiquetas' => EtiquetaResource::collection($this->whenLoaded('etiquetas')),
```

Por dos motivos a la vez. Uno, la envoltura otra vez. Dos, `whenLoaded` **omite
la clave entera** cuando la relación no viene cargada, así que la prop llega
`undefined` aunque el tipo de TypeScript diga `Etiqueta[]`.

La forma que uso, que devuelve siempre el mismo tipo:

```php
'etiquetas' => $this->relationLoaded('etiquetas')
    ? EtiquetaResource::collection($this->etiquetas->values())->resolve()
    : [],

'categoria' => $this->relationLoaded('categoria') && $this->categoria !== null
    ? (new CategoriaResource($this->categoria))->resolve()
    : null,
```

Arreglo vacío o `null`, nunca una clave ausente. El frontend puede confiar en el
contrato.

## Formularios

`useForm` trae estado, errores de validación y estado de envío:

```tsx
import { useForm } from '@inertiajs/react';

const form = useForm({ nombre: '', precio: '' });

const enviar = (event: React.FormEvent) => {
    event.preventDefault();
    form.post('/productos', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

return (
    <form onSubmit={enviar}>
        <input
            value={form.data.nombre}
            onChange={(e) => form.setData('nombre', e.target.value)}
            aria-invalid={!!form.errors.nombre}
        />
        {form.errors.nombre && <span>{form.errors.nombre}</span>}

        <button disabled={form.processing}>Guardar</button>
    </form>
);
```

Lo bueno está en `form.errors`. En el backend sólo escribes el Form Request de
siempre:

```php
class UpsertProductoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0'],
        ];
    }
}
```

Si la validación falla, Laravel redirige con los errores en la sesión, Inertia
los recoge y aparecen en `form.errors` con la misma clave del campo. No escribes
una línea para conectar ambos lados.

## Store como upsert

Una convención que sigo en todos mis proyectos: el controlador de CRUD expone
`index`, `store` y `destroy`. Nada más. `store` crea o actualiza según venga el
identificador.

```php
public function store(UpsertProductoRequest $request): RedirectResponse
{
    $datos = $request->validated();

    $producto = isset($datos['id'])
        ? Producto::query()->findOrFail($datos['id'])
        : new Producto();

    $producto->fill($datos)->save();

    return back()->with('success', 'Producto guardado correctamente.');
}
```

La razón es el frontend: el diálogo de alta y el de edición son el mismo
componente con los campos llenos o vacíos. Tener rutas separadas para `store` y
`update` obliga a decidir a cuál apuntar en cada envío, y esa condición se
repite en cada módulo. Con upsert, el formulario siempre postea al mismo sitio.

El `back()` es importante: Inertia recarga las props de la página actual, así que
la tabla se actualiza sola sin que escribas nada para refrescarla.

## Borrar

```tsx
import { router } from '@inertiajs/react';

router.delete(`/productos/${producto.id}`, {
    preserveScroll: true,
});
```

Con confirmación previa, siempre. Un `router.delete` directo en un `onClick` es
la manera de que alguien borre algo con un clic mal dado.

## Paginación

`paginate()` de Laravel devuelve la estructura completa, pero mandar el objeto
entero al frontend arrastra URLs absolutas y metadatos que no usas. Prefiero
pasar sólo lo necesario:

```php
return Inertia::render('productos/index', [
    'productos' => ProductoResource::collection($productos->getCollection())->resolve(),
    'paginacion' => [
        'total' => $productos->total(),
        'currentPage' => $productos->currentPage(),
        'lastPage' => $productos->lastPage(),
        'prevUrl' => $productos->previousPageUrl(),
        'nextUrl' => $productos->nextPageUrl(),
    ],
]);
```

Y en el componente, los enlaces se navegan con `router.get(url, {}, {
preserveState: true })` para que los filtros no se pierdan al cambiar de página.

## Lo que dejaría para el segundo día

**Visitas parciales.** Cuando una página tiene props caras —un catálogo de mil
elementos que sólo carga una vez—, `only: ['productos']` en la navegación evita
recalcular el resto. Se agrega cuando se nota la lentitud, no antes.

**Rutas tipadas.** Escribir `/productos` a mano funciona hasta que alguien
cambia el prefijo. `laravel/wayfinder` genera funciones TypeScript por ruta y el
error pasa a ser de compilación.

**Layout persistente.** Sin él, el menú lateral se vuelve a montar en cada
navegación y pierde su estado de scroll. Se resuelve asignando
`Componente.layout = (page) => <AppLayout>{page}</AppLayout>`.

Ninguna de las tres hace falta para el primer CRUD. Todas hacen falta para el
décimo.
