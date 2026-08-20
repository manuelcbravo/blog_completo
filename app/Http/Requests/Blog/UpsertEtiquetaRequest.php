<?php

namespace App\Http\Requests\Blog;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertEtiquetaRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->integer('id') ?: null;

        $nombreUnico = Rule::unique('tags', 'nombre');
        $slugUnico = Rule::unique('tags', 'slug');

        if ($id !== null) {
            $nombreUnico->ignore($id);
            $slugUnico->ignore($id);
        }

        return [
            'id' => ['nullable', 'integer', 'exists:tags,id'],
            'nombre' => ['required', 'string', 'max:255', $nombreUnico],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/', $slugUnico],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => 'Ya existe una etiqueta con ese nombre.',
        ];
    }
}
