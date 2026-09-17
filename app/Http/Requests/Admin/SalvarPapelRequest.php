<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarPapelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('papeis.gerenciar') ?? false;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('roles', 'name')],
        ];
    }
}
