import { ImageIcon, Trash2, Upload } from 'lucide-react';
import { useEffect, useMemo, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Field, FieldError } from '@/components/ui/field';
import { Label } from '@/components/ui/label';

type CampoImagenProps = {
    id: string;
    label: string;
    archivo: File | null;
    urlActual?: string | null;
    error?: string;
    onArchivoChange: (archivo: File | null) => void;
    onEliminarActual?: () => void;
    ayuda?: string;
};

export function CampoImagen({
    id,
    label,
    archivo,
    urlActual,
    error,
    onArchivoChange,
    onEliminarActual,
    ayuda = 'JPG, PNG o WebP de hasta 4 MB.',
}: CampoImagenProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const preview = useMemo(
        () => (archivo ? URL.createObjectURL(archivo) : null),
        [archivo],
    );

    useEffect(() => {
        if (preview === null) {
            return;
        }

        return () => URL.revokeObjectURL(preview);
    }, [preview]);

    const mostrada = preview ?? urlActual ?? null;

    return (
        <Field>
            <Label htmlFor={id}>{label}</Label>
            <div className="flex flex-col gap-3 rounded-xl border border-dashed border-sidebar-border/70 p-3">
                {mostrada ? (
                    <img
                        src={mostrada}
                        alt="Vista previa"
                        className="h-48 w-full rounded-lg object-cover"
                    />
                ) : (
                    <div className="flex h-48 w-full flex-col items-center justify-center gap-2 rounded-lg bg-muted/50 text-muted-foreground">
                        <ImageIcon className="size-8" />
                        <span className="text-xs">Sin imagen</span>
                    </div>
                )}

                <div className="flex flex-wrap items-center gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => inputRef.current?.click()}
                    >
                        <Upload className="mr-2 size-4" />
                        {mostrada ? 'Cambiar imagen' : 'Subir imagen'}
                    </Button>

                    {archivo && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                                onArchivoChange(null);

                                if (inputRef.current) {
                                    inputRef.current.value = '';
                                }
                            }}
                        >
                            Cancelar
                        </Button>
                    )}

                    {!archivo && urlActual && onEliminarActual && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={onEliminarActual}
                        >
                            <Trash2 className="mr-2 size-4" /> Quitar
                        </Button>
                    )}

                    <span className="text-xs text-muted-foreground">
                        {ayuda}
                    </span>
                </div>

                <input
                    ref={inputRef}
                    id={id}
                    type="file"
                    accept="image/*"
                    className="hidden"
                    onChange={(event) =>
                        onArchivoChange(event.target.files?.[0] ?? null)
                    }
                />
            </div>
            {error && <FieldError>{error}</FieldError>}
        </Field>
    );
}
