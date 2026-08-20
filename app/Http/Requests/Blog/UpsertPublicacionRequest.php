<?php

namespace App\Http\Requests\Blog;

use App\Enums\EstadoPublicacion;
use App\Enums\Permiso;
use App\Enums\TipoPublicacion;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertPublicacionRequest extends FormRequest
{
    /**
     * Los estados que no sacan nada al sitio público ni lo retiran de él.
     *
     * @var list<string>
     */
    private const ESTADOS_SIN_PERMISO = ['borrador', 'revision'];

    public function tipo(): TipoPublicacion
    {
        return TipoPublicacion::desdeSegmento((string) $this->route('tipo'));
    }

    /**
     * El upsert recibe el estado como un campo más, así que sin este guardia
     * cualquiera con permiso de captura podría publicar mandando
     * `estado=publicado` en el formulario, saltándose la ruta de estado que sí
     * está protegida. Sin el permiso de publicar, el estado no se puede llevar
     * a uno público ni cambiar el de algo que ya lo está.
     */
    private function reglaDeEstado(?int $id): Closure
    {
        return function (string $atributo, mixed $valor, Closure $falla) use ($id): void {
            if ($this->user()?->can(Permiso::BlogPublicacionesPublicar->value)) {
                return;
            }

            // `value()` aplica el cast del modelo, así que lo que vuelve es el
            // enum y no la cadena que llega del formulario.
            $actual = $id === null
                ? null
                : $this->tipo()->modelo()::query()->whereKey($id)->value('estado');

            if ($actual instanceof EstadoPublicacion && $valor === $actual->value) {
                return;
            }

            if (in_array($valor, self::ESTADOS_SIN_PERMISO, true)) {
                return;
            }

            $falla('No tienes permiso para publicar ni retirar publicaciones. Guarda como borrador.');
        };
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
            'estado' => ['required', Rule::enum(EstadoPublicacion::class), $this->reglaDeEstado($id)],
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
