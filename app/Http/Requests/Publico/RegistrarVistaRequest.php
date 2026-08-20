<?php

namespace App\Http\Requests\Publico;

use App\Enums\TipoPublicacion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarVistaRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::enum(TipoPublicacion::class)],
            'post_id' => ['required', 'integer', 'min:1'],
            'session_id' => ['nullable', 'string', 'max:255'],
            'referer' => ['nullable', 'string', 'max:255'],
        ];
    }
}
