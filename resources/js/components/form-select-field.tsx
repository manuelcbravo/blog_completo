import { Field, FieldError } from '@/components/ui/field';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { SelectOption } from '@/types';

type FormSelectFieldProps = {
    id: string;
    label: string;
    value: string;
    options: SelectOption[];
    onValueChange: (value: string) => void;
    error?: string;
    placeholder?: string;
    emptyLabel?: string;
    disabled?: boolean;
    name?: string;
};

export function FormSelectField({
    id,
    label,
    value,
    options,
    onValueChange,
    error,
    placeholder = 'Selecciona una opción',
    emptyLabel = 'Sin opciones disponibles',
    disabled,
    name,
}: FormSelectFieldProps) {
    return (
        <Field>
            <Label htmlFor={id}>{label}</Label>
            <Select
                value={value}
                onValueChange={onValueChange}
                disabled={disabled}
                name={name}
            >
                <SelectTrigger
                    id={id}
                    className="w-full"
                    aria-invalid={Boolean(error)}
                >
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent>
                    {options.length > 0 ? (
                        options.map((option) => (
                            <SelectItem
                                key={option.value}
                                value={option.value}
                                disabled={option.disabled}
                            >
                                {option.label}
                            </SelectItem>
                        ))
                    ) : (
                        <p className="px-2 py-1.5 text-sm text-muted-foreground">
                            {emptyLabel}
                        </p>
                    )}
                </SelectContent>
            </Select>
            {error && <FieldError>{error}</FieldError>}
        </Field>
    );
}
