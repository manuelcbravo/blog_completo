import { Head, router, usePage } from '@inertiajs/react';
import { Eye, Globe, Link2, Users } from 'lucide-react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn, DataTableServer } from '@/components/data-table';
import { FormSelectField } from '@/components/form-select-field';
import { formatDateTime } from '@/lib/date';
import { index } from '@/routes/blog/visitas';
import type { SelectOption } from '@/types';

type VisitaRow = {
    id: number;
    tipo: string;
    tipo_label: string;
    titulo: string | null;
    ruta: string | null;
    ip: string | null;
    navegador: string;
    user_agent: string | null;
    referer: string | null;
    origen: string;
    session_id: string | null;
    created_at: string | null;
};

type Resumen = {
    total: number;
    ultimos30: number;
    ips: number;
    referidas: number;
};

function Tarjeta({
    icono: Icono,
    etiqueta,
    valor,
    nota,
}: {
    icono: typeof Eye;
    etiqueta: string;
    valor: number;
    nota: string;
}) {
    return (
        <div className="rounded-xl border border-sidebar-border/70 p-4">
            <div className="flex items-center gap-2 text-muted-foreground">
                <Icono className="size-4" />
                <span className="text-xs tracking-wide uppercase">
                    {etiqueta}
                </span>
            </div>
            <p className="mt-2 text-2xl font-semibold">
                {valor.toLocaleString('es-MX')}
            </p>
            <p className="text-xs text-muted-foreground">{nota}</p>
        </div>
    );
}

export default function VisitasIndex({
    visitas,
    paginacion,
    filtros,
    tipos,
    resumen,
}: {
    visitas: VisitaRow[];
    paginacion: DataTableServer;
    filtros: { tipo: string; origen: string };
    tipos: SelectOption[];
    resumen: Resumen;
}) {
    const { flash } = usePage().props;

    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash?.error, flash?.success]);

    const filtrar = (cambios: Partial<typeof filtros>) => {
        const siguiente = { ...filtros, ...cambios };

        router.get(
            index.url(),
            Object.fromEntries(
                Object.entries(siguiente).filter(([, valor]) => valor !== ''),
            ),
            { preserveState: true, preserveScroll: true },
        );
    };

    const columns: DataTableColumn<VisitaRow>[] = [
        {
            key: 'created_at',
            header: 'Cuándo',
            cell: (registro) => (
                <span className="text-xs whitespace-nowrap text-muted-foreground">
                    {registro.created_at
                        ? formatDateTime(registro.created_at)
                        : '—'}
                </span>
            ),
        },
        {
            key: 'que',
            header: 'Qué vio',
            accessor: (registro) => registro.titulo ?? '',
            cell: (registro) => (
                <div className="max-w-xs space-y-1">
                    <p className="truncate font-medium">
                        {registro.titulo ?? '—'}
                    </p>
                    <p className="truncate text-xs text-muted-foreground">
                        {registro.tipo_label}
                        {registro.ruta ? ` · /${registro.ruta}` : ''}
                    </p>
                </div>
            ),
        },
        {
            key: 'origen',
            header: 'De dónde vino',
            cell: (registro) => (
                <div className="space-y-1">
                    <p className="text-sm">{registro.origen}</p>
                    {registro.referer && (
                        <p
                            className="max-w-xs truncate text-xs text-muted-foreground"
                            title={registro.referer}
                        >
                            {registro.referer}
                        </p>
                    )}
                </div>
            ),
        },
        {
            key: 'ip',
            header: 'IP',
            cell: (registro) => (
                <span className="font-mono text-xs">{registro.ip ?? '—'}</span>
            ),
        },
        {
            key: 'navegador',
            header: 'Navegador',
            cell: (registro) => (
                <span
                    className="text-xs whitespace-nowrap text-muted-foreground"
                    title={registro.user_agent ?? undefined}
                >
                    {registro.navegador}
                </span>
            ),
        },
    ];

    return (
        <>
            <Head title="Visitas" />
            <div className="space-y-4 rounded-xl p-4">
                <div className="rounded-xl border border-sidebar-border/70 bg-sidebar-accent/20 p-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="flex items-center gap-3">
                            <Eye className="size-5 text-primary" />
                            <div>
                                <h1 className="text-xl font-semibold">
                                    Visitas
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    Quién abrió tus publicaciones, tu ficha y
                                    tus proyectos.
                                </p>
                            </div>
                        </div>

                        <div className="flex w-full flex-wrap gap-3 sm:w-auto">
                            <div className="w-full sm:w-52">
                                <FormSelectField
                                    id="filtro-tipo"
                                    label=""
                                    value={
                                        filtros.tipo === ''
                                            ? 'todos'
                                            : filtros.tipo
                                    }
                                    onValueChange={(valor) =>
                                        filtrar({
                                            tipo:
                                                valor === 'todos' ? '' : valor,
                                        })
                                    }
                                    options={[
                                        { value: 'todos', label: 'Todo' },
                                        ...tipos,
                                    ]}
                                />
                            </div>
                            <div className="w-full sm:w-44">
                                <FormSelectField
                                    id="filtro-origen"
                                    label=""
                                    value={
                                        filtros.origen === ''
                                            ? 'todos'
                                            : filtros.origen
                                    }
                                    onValueChange={(valor) =>
                                        filtrar({
                                            origen:
                                                valor === 'todos' ? '' : valor,
                                        })
                                    }
                                    options={[
                                        {
                                            value: 'todos',
                                            label: 'Cualquier origen',
                                        },
                                        {
                                            value: 'referido',
                                            label: 'Llegó de otro sitio',
                                        },
                                        { value: 'directo', label: 'Directo' },
                                    ]}
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <Tarjeta
                        icono={Eye}
                        etiqueta="Visitas"
                        valor={resumen.total}
                        nota="Desde el inicio"
                    />
                    <Tarjeta
                        icono={Globe}
                        etiqueta="Últimos 30 días"
                        valor={resumen.ultimos30}
                        nota="Una por sesión y página"
                    />
                    <Tarjeta
                        icono={Users}
                        etiqueta="IPs distintas"
                        valor={resumen.ips}
                        nota="En los últimos 30 días"
                    />
                    <Tarjeta
                        icono={Link2}
                        etiqueta="Con referente"
                        valor={resumen.referidas}
                        nota="Llegaron desde otro sitio"
                    />
                </div>

                <DataTable
                    columns={columns}
                    data={visitas}
                    server={paginacion}
                    extraParams={{
                        tipo: filtros.tipo,
                        origen: filtros.origen,
                    }}
                    searchPlaceholder="Buscar por IP, ruta, referente o navegador..."
                />
            </div>
        </>
    );
}
