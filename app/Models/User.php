<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Services\ArquivoMidiaService;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'unidade_id',
    'gestor_id',
    'cep_base_inicio',
    'cep_base_fim',
    'origem_lat',
    'origem_lng',
    'origem_rotulo',
    'foto_path',
    'ativo',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'origem_lat' => 'float',
            'origem_lng' => 'float',
            'ativo' => 'boolean',
        ];
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function fotoUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! filled($this->foto_path)) {
                return null;
            }

            return app(ArquivoMidiaService::class)->urlPublica($this->foto_path);
        });
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    public function gestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestor_id');
    }

    public function vendedores(): HasMany
    {
        return $this->hasMany(User::class, 'gestor_id');
    }

    public function visitas(): HasMany
    {
        return $this->hasMany(Visita::class);
    }
}
