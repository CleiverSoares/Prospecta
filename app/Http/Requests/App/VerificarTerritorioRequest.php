<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

class VerificarTerritorioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('territorio.verificar') ?? false;
    }

    public function rules(): array
    {
        return [
            'cep' => ['required', 'string', 'max:9', 'regex:/^\d{5}-?\d{3}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'cep.required' => 'Informe o CEP.',
            'cep.regex' => 'CEP inválido. Use 00000-000.',
        ];
    }
}
