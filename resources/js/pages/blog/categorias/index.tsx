import { Head, router, useForm, usePage } from '@inertiajs/react';
import { FolderTree, MoreHorizontal, Pencil, Plus, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { ConfirmDeleteDialog } from '@/components/confirm-delete-dialog';
import { CrudFormDialog } from '@/components/crud-form-dialog';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn, DataTableServer } from '@/components/data-table';
import { FormInputField } from '@/components/form-input-field';
import { FormTextareaField } from '@/components/form-textarea-field';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatDate } from '@/lib/date';
import { resolveFormErrorMessage } from '@/lib/form-error-message';
import { destroy, index, store } from '@/routes/blog/categorias';

type CategoriaRow = {
    id: number;
    nombre: string;
    slug: string;
    descripcion: string | null;
    publicaciones?: number;
    created_at: string;
};

type CategoriaForm = {
    id: number | null;
    nombre: string;
    slug: string;
    descripcion: string;
};

const VALORES_INICIALES: CategoriaForm = {
    id: null,
    nombre: '',
    slug: '',
    descripcion: '',
};

export default function CategoriasIndex({
    categorias,
    paginacion,
}: {
    categorias: CategoriaRow[];
    paginacion: DataTableServer;
}) {
    const [porEliminar, setPorEliminar] = useState<CategoriaRow | null>(null);
    const [formMode, setFormMode] = useState<'create' | 'edit' | null>(null);
    const form = useForm<CategoriaForm>(VALORES_INICIALES);
    const { flash } = usePage().props;

    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash?.error, flash?.success]);

    const abrirCrear = () => {
        form.reset();
        form.clearErrors();
        setFormMode('create');
    };

    const abrirEditar = (registro: CategoriaRow) => {
        form.clearErrors();
        form.setData({
            id: registro.id,
            nombre: registro.nombre,
            slug: registro.slug,
            descripcion: registro.descripcion ?? '',
        });
        setFormMode('edit');
    };

    const enviar = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.post(store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                setFormMode(null);
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

    const columns: DataTableColumn<CategoriaRow>[] = [
        {
            key: 'nombre',
            header: 'Nombre',
            accessor: (registro) => registro.nombre,
            cell: (registro) => (
                <div className="space-y-1">
                    <p className="font-medium">{registro.nombre}</p>
                    <p className="text-xs text-muted-foreground">
                        /{registro.slug}
                    </p>
                </div>
            ),
        },
        {
            key: 'descripcion',
            header: 'Descripción',
            cell: (registro) => (
                <p className="max-w-md text-sm text-muted-foreground">
                    {registro.descripcion ?? '—'}
                </p>
            ),
        },
        {
            key: 'publicaciones',
            header: 'Publicaciones',
            cell: (registro) => (
                <Badge variant="outline">{registro.publicaciones ?? 0}</Badge>
            ),
        },
        {
            key: 'created_at',
            header: 'Creada',
            cell: (registro) => formatDate(registro.created_at),
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
            <Head title="Categorías" />
            <div className="space-y-4 rounded-xl p-4">
                <div className="rounded-xl border border-sidebar-border/70 bg-sidebar-accent/20 p-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="flex items-center gap-3">
                            <FolderTree className="size-5 text-primary" />
                            <div>
                                <h1 className="text-xl font-semibold">
                                    Categorías
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    Agrupan las publicaciones del blog.
                                </p>
                            </div>
                        </div>
                        <Button onClick={abrirCrear}>
                            <Plus className="mr-2 size-4" /> Nueva categoría
                        </Button>
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    data={categorias}
                    server={paginacion}
                    searchPlaceholder="Buscar categoría..."
                />
            </div>

            <CrudFormDialog
                open={formMode !== null}
                onOpenChange={(open) => !open && setFormMode(null)}
                title={
                    formMode === 'edit' ? 'Editar categoría' : 'Nueva categoría'
                }
                description="El slug se genera del nombre si lo dejas vacío."
                submitLabel={
                    formMode === 'edit' ? 'Guardar cambios' : 'Guardar'
                }
                processing={form.processing}
                onSubmit={enviar}
            >
                <FormInputField
                    id="categoria-nombre"
                    label="Nombre"
                    value={form.data.nombre}
                    error={form.errors.nombre}
                    onChange={(event) =>
                        form.setData('nombre', event.target.value)
                    }
                    placeholder="Ej. Laravel"
                />

                <FormInputField
                    id="categoria-slug"
                    label="Slug"
                    value={form.data.slug}
                    error={form.errors.slug}
                    onChange={(event) =>
                        form.setData('slug', event.target.value)
                    }
                    placeholder="laravel"
                />

                <FormTextareaField
                    id="categoria-descripcion"
                    label="Descripción"
                    value={form.data.descripcion}
                    error={form.errors.descripcion}
                    onChange={(event) =>
                        form.setData('descripcion', event.target.value)
                    }
                    rows={3}
                    placeholder="Para qué sirve esta categoría"
                />
            </CrudFormDialog>

            <ConfirmDeleteDialog
                open={porEliminar !== null}
                onOpenChange={(open) => !open && setPorEliminar(null)}
                title="Eliminar categoría"
                entityLabel="la categoría"
                itemName={porEliminar?.nombre}
                onConfirm={() => {
                    if (!porEliminar) {
                        return;
                    }

                    router.delete(destroy.url(porEliminar.id), {
                        preserveScroll: true,
                        onSuccess: () => setPorEliminar(null),
                        onError: (errors) =>
                            toast.error(
                                resolveFormErrorMessage(
                                    errors,
                                    'No se pudo eliminar la categoría.',
                                ),
                            ),
                    });
                }}
            />
        </>
    );
}

CategoriasIndex.layout = {
    breadcrumbs: [
        { title: 'Blog', href: index() },
        { title: 'Categorías', href: index() },
    ],
};
