<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

class SalvarLocalizacaoRequest extends FormRequest
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
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'precisao' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'velocidade' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'direcao' => ['sometimes', 'nullable', 'numeric', 'between:0,360'],
        ];
    }
}
