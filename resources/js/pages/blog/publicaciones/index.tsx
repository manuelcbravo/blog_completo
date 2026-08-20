import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    ExternalLink,
    FileText,
    MoreHorizontal,
    Paperclip,
    Pencil,
    Plus,
    SlidersHorizontal,
    Trash2,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';
import { CampoImagen } from '@/components/campo-imagen';
import { ConfirmDeleteDialog } from '@/components/confirm-delete-dialog';
import { CrudFormDialog } from '@/components/crud-form-dialog';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn, DataTableServer } from '@/components/data-table';
import { EstadoBadge } from '@/components/estado-badge';
import { FormInputField } from '@/components/form-input-field';
import { FormMultiSelectField } from '@/components/form-multi-select-field';
import { FormSelectField } from '@/components/form-select-field';
import { FormTextareaField } from '@/components/form-textarea-field';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/date';
import { resolveFormErrorMessage } from '@/lib/form-error-message';
import { destroy, index, store } from '@/routes/blog/publicaciones';
import { index as contenidoIndex } from '@/routes/blog/publicaciones/contenido';
import { store as estadoStore } from '@/routes/blog/publicaciones/estado';
import type { SelectOption } from '@/types';
import { DialogoDetalles } from './detalles-dialog';

type Opcion = { value: string; label: string };
type Referencia = { id: number; nombre: string };

export type PublicacionRow = {
    id: number;
    tipo: string;
    slug: string;
    titulo: string;
    resumen: string | null;
    imagen_destacada: string | null;
    imagen_url: string | null;
    estado: string;
    estado_label: string;
    fecha_publicacion: string | null;
    tiempo_lectura: number;
    visitas: number;
    importante: boolean;
    tags_seo: string | null;
    meta_titulo: string | null;
    meta_descripcion: string | null;
    id_categoria: number | null;
    id_autor: number | null;
    categoria: Referencia | null;
    autor: Referencia | null;
    etiquetas: Referencia[];
    detalles?: {
        id: number;
        detalle: string | null;
        url: string | null;
        nombre_original: string | null;
        tamano: number | null;
    }[];
    comentarios?: number;
    url_publica: string;
    created_at: string;
};

type TipoProp = {
    valor: string;
    segmento: string;
    etiqueta: string;
    etiquetaPlural: string;
    descripcion: string;
    tieneDetalles: boolean;
};

type PublicacionForm = {
    id: number | null;
    titulo: string;
    slug: string;
    resumen: string;
    tags_seo: string;
    estado: string;
    fecha_publicacion: string;
    importante: boolean;
    id_categoria: string;
    id_autor: string;
    etiquetas: string[];
    meta_titulo: string;
    meta_descripcion: string;
    imagen: File | null;
    eliminar_imagen: boolean;
};

type EstadoForm = {
    estado: string;
    fecha_publicacion: string;
};

const VALORES_INICIALES: PublicacionForm = {
    id: null,
    titulo: '',
    slug: '',
    resumen: '',
    tags_seo: '',
    estado: 'borrador',
    fecha_publicacion: '',
    importante: false,
    id_categoria: '',
    id_autor: '',
    etiquetas: [],
    meta_titulo: '',
    meta_descripcion: '',
    imagen: null,
    eliminar_imagen: false,
};

function aInputFecha(valor: string | null) {
    if (!valor) {
        return '';
    }

    return valor.slice(0, 16);
}

export default function PublicacionesIndex({
    tipo,
    publicaciones,
    paginacion,
    filtros,
    categorias,
    etiquetas,
    autores,
    estados,
}: {
    tipo: TipoProp;
    publicaciones: PublicacionRow[];
    paginacion: DataTableServer;
    filtros: { estado: string };
    categorias: Referencia[];
    etiquetas: Referencia[];
    autores: Referencia[];
    estados: Opcion[];
}) {
    const [activa, setActiva] = useState<PublicacionRow | null>(null);
    const [formMode, setFormMode] = useState<'create' | 'edit' | null>(null);
    const [porEliminar, setPorEliminar] = useState<PublicacionRow | null>(null);
    const [detallesDeId, setDetallesDeId] = useState<number | null>(null);
    const [estadoDe, setEstadoDe] = useState<PublicacionRow | null>(null);
    const form = useForm<PublicacionForm>(VALORES_INICIALES);
    const formEstado = useForm<EstadoForm>({
        estado: 'borrador',
        fecha_publicacion: '',
    });
    const { flash } = usePage().props;

    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash?.error, flash?.success]);

    const detallesDe =
        detallesDeId === null
            ? null
            : (publicaciones.find((registro) => registro.id === detallesDeId) ??
              null);

    const opcionesCategoria: SelectOption[] = useMemo(
        () => [
            { value: 'sin', label: 'Sin categoría' },
            ...categorias.map((categoria) => ({
                value: String(categoria.id),
                label: categoria.nombre,
            })),
        ],
        [categorias],
    );

    const opcionesAutor: SelectOption[] = useMemo(
        () =>
            autores.map((autor) => ({
                value: String(autor.id),
                label: autor.nombre,
            })),
        [autores],
    );

    const opcionesEtiqueta: SelectOption[] = useMemo(
        () =>
            etiquetas.map((etiqueta) => ({
                value: String(etiqueta.id),
                label: etiqueta.nombre,
            })),
        [etiquetas],
    );

    const abrirCrear = () => {
        setActiva(null);
        form.reset();
        form.clearErrors();
        form.setData({ ...VALORES_INICIALES, estado: 'borrador' });
        setFormMode('create');
    };

    const abrirEditar = (registro: PublicacionRow) => {
        setActiva(registro);
        form.clearErrors();
        form.setData({
            id: registro.id,
            titulo: registro.titulo,
            slug: registro.slug,
            resumen: registro.resumen ?? '',
            tags_seo: registro.tags_seo ?? '',
            estado: registro.estado,
            fecha_publicacion: aInputFecha(registro.fecha_publicacion),
            importante: registro.importante,
            id_categoria: registro.id_categoria
                ? String(registro.id_categoria)
                : 'sin',
            id_autor: registro.id_autor ? String(registro.id_autor) : '',
            etiquetas: (registro.etiquetas ?? []).map((etiqueta) =>
                String(etiqueta.id),
            ),
            meta_titulo: registro.meta_titulo ?? '',
            meta_descripcion: registro.meta_descripcion ?? '',
            imagen: null,
            eliminar_imagen: false,
        });
        setFormMode('edit');
    };

    const cerrarForm = (open: boolean) => {
        if (!open) {
            setFormMode(null);
            setActiva(null);
            form.clearErrors();
        }
    };

    const abrirEstado = (registro: PublicacionRow) => {
        setEstadoDe(registro);
        formEstado.clearErrors();
        formEstado.setData({
            estado: registro.estado,
            fecha_publicacion: aInputFecha(registro.fecha_publicacion),
        });
    };

    const enviarEstado = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!estadoDe) {
            return;
        }

        formEstado.post(
            estadoStore.url({ tipo: tipo.segmento, publicacion: estadoDe.id }),
            {
                preserveScroll: true,
                onSuccess: () => setEstadoDe(null),
                onError: (errors) =>
                    toast.error(
                        resolveFormErrorMessage(
                            errors,
                            'No se pudo cambiar el estado.',
                        ),
                    ),
            },
        );
    };

    const enviarForm = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.transform((datos: PublicacionForm) => ({
            ...datos,
            id_categoria:
                datos.id_categoria === 'sin' ? '' : datos.id_categoria,
        }));

        form.post(store.url(tipo.segmento), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setFormMode(null);
                setActiva(null);
                form.reset();
            },
            onError: (errors) =>
                toast.error(
                    resolveFormErrorMessage(
                        errors,
                        'Verifica los campos marcados.',
                    ),
                ),
        });
    };

    const columns: DataTableColumn<PublicacionRow>[] = [
        {
            key: 'imagen',
            header: '',
            className: 'w-20',
            cell: (registro) =>
                registro.imagen_url ? (
                    <img
                        src={registro.imagen_url}
                        alt={registro.titulo}
                        className="size-12 rounded-lg object-cover"
                    />
                ) : (
                    <div className="flex size-12 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                        <FileText className="size-4" />
                    </div>
                ),
        },
        {
            key: 'titulo',
            header: 'Título',
            accessor: (registro) => registro.titulo,
            cell: (registro) => (
                <div className="min-w-48 space-y-1">
                    <p className="font-medium">{registro.titulo}</p>
                    <p className="text-xs text-muted-foreground">
                        /{registro.slug}
                    </p>
                </div>
            ),
        },
        {
            key: 'categoria',
            header: 'Categoría',
            cell: (registro) => registro.categoria?.nombre ?? '—',
        },
        {
            key: 'autor',
            header: 'Autor',
            cell: (registro) => registro.autor?.nombre ?? '—',
        },
        {
            key: 'metricas',
            header: 'Vistas',
            cell: (registro) => (
                <div className="text-xs text-muted-foreground">
                    <p>{registro.visitas} vistas</p>
                    <p>{registro.comentarios ?? 0} comentarios</p>
                </div>
            ),
        },
        {
            key: 'estado',
            header: 'Estado',
            cell: (registro) => (
                <div className="space-y-1">
                    <EstadoBadge
                        estado={registro.estado}
                        label={registro.estado_label}
                    />
                    <p className="text-xs text-muted-foreground">
                        {formatDate(registro.fecha_publicacion)}
                    </p>
                </div>
            ),
        },
        {
            key: 'actions',
            header: '',
            className: 'w-14',
            cell: (registro) => (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-8"
                            aria-label="Abrir acciones"
                        >
                            <MoreHorizontal className="size-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem
                            onSelect={() => abrirEditar(registro)}
                        >
                            <Pencil className="mr-2 size-4" /> Editar
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() =>
                                router.get(
                                    contenidoIndex.url({
                                        tipo: tipo.segmento,
                                        publicacion: registro.id,
                                    }),
                                )
                            }
                        >
                            <FileText className="mr-2 size-4" /> Contenido
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() => abrirEstado(registro)}
                        >
                            <SlidersHorizontal className="mr-2 size-4" />{' '}
                            Estatus
                        </DropdownMenuItem>
                        {tipo.tieneDetalles && (
                            <DropdownMenuItem
                                onSelect={() => setDetallesDeId(registro.id)}
                            >
                                <Paperclip className="mr-2 size-4" /> Archivos
                            </DropdownMenuItem>
                        )}
                        <DropdownMenuItem asChild>
                            <a
                                href={registro.url_publica}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <ExternalLink className="mr-2 size-4" /> Ver en
                                el sitio
                            </a>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            variant="destructive"
                            onSelect={() => setPorEliminar(registro)}
                        >
                            <Trash2 className="mr-2 size-4" /> Eliminar
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            ),
        },
    ];

    return (
        <>
            <Head title={tipo.etiquetaPlural} />
            <div className="space-y-4 rounded-xl p-4">
                <div className="rounded-xl border border-sidebar-border/70 bg-sidebar-accent/20 p-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h1 className="text-xl font-semibold">
                                {tipo.etiquetaPlural}
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {tipo.descripcion}
                            </p>
                        </div>
                        <div className="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                            <div className="w-full sm:w-48">
                                <FormSelectField
                                    id="filtro-estado"
                                    label=""
                                    value={
                                        filtros.estado === ''
                                            ? 'todos'
                                            : filtros.estado
                                    }
                                    onValueChange={(value) =>
                                        router.get(
                                            index.url(tipo.segmento),
                                            value === 'todos'
                                                ? {}
                                                : { estado: value },
                                            {
                                                preserveState: true,
                                                preserveScroll: true,
                                            },
                                        )
                                    }
                                    options={[
                                        {
                                            value: 'todos',
                                            label: 'Todos los estados',
                                        },
                                        ...estados,
                                    ]}
                                />
                            </div>
                            <Button onClick={abrirCrear}>
                                <Plus className="mr-2 size-4" /> Nuevo
                            </Button>
                        </div>
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    data={publicaciones}
                    server={paginacion}
                    extraParams={{ estado: filtros.estado }}
                    searchPlaceholder="Buscar por título, resumen o slug..."
                />
            </div>

            <CrudFormDialog
                open={formMode !== null}
                onOpenChange={cerrarForm}
                size="lg"
                title={
                    formMode === 'edit'
                        ? `Editar ${tipo.etiqueta.toLowerCase()}`
                        : `Nueva ${tipo.etiqueta.toLowerCase()}`
                }
                description="Los datos generales y el SEO se guardan aquí; el contenido se edita en su propia pantalla."
                submitLabel={
                    formMode === 'edit' ? 'Guardar cambios' : 'Guardar'
                }
                processing={form.processing}
                onSubmit={enviarForm}
            >
                <div className="grid gap-6 md:grid-cols-2">
                    <div className="space-y-4">
                        <CampoImagen
                            id="publicacion-imagen"
                            label="Imagen destacada"
                            archivo={form.data.imagen}
                            urlActual={
                                form.data.eliminar_imagen
                                    ? null
                                    : (activa?.imagen_url ?? null)
                            }
                            error={form.errors.imagen}
                            onArchivoChange={(archivo) => {
                                form.setData('imagen', archivo);

                                if (archivo) {
                                    form.setData('eliminar_imagen', false);
                                }
                            }}
                            onEliminarActual={() =>
                                form.setData('eliminar_imagen', true)
                            }
                        />

                        <FormSelectField
                            id="publicacion-estado"
                            label="Estado"
                            value={form.data.estado}
                            error={form.errors.estado}
                            onValueChange={(value) =>
                                form.setData('estado', value)
                            }
                            options={estados}
                        />

                        <FormInputField
                            id="publicacion-fecha"
                            type="datetime-local"
                            label="Fecha de publicación"
                            value={form.data.fecha_publicacion}
                            error={form.errors.fecha_publicacion}
                            onChange={(event) =>
                                form.setData(
                                    'fecha_publicacion',
                                    event.target.value,
                                )
                            }
                        />

                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={form.data.importante}
                                onCheckedChange={(valor) =>
                                    form.setData('importante', valor === true)
                                }
                            />
                            Marcar como destacada
                        </label>
                    </div>

                    <div className="space-y-4">
                        <FormInputField
                            id="publicacion-titulo"
                            label="Título"
                            value={form.data.titulo}
                            error={form.errors.titulo}
                            onChange={(event) =>
                                form.setData('titulo', event.target.value)
                            }
                            placeholder="Ej. Panel de control con Inertia"
                        />

                        <FormInputField
                            id="publicacion-slug"
                            label="Slug"
                            value={form.data.slug}
                            error={form.errors.slug}
                            onChange={(event) =>
                                form.setData('slug', event.target.value)
                            }
                            placeholder="Se genera del título si lo dejas vacío"
                        />

                        <FormTextareaField
                            id="publicacion-resumen"
                            label="Resumen"
                            value={form.data.resumen}
                            error={form.errors.resumen}
                            onChange={(event) =>
                                form.setData('resumen', event.target.value)
                            }
                            rows={3}
                            placeholder="Una o dos líneas para las tarjetas del blog"
                        />

                        <FormSelectField
                            id="publicacion-autor"
                            label="Autor"
                            value={form.data.id_autor}
                            error={form.errors.id_autor}
                            onValueChange={(value) =>
                                form.setData('id_autor', value)
                            }
                            options={opcionesAutor}
                        />

                        <FormSelectField
                            id="publicacion-categoria"
                            label="Categoría"
                            value={form.data.id_categoria}
                            error={form.errors.id_categoria}
                            onValueChange={(value) =>
                                form.setData('id_categoria', value)
                            }
                            options={opcionesCategoria}
                        />

                        <FormMultiSelectField
                            id="publicacion-etiquetas"
                            label="Etiquetas"
                            values={form.data.etiquetas}
                            error={form.errors.etiquetas}
                            onValuesChange={(values) =>
                                form.setData('etiquetas', values)
                            }
                            options={opcionesEtiqueta}
                        />
                    </div>
                </div>

                <div className="space-y-4 rounded-xl border border-sidebar-border/70 p-4">
                    <div>
                        <Label>SEO</Label>
                        <p className="text-xs text-muted-foreground">
                            Lo que ven Google y las redes cuando comparten la
                            publicación.
                        </p>
                    </div>

                    <FormInputField
                        id="publicacion-tags-seo"
                        label="Palabras clave"
                        value={form.data.tags_seo}
                        error={form.errors.tags_seo}
                        onChange={(event) =>
                            form.setData('tags_seo', event.target.value)
                        }
                        placeholder="laravel, inertia, react"
                    />

                    <FormInputField
                        id="publicacion-meta-titulo"
                        label="Meta título"
                        value={form.data.meta_titulo}
                        error={form.errors.meta_titulo}
                        onChange={(event) =>
                            form.setData('meta_titulo', event.target.value)
                        }
                        placeholder="Se usa el título si lo dejas vacío"
                    />

                    <FormTextareaField
                        id="publicacion-meta-descripcion"
                        label="Meta descripción"
                        value={form.data.meta_descripcion}
                        error={form.errors.meta_descripcion}
                        onChange={(event) =>
                            form.setData('meta_descripcion', event.target.value)
                        }
                        rows={2}
                        placeholder="Hasta 160 caracteres para el resultado de búsqueda"
                    />
                </div>
            </CrudFormDialog>

            <CrudFormDialog
                open={estadoDe !== null}
                onOpenChange={(open) => !open && setEstadoDe(null)}
                size="sm"
                title="Cambiar estatus"
                description={estadoDe?.titulo ?? ''}
                submitLabel="Guardar estatus"
                processing={formEstado.processing}
                onSubmit={enviarEstado}
            >
                <FormSelectField
                    id="estatus-estado"
                    label="Estado"
                    value={formEstado.data.estado}
                    error={formEstado.errors.estado}
                    onValueChange={(valor) =>
                        formEstado.setData('estado', valor)
                    }
                    options={estados}
                />

                <FormInputField
                    id="estatus-fecha"
                    type="datetime-local"
                    label="Fecha de publicación"
                    value={formEstado.data.fecha_publicacion}
                    error={formEstado.errors.fecha_publicacion}
                    onChange={(event) =>
                        formEstado.setData(
                            'fecha_publicacion',
                            event.target.value,
                        )
                    }
                />

                {formEstado.data.estado === 'publicado' &&
                    estadoDe?.estado !== 'publicado' && (
                        <p className="rounded-lg bg-amber-100 p-3 text-xs text-amber-900 dark:bg-amber-950 dark:text-amber-300">
                            Al publicar se encola el aviso por correo a los
                            suscriptores confirmados.
                        </p>
                    )}

                {formEstado.data.estado === 'programado' && (
                    <p className="rounded-lg bg-sky-100 p-3 text-xs text-sky-900 dark:bg-sky-950 dark:text-sky-300">
                        Se publicará sola cuando llegue la fecha, con la tarea
                        blog:publicar-programadas.
                    </p>
                )}
            </CrudFormDialog>

            <ConfirmDeleteDialog
                open={porEliminar !== null}
                onOpenChange={(open) => !open && setPorEliminar(null)}
                title={`Eliminar ${tipo.etiqueta.toLowerCase()}`}
                entityLabel="la publicación"
                itemName={porEliminar?.titulo}
                onConfirm={() => {
                    if (!porEliminar) {
                        return;
                    }

                    router.delete(
                        destroy.url({
                            tipo: tipo.segmento,
                            publicacion: porEliminar.id,
                        }),
                        {
                            preserveScroll: true,
                            onSuccess: () => setPorEliminar(null),
                            onError: (errors) =>
                                toast.error(
                                    resolveFormErrorMessage(
                                        errors,
                                        'No se pudo eliminar la publicación.',
                                    ),
                                ),
                        },
                    );
                }}
            />

            {tipo.tieneDetalles && (
                <DialogoDetalles
                    recurso={detallesDe}
                    onOpenChange={(open) => !open && setDetallesDeId(null)}
                />
            )}
        </>
    );
}

PublicacionesIndex.layout = {
    breadcrumbs: [{ title: 'Blog', href: index('posts') }],
};
