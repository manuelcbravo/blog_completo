<?php

namespace App\Http\Requests\Blog;

use App\Enums\EstadoComentario;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertComentarioRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:post_comments,id'],
            'estado' => ['required', Rule::enum(EstadoComentario::class)],
            'respuesta' => ['nullable', 'string', 'max:5000'],
            'notificar' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'estado.required' => 'Selecciona el estado del comentario.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['notificar' => $this->boolean('notificar')]);
    }
}
