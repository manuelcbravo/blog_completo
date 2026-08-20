<?php

namespace App\Http\Requests\Blog;

use App\Enums\EstadoPublicacion;
use App\Enums\TipoPublicacion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertPublicacionRequest extends FormRequest
{
    public function tipo(): TipoPublicacion
    {
        return TipoPublicacion::desdeSegmento((string) $this->route('tipo'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tabla = $this->tipo()->nuevoModelo()->getTable();
        $id = $this->integer('id') ?: null;

        $slugUnico = Rule::unique($tabla, 'slug');

        if ($id !== null) {
            $slugUnico->ignore($id);
        }

        return [
            'id' => ['nullable', 'integer', Rule::exists($tabla, 'id')],
            'titulo' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/', $slugUnico],
            'resumen' => ['nullable', 'string', 'max:255'],
            'tags_seo' => ['required', 'string', 'max:500'],
            'estado' => ['required', Rule::enum(EstadoPublicacion::class)],
            'fecha_publicacion' => [
                'nullable',
                'date',
                Rule::requiredIf($this->input('estado') === EstadoPublicacion::Programado->value),
            ],
            'importante' => ['boolean'],
            'id_categoria' => ['nullable', 'integer', 'exists:categories,id'],
            'id_autor' => ['required', 'integer', 'exists:users,id'],
            'etiquetas' => ['array'],
            'etiquetas.*' => ['integer', 'exists:tags,id'],
            'meta_titulo' => ['nullable', 'string', 'max:255'],
            'meta_descripcion' => ['nullable', 'string', 'max:500'],
            'imagen' => ['nullable', 'image', 'max:4096'],
            'eliminar_imagen' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'titulo.required' => 'El título es obligatorio.',
            'tags_seo.required' => 'Las etiquetas para SEO son obligatorias.',
            'id_autor.required' => 'Selecciona el autor.',
            'slug.regex' => 'El slug sólo admite minúsculas, números y guiones.',
            'slug.unique' => 'Ya existe una publicación con ese slug.',
            'fecha_publicacion.required' => 'Una publicación programada necesita fecha y hora.',
            'imagen.max' => 'La imagen no debe pesar más de 4 MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'importante' => $this->boolean('importante'),
            'eliminar_imagen' => $this->boolean('eliminar_imagen'),
        ]);
    }
}
