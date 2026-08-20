import { Head, router, useForm, usePage } from '@inertiajs/react';
import { MoreHorizontal, Pencil, Plus, Tags, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { ConfirmDeleteDialog } from '@/components/confirm-delete-dialog';
import { CrudFormDialog } from '@/components/crud-form-dialog';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn, DataTableServer } from '@/components/data-table';
import { FormInputField } from '@/components/form-input-field';
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
import { destroy, index, store } from '@/routes/blog/etiquetas';

type EtiquetaRow = {
    id: number;
    nombre: string;
    slug: string;
    created_at: string;
};

type EtiquetaForm = {
    id: number | null;
    nombre: string;
    slug: string;
};

const VALORES_INICIALES: EtiquetaForm = { id: null, nombre: '', slug: '' };

export default function EtiquetasIndex({
    etiquetas,
    paginacion,
}: {
    etiquetas: EtiquetaRow[];
    paginacion: DataTableServer;
}) {
    const [porEliminar, setPorEliminar] = useState<EtiquetaRow | null>(null);
    const [formMode, setFormMode] = useState<'create' | 'edit' | null>(null);
    const form = useForm<EtiquetaForm>(VALORES_INICIALES);
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

    const abrirEditar = (registro: EtiquetaRow) => {
        form.clearErrors();
        form.setData({
            id: registro.id,
            nombre: registro.nombre,
            slug: registro.slug,
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

    const columns: DataTableColumn<EtiquetaRow>[] = [
        {
            key: 'nombre',
            header: 'Etiqueta',
            accessor: (registro) => registro.nombre,
            cell: (registro) => registro.nombre,
        },
        {
            key: 'slug',
            header: 'Slug',
            cell: (registro) => (
                <span className="text-xs text-muted-foreground">
                    /{registro.slug}
                </span>
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
            <Head title="Etiquetas" />
            <div className="space-y-4 rounded-xl p-4">
                <div className="rounded-xl border border-sidebar-border/70 bg-sidebar-accent/20 p-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="flex items-center gap-3">
                            <Tags className="size-5 text-primary" />
                            <div>
                                <h1 className="text-xl font-semibold">
                                    Etiquetas
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    Palabras clave que relacionan publicaciones
                                    entre sí.
                                </p>
                            </div>
                        </div>
                        <Button onClick={abrirCrear}>
                            <Plus className="mr-2 size-4" /> Nueva etiqueta
                        </Button>
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    data={etiquetas}
                    server={paginacion}
                    searchPlaceholder="Buscar etiqueta..."
                />
            </div>

            <CrudFormDialog
                open={formMode !== null}
                onOpenChange={(open) => !open && setFormMode(null)}
                title={
                    formMode === 'edit' ? 'Editar etiqueta' : 'Nueva etiqueta'
                }
                description="El slug se genera del nombre si lo dejas vacío."
                submitLabel={
                    formMode === 'edit' ? 'Guardar cambios' : 'Guardar'
                }
                processing={form.processing}
                onSubmit={enviar}
            >
                <FormInputField
                    id="etiqueta-nombre"
                    label="Nombre"
                    value={form.data.nombre}
                    error={form.errors.nombre}
                    onChange={(event) =>
                        form.setData('nombre', event.target.value)
                    }
                    placeholder="Ej. inertia"
                />

                <FormInputField
                    id="etiqueta-slug"
                    label="Slug"
                    value={form.data.slug}
                    error={form.errors.slug}
                    onChange={(event) =>
                        form.setData('slug', event.target.value)
                    }
                    placeholder="inertia"
                />
            </CrudFormDialog>

            <ConfirmDeleteDialog
                open={porEliminar !== null}
                onOpenChange={(open) => !open && setPorEliminar(null)}
                title="Eliminar etiqueta"
                entityLabel="la etiqueta"
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
                                    'No se pudo eliminar la etiqueta.',
                                ),
                            ),
                    });
                }}
            />
        </>
    );
}

EtiquetasIndex.layout = {
    breadcrumbs: [
        { title: 'Blog', href: index() },
        { title: 'Etiquetas', href: index() },
    ],
};
