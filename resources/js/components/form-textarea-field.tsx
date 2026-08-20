import type { ComponentProps } from 'react';
import { Field, FieldError } from '@/components/ui/field';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type FormTextareaFieldProps = {
    id: string;
    label: string;
    value: string;
    error?: string;
} & Omit<ComponentProps<typeof Textarea>, 'id' | 'value'>;

export function FormTextareaField({
    id,
    label,
    value,
    error,
    ...textareaProps
}: FormTextareaFieldProps) {
    return (
        <Field>
            <Label htmlFor={id}>{label}</Label>
            <Textarea
                id={id}
                value={value}
                aria-invalid={Boolean(error)}
                {...textareaProps}
            />
            {error && <FieldError>{error}</FieldError>}
        </Field>
    );
}
