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
            'archivo' => [
                'required',
                'file',
                'max:20480',
                // Se sirven desde el disco público con su URL directa, así que
                // no se aceptan tipos que el navegador ejecute: html, svg o js
                // subidos aquí correrían como página en el dominio de archivos.
                'mimes:pdf,zip,doc,docx,xls,xlsx,ppt,pptx,csv,txt,png,jpg,jpeg,webp,gif',
            ],
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
            'archivo.mimes' => 'Formato no permitido. Se aceptan documentos, hojas de cálculo, comprimidos e imágenes.',
        ];
    }
}
