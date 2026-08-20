import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    Ban,
    Check,
    MessageSquare,
    MoreHorizontal,
    Reply,
    ShieldAlert,
    Trash2,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { ConfirmDeleteDialog } from '@/components/confirm-delete-dialog';
import { CrudFormDialog } from '@/components/crud-form-dialog';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn, DataTableServer } from '@/components/data-table';
import { EstadoBadge } from '@/components/estado-badge';
import { FormSelectField } from '@/components/form-select-field';
import { FormTextareaField } from '@/components/form-textarea-field';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatDateTime } from '@/lib/date';
import { resolveFormErrorMessage } from '@/lib/form-error-message';
import { destroy, index, store } from '@/routes/blog/comentarios';

type Opcion = { value: string; label: string };

type ComentarioRow = {
    id: number;
    post_id: number;
    tipo: string;
    tipo_label: string;
    nombre: string;
    correo: string;
    contenido: string;
    estado: string;
    estado_label: string;
    publicacion_titulo: string | null;
    respuestas: {
        id: number;
        nombre: string;
        contenido: string;
        created_at: string;
    }[];
    created_at: string;
};

type RespuestaForm = {
    id: number | null;
    estado: string;
    respuesta: string;
    notificar: boolean;
};

export default function ComentariosIndex({
    comentarios,
    paginacion,
    filtros,
    estados,
    tipos,
    pendientes,
}: {
    comentarios: ComentarioRow[];
    paginacion: DataTableServer;
    filtros: { estado: string; tipo: string };
    estados: Opcion[];
    tipos: Opcion[];
    pendientes: number;
}) {
    const [porEliminar, setPorEliminar] = useState<ComentarioRow | null>(null);
    const [respondiendo, setRespondiendo] = useState<ComentarioRow | null>(
        null,
    );
    const form = useForm<RespuestaForm>({
        id: null,
        estado: 'aprobado',
        respuesta: '',
        notificar: true,
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

    const filtrar = (clave: 'estado' | 'tipo', valor: string) => {
        const params: Record<string, string> = {
            estado: filtros.estado,
            tipo: filtros.tipo,
            [clave]: valor === 'todos' ? '' : valor,
        };

        router.get(
            index.url(),
            Object.fromEntries(
                Object.entries(params).filter(([, value]) => value !== ''),
            ),
            { preserveState: true, preserveScroll: true },
        );
    };

    const moderar = (registro: ComentarioRow, estado: string) => {
        router.post(
            store.url(),
            { id: registro.id, estado },
            {
                preserveScroll: true,
                onError: (errors) =>
                    toast.error(
                        resolveFormErrorMessage(
                            errors,
                            'No se pudo actualizar el comentario.',
                        ),
                    ),
            },
        );
    };

    const abrirRespuesta = (registro: ComentarioRow) => {
        setRespondiendo(registro);
        form.clearErrors();
        form.setData({
            id: registro.id,
            estado:
                registro.estado === 'pendiente' ? 'aprobado' : registro.estado,
            respuesta: '',
            notificar: true,
        });
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

    const columns: DataTableColumn<ComentarioRow>[] = [
        {
            key: 'autor',
            header: 'Autor',
            accessor: (registro) => registro.nombre,
            cell: (registro) => (
                <div className="space-y-1">
                    <p className="font-medium">{registro.nombre}</p>
                    <p className="text-xs text-muted-foreground">
                        {registro.correo}
                    </p>
                </div>
            ),
        },
        {
            key: 'comentario',
            header: 'Comentario',
            cell: (registro) => (
                <div className="max-w-md space-y-1">
                    <p className="text-sm">{registro.contenido}</p>
                    <p className="text-xs text-muted-foreground">
                        {registro.tipo_label}: {registro.publicacion_titulo}
                    </p>
                    {registro.respuestas.length > 0 && (
                        <Badge variant="outline">
                            {registro.respuestas.length} respuesta
                            {registro.respuestas.length === 1 ? '' : 's'}
                        </Badge>
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
                            onSelect={() => moderar(registro, 'aprobado')}
                        >
                            <Check className="mr-2 size-4" /> Aprobar
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() => moderar(registro, 'rechazado')}
                        >
                            <Ban className="mr-2 size-4" /> Rechazar
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() => moderar(registro, 'spam')}
                        >
                            <ShieldAlert className="mr-2 size-4" /> Marcar spam
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            onSelect={() => abrirRespuesta(registro)}
                        >
                            <Reply className="mr-2 size-4" /> Responder
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
            <Head title="Comentarios" />
            <div className="space-y-4 rounded-xl p-4">
                <div className="rounded-xl border border-sidebar-border/70 bg-sidebar-accent/20 p-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="flex items-center gap-3">
                            <MessageSquare className="size-5 text-primary" />
                            <div>
                                <h1 className="text-xl font-semibold">
                                    Comentarios
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    {pendientes === 0
                                        ? 'No hay comentarios pendientes.'
                                        : `${pendientes} comentario${pendientes === 1 ? '' : 's'} esperando moderación.`}
                                </p>
                            </div>
                        </div>
                        <div className="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                            <div className="w-full sm:w-44">
                                <FormSelectField
                                    id="filtro-estado"
                                    label=""
                                    value={
                                        filtros.estado === ''
                                            ? 'todos'
                                            : filtros.estado
                                    }
                                    onValueChange={(valor) =>
                                        filtrar('estado', valor)
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
                            <div className="w-full sm:w-44">
                                <FormSelectField
                                    id="filtro-tipo"
                                    label=""
                                    value={
                                        filtros.tipo === ''
                                            ? 'todos'
                                            : filtros.tipo
                                    }
                                    onValueChange={(valor) =>
                                        filtrar('tipo', valor)
                                    }
                                    options={[
                                        {
                                            value: 'todos',
                                            label: 'Todos los tipos',
                                        },
                                        ...tipos,
                                    ]}
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    data={comentarios}
                    server={paginacion}
                    extraParams={{ estado: filtros.estado, tipo: filtros.tipo }}
                    searchPlaceholder="Buscar por nombre, correo o texto..."
                />
            </div>

            <CrudFormDialog
                open={respondiendo !== null}
                onOpenChange={(open) => !open && setRespondiendo(null)}
                title="Responder comentario"
                description={`Comentario de ${respondiendo?.nombre ?? ''}`}
                submitLabel="Enviar respuesta"
                processing={form.processing}
                onSubmit={enviarRespuesta}
            >
                <div className="rounded-lg bg-muted/50 p-3 text-sm">
                    {respondiendo?.contenido}
                </div>

                <FormSelectField
                    id="comentario-estado"
                    label="Estado del comentario"
                    value={form.data.estado}
                    error={form.errors.estado}
                    onValueChange={(valor) => form.setData('estado', valor)}
                    options={estados}
                />

                <FormTextareaField
                    id="comentario-respuesta"
                    label="Respuesta"
                    value={form.data.respuesta}
                    error={form.errors.respuesta}
                    onChange={(event) =>
                        form.setData('respuesta', event.target.value)
                    }
                    rows={5}
                    placeholder="Se publica como respuesta anidada en el sitio."
                />

                <label className="flex items-center gap-2 text-sm">
                    <Checkbox
                        checked={form.data.notificar}
                        onCheckedChange={(valor) =>
                            form.setData('notificar', valor === true)
                        }
                    />
                    Avisar por correo a {respondiendo?.correo}
                </label>
            </CrudFormDialog>

            <ConfirmDeleteDialog
                open={porEliminar !== null}
                onOpenChange={(open) => !open && setPorEliminar(null)}
                title="Eliminar comentario"
                entityLabel="el comentario de"
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
                                    'No se pudo eliminar el comentario.',
                                ),
                            ),
                    });
                }}
            />
        </>
    );
}

ComentariosIndex.layout = {
    breadcrumbs: [
        { title: 'Blog', href: index() },
        { title: 'Comentarios', href: index() },
    ],
};
