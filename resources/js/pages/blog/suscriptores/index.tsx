import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Mail, MoreHorizontal, Pencil, Plus, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { ConfirmDeleteDialog } from '@/components/confirm-delete-dialog';
import { CrudFormDialog } from '@/components/crud-form-dialog';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn, DataTableServer } from '@/components/data-table';
import { EstadoBadge } from '@/components/estado-badge';
import { FormInputField } from '@/components/form-input-field';
import { FormSelectField } from '@/components/form-select-field';
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
import { destroy, index, store } from '@/routes/blog/suscriptores';

type Opcion = { value: string; label: string };

type SuscriptorRow = {
    id: number;
    email: string;
    nombre: string | null;
    estado: string;
    estado_label: string;
    origen: string | null;
    confirmado_at: string | null;
    created_at: string;
};

type SuscriptorForm = {
    id: number | null;
    email: string;
    nombre: string;
    estado: string;
};

const VALORES_INICIALES: SuscriptorForm = {
    id: null,
    email: '',
    nombre: '',
    estado: 'confirmado',
};

export default function SuscriptoresIndex({
    suscriptores,
    paginacion,
    filtros,
    estados,
    resumen,
}: {
    suscriptores: SuscriptorRow[];
    paginacion: DataTableServer;
    filtros: { estado: string };
    estados: Opcion[];
    resumen: { total: number; confirmados: number; pendientes: number };
}) {
    const [porEliminar, setPorEliminar] = useState<SuscriptorRow | null>(null);
    const [formMode, setFormMode] = useState<'create' | 'edit' | null>(null);
    const form = useForm<SuscriptorForm>(VALORES_INICIALES);
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

    const abrirEditar = (registro: SuscriptorRow) => {
        form.clearErrors();
        form.setData({
            id: registro.id,
            email: registro.email,
            nombre: registro.nombre ?? '',
            estado: registro.estado,
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

    const columns: DataTableColumn<SuscriptorRow>[] = [
        {
            key: 'email',
            header: 'Correo',
            accessor: (registro) => registro.email,
            cell: (registro) => (
                <div className="space-y-1">
                    <p className="font-medium">{registro.email}</p>
                    {registro.nombre && (
                        <p className="text-xs text-muted-foreground">
                            {registro.nombre}
                        </p>
                    )}
                </div>
            ),
        },
        {
            key: 'estado',
            header: 'Estado',
            cell: (registro) => (
                <EstadoBadge
                    estado={registro.estado}
                    label={registro.estado_label}
                />
            ),
        },
        {
            key: 'origen',
            header: 'Origen',
            cell: (registro) => (
                <span className="text-xs text-muted-foreground">
                    {registro.origen ?? '—'}
                </span>
            ),
        },
        {
            key: 'confirmado_at',
            header: 'Confirmado',
            cell: (registro) => formatDate(registro.confirmado_at),
        },
        {
            key: 'created_at',
            header: 'Alta',
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
            <Head title="Suscriptores" />
            <div className="space-y-4 rounded-xl p-4">
                <div className="rounded-xl border border-sidebar-border/70 bg-sidebar-accent/20 p-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="flex items-center gap-3">
                            <Mail className="size-5 text-primary" />
                            <div>
                                <h1 className="text-xl font-semibold">
                                    Suscriptores
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    {resumen.confirmados} confirmados de{' '}
                                    {resumen.total} · {resumen.pendientes} sin
                                    confirmar
                                </p>
                            </div>
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
                                    onValueChange={(valor) =>
                                        router.get(
                                            index.url(),
                                            valor === 'todos'
                                                ? {}
                                                : { estado: valor },
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
                    data={suscriptores}
                    server={paginacion}
                    extraParams={{ estado: filtros.estado }}
                    searchPlaceholder="Buscar por correo o nombre..."
                />
            </div>

            <CrudFormDialog
                open={formMode !== null}
                onOpenChange={(open) => !open && setFormMode(null)}
                title={
                    formMode === 'edit'
                        ? 'Editar suscriptor'
                        : 'Nuevo suscriptor'
                }
                description="Un alta manual no dispara el correo de confirmación."
                submitLabel={
                    formMode === 'edit' ? 'Guardar cambios' : 'Guardar'
                }
                processing={form.processing}
                onSubmit={enviar}
            >
                <FormInputField
                    id="suscriptor-email"
                    type="email"
                    label="Correo"
                    value={form.data.email}
                    error={form.errors.email}
                    onChange={(event) =>
                        form.setData('email', event.target.value)
                    }
                    placeholder="persona@correo.com"
                />

                <FormInputField
                    id="suscriptor-nombre"
                    label="Nombre"
                    value={form.data.nombre}
                    error={form.errors.nombre}
                    onChange={(event) =>
                        form.setData('nombre', event.target.value)
                    }
                    placeholder="Opcional"
                />

                <FormSelectField
                    id="suscriptor-estado"
                    label="Estado"
                    value={form.data.estado}
                    error={form.errors.estado}
                    onValueChange={(valor) => form.setData('estado', valor)}
                    options={estados}
                />
            </CrudFormDialog>

            <ConfirmDeleteDialog
                open={porEliminar !== null}
                onOpenChange={(open) => !open && setPorEliminar(null)}
                title="Eliminar suscriptor"
                entityLabel="la suscripción de"
                itemName={porEliminar?.email}
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
                                    'No se pudo eliminar el suscriptor.',
                                ),
                            ),
                    });
                }}
            />
        </>
    );
}

SuscriptoresIndex.layout = {
    breadcrumbs: [
        { title: 'Blog', href: index() },
        { title: 'Suscriptores', href: index() },
    ],
};
