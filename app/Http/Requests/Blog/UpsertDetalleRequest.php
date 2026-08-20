<?php

namespace App\Http\Requests\Blog;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpsertDetalleRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'detalle' => ['required', 'string', 'max:255'],
            'archivo' => ['required', 'file', 'max:20480'],
            'orden' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'detalle.required' => 'Describe el recurso.',
            'archivo.required' => 'Adjunta el archivo del recurso.',
            'archivo.max' => 'El archivo no debe pesar más de 20 MB.',
        ];
    }
}
