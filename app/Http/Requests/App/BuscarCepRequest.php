<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

class BuscarCepRequest extends FormRequest
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
            'cep' => ['required', 'string', 'min:8', 'max:9'],
        ];
    }
}
