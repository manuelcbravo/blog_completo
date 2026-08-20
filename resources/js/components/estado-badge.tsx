import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

type Variante = 'neutro' | 'exito' | 'aviso' | 'peligro' | 'info';

const CLASES: Record<Variante, string> = {
    neutro: 'bg-muted text-muted-foreground',
    exito: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    aviso: 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-300',
    peligro: 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300',
    info: 'bg-sky-100 text-sky-900 dark:bg-sky-950 dark:text-sky-300',
};

const MAPA: Record<string, Variante> = {
    borrador: 'neutro',
    revision: 'aviso',
    programado: 'info',
    publicado: 'exito',
    abajo: 'peligro',
    pendiente: 'aviso',
    aprobado: 'exito',
    rechazado: 'peligro',
    spam: 'peligro',
    confirmado: 'exito',
    baja: 'peligro',
    nuevo: 'info',
    leido: 'neutro',
    respondido: 'exito',
    archivado: 'neutro',
};

export function EstadoBadge({
    estado,
    label,
    className,
}: {
    estado: string;
    label: string;
    className?: string;
}) {
    const variante = MAPA[estado] ?? 'neutro';

    return (
        <Badge
            variant="secondary"
            className={cn(
                'border-transparent font-medium',
                CLASES[variante],
                className,
            )}
        >
            {label}
        </Badge>
    );
}
