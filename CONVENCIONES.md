# Convenciones del proyecto

Base Laravel 13 + React (Inertia) + TypeScript. El idioma de la interfaz y de los
textos del código es **español**.

## Módulo nuevo: empieza por el generador

```bash
php artisan make:modulo Producto --grupo=inventario
```

Genera el módulo completo siguiendo todas las convenciones de este documento:
modelo, factory, migración, controlador (`index`/`store`/`destroy`), Form Request,
Resource y la página Inertia con `DataTable`, `CrudFormDialog`,
`ConfirmDeleteDialog` y los campos de formulario. Al terminar imprime los pasos
que faltan: el `case` del permiso, las rutas y los comandos a correr.

| Opción | Para qué |
| --- | --- |
| `--grupo=` | Agrupa el módulo y prefija sus rutas (`inventario.productos.*`) |
| `--plural=` | Plural correcto cuando `Str::plural` falla (ej. `Rol` → `Roles`) |
| `--permiso=` | Permiso de las rutas; por defecto `<recurso>.gestionar` |
| `--force` | Sobrescribe lo que ya exista |

Las plantillas están en `stubs/modulo/` — ajústalas ahí y todos los módulos
futuros heredan el cambio. Los campos del stub (`nombre`, `descripcion`,
`activo`) son un punto de partida: cámbialos en la migración, el modelo, el
Request, el Resource y la página.

## Frontend

### Formularios

Todo formulario de alta/edición va dentro de `CrudFormDialog`
(`resources/js/components/crud-form-dialog.tsx`). No se arman diálogos con
`Dialog` a mano.

```tsx
<CrudFormDialog
    open={formMode !== null}
    onOpenChange={closeFormDialog}
    title={formMode === 'edit' ? 'Editar usuario' : 'Crear usuario'}
    description="Completa los datos y asigna uno o más roles."
    submitLabel={formMode === 'edit' ? 'Guardar cambios' : 'Guardar usuario'}
    processing={form.processing}
    onSubmit={submitForm}
>
    {/* campos */}
</CrudFormDialog>
```

### Confirmación de borrado

Toda eliminación se confirma con `ConfirmDeleteDialog`
(`resources/js/components/confirm-delete-dialog.tsx`).

```tsx
<ConfirmDeleteDialog
    open={activeUser !== null}
    onOpenChange={(open) => !open && setActiveUser(null)}
    title="Eliminar usuario"
    entityLabel="la cuenta de"
    itemName={activeUser?.name}
    onConfirm={handleDelete}
/>
```

### Campos de formulario

Se usan siempre los componentes de campo, nunca los primitivos de
`components/ui/` sueltos. Cada uno arma `Field + Label + control + FieldError` y
marca `aria-invalid` cuando hay error.

| Campo | Componente | Prop de cambio |
| --- | --- | --- |
| Texto | `FormInputField` | `onChange` |
| Área de texto | `FormTextareaField` | `onChange` |
| Select simple | `FormSelectField` | `onValueChange` |
| Select múltiple | `FormMultiSelectField` | `onValuesChange` |

```tsx
<FormInputField
    id="user-name"
    label="Nombre"
    value={form.data.name}
    error={form.errors.name}
    onChange={(event) => form.setData('name', event.target.value)}
    placeholder="Ej. Ana López"
/>

<FormTextareaField
    id="role-description"
    label="Descripción"
    value={form.data.description}
    error={form.errors.description}
    onChange={(event) => form.setData('description', event.target.value)}
    rows={4}
/>

<FormSelectField
    id="user-status"
    label="Estado"
    value={form.data.status}
    error={form.errors.status}
    onValueChange={(value) => form.setData('status', value)}
    options={[
        { value: 'active', label: 'Activo' },
        { value: 'suspended', label: 'Suspendido' },
    ]}
/>

<FormMultiSelectField
    id="user-roles"
    label="Roles"
    values={form.data.roles}
    error={form.errors.roles}
    onValuesChange={(values) => form.setData('roles', values)}
    options={roles.map((role) => ({
        value: role.name,
        label: role.name,
    }))}
/>
```

Los selects reciben `options: SelectOption[]` (`{ value, label, disabled? }`),
tipo exportado desde `@/types`.

## Backend

### Resources y Collections

Los `index` —y cualquier endpoint que devuelva arreglos largos— arman su payload
con API Resources, no con `->map()` en línea dentro del controlador.

```php
return Inertia::render('config/users/index', [
    'users' => UserResource::collection($usuarios->getCollection())->resolve(),
    'roles' => RoleResource::collection($roles)->resolve(),
]);
```

**El `->resolve()` no es opcional.** Un `JsonResource` implementa `Responsable`
pero no `Arrayable`, así que Inertia lo serializa llamando `toResponse()` y la
prop llega envuelta en `{ data: [...] }`, lo que rompe la página. `->resolve()`
devuelve el arreglo plano.

Los Resources viven en `app/Http/Resources/`, agrupados por módulo igual que los
controladores (`app/Http/Resources/Config/UserResource.php`). Llevan
`@mixin <Modelo>` en el docblock para que PHPStan resuelva los atributos.

**La regla del `->resolve()` vale también para los Resources anidados.** Inertia
resuelve las props recorriendo el arreglo, y cualquier Resource que encuentre
dentro implementa `Responsable`: lo convierte llamando `toResponse()`, así que la
relación llega envuelta en `{"data": [...]}`. La página recibe un objeto donde el
tipo de TypeScript promete un arreglo y revienta con
`etiquetas.map is not a function`.

Por eso las relaciones se resuelven a mano y siempre devuelven el mismo tipo,
esté cargada la relación o no:

```php
'etiquetas' => $this->relationLoaded('etiquetas')
    ? EtiquetaResource::collection($this->etiquetas->values())->resolve()
    : [],

'categoria' => $this->relationLoaded('categoria') && $this->categoria !== null
    ? (new CategoriaResource($this->categoria))->resolve()
    : null,
```

`whenLoaded` a secas tiene además un segundo problema: cuando la relación no
viene cargada omite la clave entera, y entonces la prop llega `undefined` aunque
el tipo diga `Etiqueta[]`. Con `relationLoaded` el contrato es estable: arreglo
vacío o `null`, nunca una clave ausente.

Hay pruebas que fijan esta forma en `tests/Feature/Blog/PropsTest.php`.

Cuando la colección necesita metadatos propios (paginación, totales, filtros) se
crea además su `ResourceCollection`.

### Form Requests

Cada upsert tiene su Form Request en `app/Http/Requests/<Modulo>/`, nombrado
`Upsert<Modelo>Request`. Ahí van `authorize()`, `rules()` y los mensajes; el
controlador no valida.

Existentes: `UpsertUserRequest`, `UpsertRoleRequest`.

### Controladores

Un controlador de CRUD expone `index`, `store` y `destroy`.

**`store` funciona como upsert**: crea o actualiza según venga o no el
identificador en la petición. No se agrega un método `update` ni se le cambia el
nombre a `store`.

```php
public function store(UpsertUserRequest $request): RedirectResponse
{
    // crea si no hay id, actualiza si lo hay
}
```

La ruta correspondiente es un único `POST` con nombre `<modulo>.<recurso>.store`.

## Módulo Blog

Este proyecto es la réplica de `blog_flux` (Livewire/Flux) sobre esta base.
La documentación del módulo vive en `docs/`:

| Documento | Contenido |
| --- | --- |
| `docs/PLAN.md` | Qué se replicó, qué quedó fuera y por qué |
| `docs/BLOG.md` | Modelo de datos, permisos, rutas, API pública y almacenamiento |
| `docs/CORREOS.md` | Configuración de correo, los ocho correos y la cola |

Dos reglas propias del módulo:

- `Post`, `Tutorial` y `Recurso` comparten un único controlador, Form Request,
  Resource y página React, parametrizados por `TipoPublicacion`. Si agregas un
  campo, va en `App\Models\Publicacion` y en las tres migraciones.
- Los archivos suben al disco de `config('blog.disco')`, nunca a `'public'` a mano.
