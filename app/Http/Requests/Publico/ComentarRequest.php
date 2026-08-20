<?php

namespace App\Http\Requests\Publico;

use App\Enums\TipoPublicacion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComentarRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::enum(TipoPublicacion::class)],
            'post_id' => ['required', 'integer', 'min:1'],
            'parent_id' => ['nullable', 'integer', 'exists:post_comments,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'correo' => ['required', 'email', 'max:255'],
            'contenido' => ['required', 'string', 'min:3', 'max:5000'],
            'sitio_web' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contenido.required' => 'Escribe tu comentario.',
            'sitio_web.prohibited' => 'No pudimos procesar la solicitud.',
        ];
    }
}
