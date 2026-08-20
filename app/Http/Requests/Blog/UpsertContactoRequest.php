<?php

namespace App\Http\Requests\Blog;

use App\Enums\EstadoContacto;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertContactoRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:contacts,id'],
            'estado' => ['required', Rule::enum(EstadoContacto::class)],
            'respuesta' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'estado.required' => 'Selecciona el estado del mensaje.',
        ];
    }
}
