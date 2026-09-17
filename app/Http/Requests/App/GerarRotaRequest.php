<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

class GerarRotaRequest extends FormRequest
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
            'prospecto_ids' => ['required', 'array', 'min:1'],
            'prospecto_ids.*' => ['integer', 'distinct', 'exists:prospectos,id'],
            'limite' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50'],
            'local' => ['required', 'string', 'max:255'],
            'segmento' => ['required', 'string', 'max:40'],
            'horas' => ['required', 'string', 'max:40'],
            'mix_prospeccao' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'local.required' => 'Complete o Setup do dia (Local) antes de gerar a rota.',
            'segmento.required' => 'Complete o Setup do dia (Segmento) antes de gerar a rota.',
            'horas.required' => 'Complete o Setup do dia (Horas) antes de gerar a rota.',
            'mix_prospeccao.required' => 'Complete o Setup do dia (% Prospecção) antes de gerar a rota.',
        ];
    }
}
