import { ChevronDownIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Field, FieldError } from '@/components/ui/field';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type { SelectOption } from '@/types';

type FormMultiSelectFieldProps = {
    id: string;
    label: string;
    values: string[];
    options: SelectOption[];
    onValuesChange: (values: string[]) => void;
    error?: string;
    placeholder?: string;
    emptyLabel?: string;
    maxVisible?: number;
    disabled?: boolean;
};

export function FormMultiSelectField({
    id,
    label,
    values,
    options,
    onValuesChange,
    error,
    placeholder = 'Selecciona una o más opciones',
    emptyLabel = 'Sin opciones disponibles',
    maxVisible = 3,
    disabled,
}: FormMultiSelectFieldProps) {
    const selected = options.filter((option) => values.includes(option.value));
    const visible = selected.slice(0, maxVisible);
    const hidden = selected.length - visible.length;

    const toggleValue = (value: string) => {
        onValuesChange(
            values.includes(value)
                ? values.filter((current) => current !== value)
                : [...values, value],
        );
    };

    return (
        <Field>
            <Label htmlFor={id}>{label}</Label>
            <DropdownMenu>
                <DropdownMenuTrigger
                    id={id}
                    type="button"
                    disabled={disabled}
                    aria-invalid={Boolean(error)}
                    className={cn(
                        'flex min-h-9 w-full items-center justify-between gap-2 rounded-md border border-input bg-transparent px-3 py-1.5 text-sm shadow-xs transition-[color,box-shadow] outline-none',
                        'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
                        'aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40',
                        'disabled:cursor-not-allowed disabled:opacity-50',
                    )}
                >
                    {selected.length > 0 ? (
                        <span className="flex flex-wrap items-center gap-1">
                            {visible.map((option) => (
                                <Badge key={option.value} variant="secondary">
                                    {option.label}
                                </Badge>
                            ))}
                            {hidden > 0 && (
                                <Badge variant="outline">+{hidden}</Badge>
                            )}
                        </span>
                    ) : (
                        <span className="text-muted-foreground">
                            {placeholder}
                        </span>
                    )}
                    <ChevronDownIcon className="size-4 shrink-0 opacity-50" />
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    align="start"
                    className="max-h-64 w-(--radix-dropdown-menu-trigger-width) overflow-y-auto"
                >
                    {options.length > 0 ? (
                        options.map((option) => (
                            <DropdownMenuCheckboxItem
                                key={option.value}
                                checked={values.includes(option.value)}
                                disabled={option.disabled}
                                onSelect={(event) => event.preventDefault()}
                                onCheckedChange={() =>
                                    toggleValue(option.value)
                                }
                            >
                                {option.label}
                            </DropdownMenuCheckboxItem>
                        ))
                    ) : (
                        <p className="px-2 py-1.5 text-sm text-muted-foreground">
                            {emptyLabel}
                        </p>
                    )}
                </DropdownMenuContent>
            </DropdownMenu>
            {error && <FieldError>{error}</FieldError>}
        </Field>
    );
}
