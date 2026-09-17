<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

class SalvarSetupRequest extends FormRequest
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
            'local.required' => 'Informe o local do dia.',
            'segmento.required' => 'Informe o segmento.',
            'horas.required' => 'Informe as horas disponíveis.',
            'mix_prospeccao.required' => 'Informe o mix de prospecção.',
        ];
    }
}
