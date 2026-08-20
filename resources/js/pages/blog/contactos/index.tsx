import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Archive, Inbox, MoreHorizontal, Reply, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { ConfirmDeleteDialog } from '@/components/confirm-delete-dialog';
import { CrudFormDialog } from '@/components/crud-form-dialog';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn, DataTableServer } from '@/components/data-table';
import { EstadoBadge } from '@/components/estado-badge';
import { FormSelectField } from '@/components/form-select-field';
import { FormTextareaField } from '@/components/form-textarea-field';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatDateTime } from '@/lib/date';
import { resolveFormErrorMessage } from '@/lib/form-error-message';
import { destroy, index, store } from '@/routes/blog/contactos';

type Opcion = { value: string; label: string };

type ContactoRow = {
    id: number;
    nombre: string;
    email: string;
    mensaje: string;
    estado: string;
    estado_label: string;
    respuesta: string | null;
    responsable: { id: number; nombre: string } | null;
    respondido_at: string | null;
    created_at: string;
};

type ContactoForm = {
    id: number | null;
    estado: string;
    respuesta: string;
};

export default function ContactosIndex({
    contactos,
    paginacion,
    filtros,
    estados,
    nuevos,
}: {
    contactos: ContactoRow[];
    paginacion: DataTableServer;
    filtros: { estado: string };
    estados: Opcion[];
    nuevos: number;
}) {
    const [porEliminar, setPorEliminar] = useState<ContactoRow | null>(null);
    const [respondiendo, setRespondiendo] = useState<ContactoRow | null>(null);
    const form = useForm<ContactoForm>({
        id: null,
        estado: 'respondido',
        respuesta: '',
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

    const cambiarEstado = (registro: ContactoRow, estado: string) => {
        router.post(
            store.url(),
            { id: registro.id, estado },
            {
                preserveScroll: true,
                onError: (errors) =>
                    toast.error(
                        resolveFormErrorMessage(
                            errors,
                            'No se pudo actualizar el mensaje.',
                        ),
                    ),
            },
        );
    };

    const abrirRespuesta = (registro: ContactoRow) => {
        setRespondiendo(registro);
        form.clearErrors();
        form.setData({ id: registro.id, estado: 'respondido', respuesta: '' });
    };

    const enviarRespuesta = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.post(store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                setRespondiendo(null);
                form.reset();
            },
            onError: (errors) =>
                toast.error(
                    resolveFormErrorMessage(
                        errors,
                        'No se pudo enviar la respuesta.',
                    ),
                ),
        });
    };

    const columns: DataTableColumn<ContactoRow>[] = [
        {
            key: 'contacto',
            header: 'De',
            accessor: (registro) => registro.nombre,
            cell: (registro) => (
                <div className="space-y-1">
                    <p className="font-medium">{registro.nombre}</p>
                    <p className="text-xs text-muted-foreground">
                        {registro.email}
                    </p>
                </div>
            ),
        },
        {
            key: 'mensaje',
            header: 'Mensaje',
            cell: (registro) => (
                <div className="max-w-md space-y-1">
                    <p className="text-sm">{registro.mensaje}</p>
                    {registro.respuesta && (
                        <p className="rounded bg-muted/60 p-2 text-xs text-muted-foreground">
                            Respuesta: {registro.respuesta}
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
            key: 'created_at',
            header: 'Recibido',
            cell: (registro) => (
                <span className="text-xs text-muted-foreground">
                    {formatDateTime(registro.created_at)}
                </span>
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
                            onSelect={() => abrirRespuesta(registro)}
                        >
                            <Reply className="mr-2 size-4" /> Responder
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() => cambiarEstado(registro, 'leido')}
                        >
                            <Inbox className="mr-2 size-4" /> Marcar leído
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() =>
                                cambiarEstado(registro, 'archivado')
                            }
                        >
                            <Archive className="mr-2 size-4" /> Archivar
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
            <Head title="Mensajes de contacto" />
            <div className="space-y-4 rounded-xl p-4">
                <div className="rounded-xl border border-sidebar-border/70 bg-sidebar-accent/20 p-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="flex items-center gap-3">
                            <Inbox className="size-5 text-primary" />
                            <div>
                                <h1 className="text-xl font-semibold">
                                    Mensajes de contacto
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    {nuevos === 0
                                        ? 'No hay mensajes nuevos.'
                                        : `${nuevos} mensaje${nuevos === 1 ? '' : 's'} sin atender.`}
                                </p>
                            </div>
                        </div>
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
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    data={contactos}
                    server={paginacion}
                    extraParams={{ estado: filtros.estado }}
                    searchPlaceholder="Buscar por nombre, correo o mensaje..."
                />
            </div>

            <CrudFormDialog
                open={respondiendo !== null}
                onOpenChange={(open) => !open && setRespondiendo(null)}
                title="Responder mensaje"
                description={`Se envía un correo a ${respondiendo?.email ?? ''}`}
                submitLabel="Enviar respuesta"
                processing={form.processing}
                onSubmit={enviarRespuesta}
            >
                <div className="rounded-lg bg-muted/50 p-3 text-sm">
                    {respondiendo?.mensaje}
                </div>

                <FormTextareaField
                    id="contacto-respuesta"
                    label="Respuesta"
                    value={form.data.respuesta}
                    error={form.errors.respuesta}
                    onChange={(event) =>
                        form.setData('respuesta', event.target.value)
                    }
                    rows={6}
                    placeholder="Escribe la respuesta que recibirá por correo."
                />

                <FormSelectField
                    id="contacto-estado"
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
                title="Eliminar mensaje"
                entityLabel="el mensaje de"
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
                                    'No se pudo eliminar el mensaje.',
                                ),
                            ),
                    });
                }}
            />
        </>
    );
}

ContactosIndex.layout = {
    breadcrumbs: [
        { title: 'Blog', href: index() },
        { title: 'Contacto', href: index() },
    ],
};
