<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

class BuscarProspectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('prospectos.buscar') ?? false;
    }

    public function rules(): array
    {
        return [
            'cnpj' => ['required', 'string', 'regex:/^\d{14}$|^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'cnpj.required' => 'Informe o CNPJ.',
            'cnpj.regex' => 'CNPJ inválido.',
        ];
    }
}
