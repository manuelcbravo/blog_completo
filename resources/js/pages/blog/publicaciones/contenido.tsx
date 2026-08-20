import { Head, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Clock, Eye, Save } from 'lucide-react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { EditorContenido } from '@/components/editor-contenido';
import { EstadoBadge } from '@/components/estado-badge';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/blog/publicaciones';
import {
    imagen as subirImagen,
    store,
} from '@/routes/blog/publicaciones/contenido';

type TipoProp = {
    valor: string;
    segmento: string;
    etiqueta: string;
    etiquetaPlural: string;
};

type PublicacionProp = {
    id: number;
    titulo: string;
    slug: string;
    estado: string;
    estado_label: string;
    tiempo_lectura: number;
    visitas: number;
    url_publica: string;
};

export default function ContenidoPublicacion({
    tipo,
    publicacion,
    contenido,
}: {
    tipo: TipoProp;
    publicacion: PublicacionProp;
    contenido: string;
}) {
    const form = useForm<{ contenido: string }>({ contenido });
    const { flash } = usePage().props;

    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash?.error, flash?.success]);

    const guardar = () => {
        form.post(
            store.url({ tipo: tipo.segmento, publicacion: publicacion.id }),
            {
                preserveScroll: true,
                onError: (errors) =>
                    toast.error(
                        errors.contenido ?? 'No se pudo guardar el contenido.',
                    ),
            },
        );
    };

    const subir = async (archivo: File): Promise<string | null> => {
        const datos = new FormData();
        datos.append('imagen', archivo);

        try {
            const respuesta = await fetch(subirImagen.url(tipo.segmento), {
                method: 'POST',
                body: datos,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') ?? '',
                },
            });

            if (!respuesta.ok) {
                toast.error('No se pudo subir la imagen.');

                return null;
            }

            const cuerpo = (await respuesta.json()) as { url: string };

            return cuerpo.url;
        } catch {
            toast.error('No se pudo subir la imagen.');

            return null;
        }
    };

    return (
        <>
            <Head title={`Contenido · ${publicacion.titulo}`} />
            <div className="space-y-4 rounded-xl p-4">
                <div className="rounded-xl border border-sidebar-border/70 bg-sidebar-accent/20 p-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="space-y-1">
                            <div className="flex items-center gap-2">
                                <h1 className="text-xl font-semibold">
                                    {publicacion.titulo}
                                </h1>
                                <EstadoBadge
                                    estado={publicacion.estado}
                                    label={publicacion.estado_label}
                                />
                            </div>
                            <p className="flex items-center gap-4 text-xs text-muted-foreground">
                                <span className="flex items-center gap-1">
                                    <Clock className="size-3" />{' '}
                                    {publicacion.tiempo_lectura} min de lectura
                                </span>
                                <span className="flex items-center gap-1">
                                    <Eye className="size-3" />{' '}
                                    {publicacion.visitas} vistas
                                </span>
                                <span>/{publicacion.slug}</span>
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                onClick={() =>
                                    router.get(index.url(tipo.segmento))
                                }
                            >
                                <ArrowLeft className="mr-2 size-4" /> Regresar
                            </Button>
                            <Button
                                onClick={guardar}
                                disabled={form.processing}
                            >
                                <Save className="mr-2 size-4" /> Guardar
                                contenido
                            </Button>
                        </div>
                    </div>
                </div>

                <EditorContenido
                    value={form.data.contenido}
                    onChange={(valor) => form.setData('contenido', valor)}
                    onSubirImagen={subir}
                />

                {form.errors.contenido && (
                    <p className="text-sm text-destructive">
                        {form.errors.contenido}
                    </p>
                )}
            </div>
        </>
    );
}

ContenidoPublicacion.layout = {
    breadcrumbs: [{ title: 'Blog', href: index('posts') }],
};
