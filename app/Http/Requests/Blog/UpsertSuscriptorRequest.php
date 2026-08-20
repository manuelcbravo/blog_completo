<?php

namespace App\Http\Requests\Blog;

use App\Enums\EstadoSuscriptor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertSuscriptorRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->integer('id') ?: null;
        $emailUnico = Rule::unique('subscribers', 'email');

        if ($id !== null) {
            $emailUnico->ignore($id);
        }

        return [
            'id' => ['nullable', 'integer', 'exists:subscribers,id'],
            'email' => ['required', 'email', 'max:255', $emailUnico],
            'nombre' => ['nullable', 'string', 'max:255'],
            'estado' => ['required', Rule::enum(EstadoSuscriptor::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Ese correo ya está suscrito.',
        ];
    }
}
