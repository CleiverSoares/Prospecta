<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UsuarioService
{
    public function __construct(
        private readonly ArquivoMidiaService $arquivoMidiaService,
    ) {}

    /**
     * @param  array{q?: string|null, papel?: string|null, incluir_inativos?: bool}  $filtros
     */
    public function listar(array $filtros = [], ?User $ator = null, int $porPagina = 20): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['unidade', 'gestor', 'roles'])
            ->orderBy('name');

        if (! ($filtros['incluir_inativos'] ?? false)) {
            $query->where('ativo', true);
        }

        if ($ator?->hasRole('gestor') && ! $ator->hasRole('adm')) {
            $unidadeId = $ator->unidade_id;
            $query->where(function ($q) use ($unidadeId, $ator) {
                $q->where('unidade_id', $unidadeId)
                    ->orWhere('gestor_id', $ator->id)
                    ->orWhere('id', $ator->id);
            });
        }

        if (filled($filtros['q'] ?? null)) {
            $termo = '%'.$filtros['q'].'%';
            $query->where(function ($q) use ($termo) {
                $q->where('name', 'like', $termo)
                    ->orWhere('email', 'like', $termo);
            });
        }

        if (filled($filtros['papel'] ?? null)) {
            $query->role($filtros['papel']);
        }

        return $query->paginate($porPagina)->withQueryString();
    }

    public function desativar(User $usuario): User
    {
        $usuario->update(['ativo' => false]);

        return $usuario->fresh();
    }

    public function reativar(User $usuario): User
    {
        $usuario->update(['ativo' => true]);

        return $usuario->fresh();
    }

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     password?: string|null,
     *     unidade_id?: int|null,
     *     gestor_id?: int|null,
     *     cep_base_inicio?: string|null,
     *     cep_base_fim?: string|null,
     *     origem_rotulo?: string|null,
     *     origem_lat?: float|null,
     *     origem_lng?: float|null,
     *     role: string,
     *     foto?: UploadedFile|null
     * }  $dados
     */
    public function criar(array $dados): User
    {
        return DB::transaction(function () use ($dados) {
            $usuario = User::query()->create([
                'name' => $dados['name'],
                'email' => $dados['email'],
                'password' => Hash::make($dados['password'] ?? 'password'),
                'unidade_id' => $dados['unidade_id'] ?? null,
                'gestor_id' => $dados['gestor_id'] ?? null,
                'cep_base_inicio' => $dados['cep_base_inicio'] ?? null,
                'cep_base_fim' => $dados['cep_base_fim'] ?? null,
                'origem_rotulo' => $dados['origem_rotulo'] ?? null,
                'origem_lat' => $dados['origem_lat'] ?? null,
                'origem_lng' => $dados['origem_lng'] ?? null,
                'email_verified_at' => now(),
            ]);

            if (($dados['foto'] ?? null) instanceof UploadedFile) {
                $usuario->update(['foto_path' => $this->salvarFoto($usuario, $dados['foto'])]);
            }

            $this->sincronizarPapel($usuario, $dados['role']);

            return $usuario->fresh(['unidade', 'gestor', 'roles']);
        });
    }

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     password?: string|null,
     *     unidade_id?: int|null,
     *     gestor_id?: int|null,
     *     cep_base_inicio?: string|null,
     *     cep_base_fim?: string|null,
     *     origem_rotulo?: string|null,
     *     origem_lat?: float|null,
     *     origem_lng?: float|null,
     *     role: string,
     *     foto?: UploadedFile|null
     * }  $dados
     */
    public function atualizar(User $usuario, array $dados): User
    {
        return DB::transaction(function () use ($usuario, $dados) {
            $payload = [
                'name' => $dados['name'],
                'email' => $dados['email'],
                'unidade_id' => $dados['unidade_id'] ?? null,
                'gestor_id' => $dados['gestor_id'] ?? null,
                'cep_base_inicio' => $dados['cep_base_inicio'] ?? null,
                'cep_base_fim' => $dados['cep_base_fim'] ?? null,
                'origem_rotulo' => $dados['origem_rotulo'] ?? null,
                'origem_lat' => $dados['origem_lat'] ?? null,
                'origem_lng' => $dados['origem_lng'] ?? null,
            ];

            if (! empty($dados['password'])) {
                $payload['password'] = Hash::make($dados['password']);
            }

            if (($dados['foto'] ?? null) instanceof UploadedFile) {
                $payload['foto_path'] = $this->salvarFoto($usuario, $dados['foto'], $usuario->foto_path);
            }

            $usuario->update($payload);
            $this->sincronizarPapel($usuario, $dados['role']);

            return $usuario->fresh(['unidade', 'gestor', 'roles']);
        });
    }

    public function sincronizarPapel(User $usuario, string $role): void
    {
        Role::findOrCreate($role, 'web');
        $usuario->syncRoles([$role]);
    }

    private function salvarFoto(User $usuario, UploadedFile $arquivo, ?string $anterior = null): string
    {
        $this->arquivoMidiaService->apagar($anterior);

        return $this->arquivoMidiaService->salvar($arquivo, 'usuarios/fotos');
    }
}
