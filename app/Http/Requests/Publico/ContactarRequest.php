<?php

namespace App\Http\Requests\Publico;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ContactarRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'mensaje' => ['required', 'string', 'min:10', 'max:5000'],
            'sitio_web' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mensaje.required' => 'Escribe tu mensaje.',
            'mensaje.min' => 'Cuéntanos un poco más.',
            'sitio_web.prohibited' => 'No pudimos procesar la solicitud.',
        ];
    }
}
