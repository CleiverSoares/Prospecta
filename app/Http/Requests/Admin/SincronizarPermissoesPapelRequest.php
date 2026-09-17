<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SincronizarPermissoesPapelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('papeis.gerenciar') ?? false;
    }

    public function rules(): array
    {
        return [
            'permissoes' => ['nullable', 'array'],
            'permissoes.*' => ['string', 'exists:permissions,name'],
        ];
    }
}
