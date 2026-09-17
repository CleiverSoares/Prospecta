<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

class ReservarCercaRequest extends FormRequest
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
            'rotulo' => ['sometimes', 'nullable', 'string', 'max:120'],
            'cep_inicio' => ['sometimes', 'nullable', 'string', 'max:9'],
            'cep_fim' => ['sometimes', 'nullable', 'string', 'max:9'],
            'poligono_geojson' => ['sometimes', 'nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cep_inicio' => filled($this->cep_inicio) ? preg_replace('/\D+/', '', (string) $this->cep_inicio) : null,
            'cep_fim' => filled($this->cep_fim) ? preg_replace('/\D+/', '', (string) $this->cep_fim) : null,
        ]);
    }
}
