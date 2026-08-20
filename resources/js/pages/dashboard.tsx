import { Head, Link } from '@inertiajs/react';
import type Highcharts from 'highcharts';
import {
    CalendarClock,
    Eye,
    Inbox,
    MessageSquare,
    PenLine,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useMemo } from 'react';
import { EstadoBadge } from '@/components/estado-badge';
import { Grafica } from '@/components/grafica';
import { Badge } from '@/components/ui/badge';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { formatDate, formatDateTime } from '@/lib/date';
import { dashboard } from '@/routes';
import { index as comentarios } from '@/routes/blog/comentarios';
import { index as contactos } from '@/routes/blog/contactos';
import { index as publicaciones } from '@/routes/blog/publicaciones';
import { index as suscriptores } from '@/routes/blog/suscriptores';

type SerieVista = {
    fecha: string;
    vistas: number;
    post: number;
    tutorial: number;
    recurso: number;
};

type Analitica = {
    resumen: {
        publicadas: number;
        borradores: number;
        vistas: number;
        suscriptores: number;
        suscriptoresNuevos: number;
        comentariosPendientes: number;
        contactosNuevos: number;
        suscriptoresPendientes: number;
        programadas: number;
    };
    serieVistas: SerieVista[];
    porTipo: {
        tipo: string;
        etiqueta: string;
        total: number;
        vistas: number;
    }[];
    top: { titulo: string; tipo: string; vistas: number }[];
    ultimas: {
        titulo: string;
        tipo: string;
        estado: string;
        fecha: string | null;
    }[];
    programadas: {
        titulo: string;
        tipo: string;
        segmento: string;
        fecha: string | null;
        sinFecha: boolean;
    }[];
};

const ESTADO_A_CLAVE: Record<string, string> = {
    Borrador: 'borrador',
    'En revisión': 'revision',
    Programado: 'programado',
    Publicado: 'publicado',
    'Fuera de línea': 'abajo',
};

const SERIES_VISTAS = [
    { clave: 'post', nombre: 'Publicaciones' },
    { clave: 'tutorial', nombre: 'Tutoriales' },
    { clave: 'recurso', nombre: 'Recursos' },
] as const;

function etiquetaDia(fecha: string) {
    const [, mes, dia] = fecha.split('-');

    return `${dia}/${mes}`;
}

export default function Dashboard({
    analitica,
}: {
    analitica: Analitica | null;
}) {
    const vacio = analitica === null;

    const opcionesVistas: Highcharts.Options = useMemo(() => {
        const serieVistas = analitica?.serieVistas ?? [];

        return {
            chart: { type: 'areaspline' },
            xAxis: {
                categories: serieVistas.map((punto) =>
                    etiquetaDia(punto.fecha),
                ),
                tickInterval: 5,
                crosshair: { width: 1, color: 'rgba(120,120,120,.35)' },
            },
            yAxis: { allowDecimals: false },
            tooltip: { shared: true, valueSuffix: ' vistas' },
            legend: { enabled: true, align: 'right', verticalAlign: 'top' },
            plotOptions: {
                areaspline: {
                    stacking: 'normal',
                    fillOpacity: 0.18,
                    marker: {
                        enabled: false,
                        states: { hover: { enabled: true } },
                    },
                },
            },
            series: SERIES_VISTAS.map((serie) => ({
                type: 'areaspline' as const,
                name: serie.nombre,
                data: serieVistas.map((punto) => punto[serie.clave]),
            })),
        };
    }, [analitica]);

    const opcionesTop: Highcharts.Options = useMemo(() => {
        const top = analitica?.top ?? [];

        return {
            chart: { type: 'bar', height: Math.max(220, top.length * 42) },
            xAxis: {
                categories: top.map((fila) => fila.titulo),
                labels: { style: { fontSize: '11px' }, useHTML: false },
            },
            yAxis: { allowDecimals: false },
            tooltip: {
                pointFormat: '<b>{point.y}</b> vistas',
                headerFormat: '{point.key}<br>',
            },
            legend: { enabled: false },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    pointPadding: 0.1,
                    groupPadding: 0.12,
                    dataLabels: { enabled: true, style: { fontWeight: '600' } },
                },
            },
            series: [
                {
                    type: 'bar' as const,
                    name: 'Vistas',
                    data: top.map((fila) => fila.vistas),
                },
            ],
        };
    }, [analitica]);

    if (vacio) {
        return (
            <>
                <Head title="Dashboard" />
                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                    <div className="relative min-h-[60vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                </div>
            </>
        );
    }

    const { resumen, porTipo, top, ultimas, programadas } = analitica;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Tarjeta
                        icono={Eye}
                        titulo="Vistas (30 días)"
                        valor={resumen.vistas}
                        detalle={`${resumen.publicadas} publicaciones en línea`}
                    />
                    <Tarjeta
                        icono={Users}
                        titulo="Suscriptores"
                        valor={resumen.suscriptores}
                        detalle={`${resumen.suscriptoresNuevos} altas en 30 días`}
                        href={suscriptores.url()}
                    />
                    <Tarjeta
                        icono={MessageSquare}
                        titulo="Comentarios por moderar"
                        valor={resumen.comentariosPendientes}
                        detalle="Pendientes de aprobación"
                        href={comentarios.url()}
                        alerta={resumen.comentariosPendientes > 0}
                    />
                    <Tarjeta
                        icono={Inbox}
                        titulo="Mensajes nuevos"
                        valor={resumen.contactosNuevos}
                        detalle="Sin atender"
                        href={contactos.url()}
                        alerta={resumen.contactosNuevos > 0}
                    />
                </div>

                {programadas.length > 0 && (
                    <section className="rounded-xl border border-sidebar-border/70 bg-card p-4">
                        <header className="mb-4 flex items-baseline justify-between">
                            <h2 className="font-semibold">
                                En cola de publicación
                            </h2>
                            <span className="text-xs text-muted-foreground">
                                {resumen.programadas} programada
                                {resumen.programadas === 1 ? '' : 's'}
                            </span>
                        </header>
                        <ul className="space-y-3">
                            {programadas.map((fila, indice) => (
                                <li
                                    key={`${fila.titulo}-${indice}`}
                                    className="flex items-center gap-3"
                                >
                                    <CalendarClock className="size-4 shrink-0 text-sky-600 dark:text-sky-400" />
                                    <div className="min-w-0 flex-1">
                                        <Link
                                            href={publicaciones.url(
                                                fila.segmento,
                                            )}
                                            className="truncate text-sm font-medium hover:underline"
                                        >
                                            {fila.titulo}
                                        </Link>
                                        <p className="text-xs text-muted-foreground">
                                            {fila.tipo}
                                        </p>
                                    </div>
                                    {fila.sinFecha ? (
                                        <Badge variant="destructive">
                                            Sin fecha
                                        </Badge>
                                    ) : (
                                        <Badge variant="outline">
                                            {formatDateTime(fila.fecha)}
                                        </Badge>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <div className="grid gap-4 lg:grid-cols-3">
                    <section className="rounded-xl border border-sidebar-border/70 bg-card p-4 lg:col-span-2">
                        <header className="mb-2 flex items-baseline justify-between">
                            <h2 className="font-semibold">Vistas por día</h2>
                            <span className="text-xs text-muted-foreground">
                                Últimos 30 días
                            </span>
                        </header>
                        {resumen.vistas === 0 ? (
                            <p className="py-16 text-center text-sm text-muted-foreground">
                                Sin vistas registradas en el periodo.
                            </p>
                        ) : (
                            <Grafica
                                options={opcionesVistas}
                                alto={280}
                                etiqueta="Vistas diarias de los últimos 30 días, separadas por publicaciones, tutoriales y recursos"
                            />
                        )}
                    </section>

                    <section className="rounded-xl border border-sidebar-border/70 bg-card p-4">
                        <h2 className="mb-4 font-semibold">
                            Por tipo de contenido
                        </h2>
                        <ul className="space-y-3">
                            {porTipo.map((fila, indice) => (
                                <li
                                    key={fila.tipo}
                                    className="flex items-center justify-between gap-3"
                                >
                                    <div className="flex min-w-0 items-center gap-2">
                                        <span
                                            aria-hidden
                                            className="size-2.5 shrink-0 rounded-full"
                                            style={{
                                                backgroundColor:
                                                    COLOR_LEYENDA[indice],
                                            }}
                                        />
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">
                                                {fila.etiqueta}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {fila.total} registros
                                            </p>
                                        </div>
                                    </div>
                                    <span className="shrink-0 text-sm font-semibold tabular-nums">
                                        {fila.vistas}
                                    </span>
                                </li>
                            ))}
                        </ul>

                        <div className="mt-6 space-y-2 border-t border-sidebar-border/70 pt-4 text-sm">
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">
                                    Borradores
                                </span>
                                <span className="font-medium">
                                    {resumen.borradores}
                                </span>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-muted-foreground">
                                    Suscripciones sin confirmar
                                </span>
                                <span className="font-medium">
                                    {resumen.suscriptoresPendientes}
                                </span>
                            </div>
                        </div>
                    </section>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <section className="rounded-xl border border-sidebar-border/70 bg-card p-4">
                        <h2 className="mb-2 font-semibold">Más leídas</h2>
                        {top.length === 0 ? (
                            <p className="py-16 text-center text-sm text-muted-foreground">
                                Todavía no hay vistas registradas en el periodo.
                            </p>
                        ) : (
                            <Grafica
                                options={opcionesTop}
                                alto={Math.max(220, top.length * 42)}
                                etiqueta="Publicaciones con más vistas en los últimos 30 días"
                            />
                        )}
                    </section>

                    <section className="rounded-xl border border-sidebar-border/70 bg-card p-4">
                        <header className="mb-4 flex items-center justify-between">
                            <h2 className="font-semibold">
                                Últimas publicaciones
                            </h2>
                            <Link
                                href={publicaciones.url('posts')}
                                className="text-xs text-primary underline underline-offset-4"
                            >
                                Ver todas
                            </Link>
                        </header>
                        {ultimas.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Aún no has creado publicaciones.
                            </p>
                        ) : (
                            <ul className="space-y-3">
                                {ultimas.map((fila, indice) => (
                                    <li
                                        key={`${fila.titulo}-${indice}`}
                                        className="flex items-center gap-3"
                                    >
                                        <PenLine className="size-4 shrink-0 text-muted-foreground" />
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium">
                                                {fila.titulo}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {fila.tipo} ·{' '}
                                                {formatDate(fila.fecha)}
                                            </p>
                                        </div>
                                        <EstadoBadge
                                            estado={
                                                ESTADO_A_CLAVE[fila.estado] ??
                                                'borrador'
                                            }
                                            label={fila.estado}
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}

const COLOR_LEYENDA = ['#2a78d6', '#eb6834', '#1baf7a'];

function Tarjeta({
    icono: Icono,
    titulo,
    valor,
    detalle,
    href,
    alerta = false,
}: {
    icono: LucideIcon;
    titulo: string;
    valor: number;
    detalle: string;
    href?: string;
    alerta?: boolean;
}) {
    const contenido = (
        <div className="flex h-full flex-col justify-between rounded-xl border border-sidebar-border/70 bg-card p-4">
            <div className="flex items-start justify-between">
                <span className="text-sm text-muted-foreground">{titulo}</span>
                <Icono
                    className={
                        alerta
                            ? 'size-4 text-amber-600 dark:text-amber-400'
                            : 'size-4 text-primary'
                    }
                />
            </div>
            <p className="mt-3 text-3xl font-semibold tabular-nums">{valor}</p>
            <p className="mt-1 text-xs text-muted-foreground">{detalle}</p>
        </div>
    );

    if (!href) {
        return contenido;
    }

    return (
        <Link href={href} className="block transition-opacity hover:opacity-80">
            {contenido}
        </Link>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
