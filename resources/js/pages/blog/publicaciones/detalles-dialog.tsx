import { router, useForm } from '@inertiajs/react';
import { Download, Paperclip, Trash2 } from 'lucide-react';
import { useRef } from 'react';
import { toast } from 'sonner';
import { CrudFormDialog } from '@/components/crud-form-dialog';
import { FormInputField } from '@/components/form-input-field';
import { Button } from '@/components/ui/button';
import { Field, FieldError } from '@/components/ui/field';
import { Label } from '@/components/ui/label';
import { resolveFormErrorMessage } from '@/lib/form-error-message';
import { destroy, store } from '@/routes/blog/detalles';
import type { PublicacionRow } from './index';

type DetalleForm = {
    detalle: string;
    archivo: File | null;
};

function pesoLegible(bytes: number | null) {
    if (!bytes) {
        return '';
    }

    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export function DialogoDetalles({
    recurso,
    onOpenChange,
}: {
    recurso: PublicacionRow | null;
    onOpenChange: (open: boolean) => void;
}) {
    const inputArchivo = useRef<HTMLInputElement>(null);
    const form = useForm<DetalleForm>({ detalle: '', archivo: null });

    const enviar = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!recurso) {
            return;
        }

        form.post(store.url(recurso.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();

                if (inputArchivo.current) {
                    inputArchivo.current.value = '';
                }
            },
            onError: (errors) =>
                toast.error(
                    resolveFormErrorMessage(
                        errors,
                        'Revisa el archivo y el detalle.',
                    ),
                ),
        });
    };

    const detalles = recurso?.detalles ?? [];

    return (
        <CrudFormDialog
            open={recurso !== null}
            onOpenChange={onOpenChange}
            size="lg"
            title="Archivos del recurso"
            description={recurso?.titulo ?? ''}
            submitLabel="Agregar archivo"
            processing={form.processing}
            onSubmit={enviar}
        >
            <div className="grid gap-4 md:grid-cols-2">
                <FormInputField
                    id="detalle-descripcion"
                    label="Detalle"
                    value={form.data.detalle}
                    error={form.errors.detalle}
                    onChange={(event) =>
                        form.setData('detalle', event.target.value)
                    }
                    placeholder="Ej. Plantilla en Figma"
                />

                <Field>
                    <Label htmlFor="detalle-archivo">Archivo</Label>
                    <input
                        ref={inputArchivo}
                        id="detalle-archivo"
                        type="file"
                        className="w-full rounded-md border border-input px-3 py-1.5 text-sm file:mr-3 file:rounded file:border-0 file:bg-primary file:px-3 file:py-1 file:text-primary-foreground"
                        onChange={(event) =>
                            form.setData(
                                'archivo',
                                event.target.files?.[0] ?? null,
                            )
                        }
                    />
                    {form.errors.archivo && (
                        <FieldError>{form.errors.archivo}</FieldError>
                    )}
                </Field>
            </div>

            <div className="rounded-xl border border-sidebar-border/70">
                {detalles.length === 0 ? (
                    <p className="p-4 text-sm text-muted-foreground">
                        Este recurso todavía no tiene archivos.
                    </p>
                ) : (
                    <ul className="divide-y divide-sidebar-border/70">
                        {detalles.map((detalle) => (
                            <li
                                key={detalle.id}
                                className="flex items-center justify-between gap-3 p-3"
                            >
                                <div className="flex min-w-0 items-center gap-3">
                                    <Paperclip className="size-4 shrink-0 text-muted-foreground" />
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">
                                            {detalle.detalle}
                                        </p>
                                        <p className="truncate text-xs text-muted-foreground">
                                            {detalle.nombre_original}{' '}
                                            {pesoLegible(detalle.tamano)}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex shrink-0 items-center gap-1">
                                    {detalle.url && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            className="size-8"
                                            asChild
                                        >
                                            <a
                                                href={detalle.url}
                                                target="_blank"
                                                rel="noreferrer"
                                                aria-label="Descargar"
                                            >
                                                <Download className="size-4" />
                                            </a>
                                        </Button>
                                    )}
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="size-8 text-destructive"
                                        aria-label="Eliminar archivo"
                                        onClick={() => {
                                            if (!recurso) {
                                                return;
                                            }

                                            router.delete(
                                                destroy.url({
                                                    recurso: recurso.id,
                                                    detalle: detalle.id,
                                                }),
                                                { preserveScroll: true },
                                            );
                                        }}
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </CrudFormDialog>
    );
}
