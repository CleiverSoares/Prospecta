<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

class ConsultarReceitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cnpj' => ['required', 'string', 'min:14', 'max:18'],
            'prospecto_id' => ['sometimes', 'nullable', 'integer', 'exists:prospectos,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('cnpj')) {
            $this->merge([
                'cnpj' => preg_replace('/\D+/', '', (string) $this->input('cnpj')),
            ]);
        }
    }
}
