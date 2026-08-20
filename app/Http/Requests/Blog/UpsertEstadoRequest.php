<?php

namespace App\Http\Requests\Blog;

use App\Enums\EstadoPublicacion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertEstadoRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::enum(EstadoPublicacion::class)],
            'fecha_publicacion' => [
                'nullable',
                'date',
                Rule::requiredIf($this->input('estado') === EstadoPublicacion::Programado->value),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'estado.required' => 'Selecciona el estado.',
            'fecha_publicacion.required' => 'Una publicación programada necesita fecha.',
        ];
    }
}
