<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

class GeocodificarAreaRequest extends FormRequest
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
            'logradouro' => ['nullable', 'string', 'max:255'],
            'bairro' => ['required', 'string', 'max:120'],
            'cidade' => ['required', 'string', 'max:120'],
            'uf' => ['required', 'string', 'size:2'],
        ];
    }
}
