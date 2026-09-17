<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PapeisEPermissoesSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissoes = [
            'unidades.ver',
            'unidades.criar',
            'unidades.editar',
            'unidades.excluir',
            'usuarios.ver',
            'usuarios.criar',
            'usuarios.editar',
            'prospectos.ver',
            'prospectos.buscar',
            'territorio.verificar',
            'visitas.criar',
            'visitas.ver',
            'admin.acessar',
            'app.acessar',
            'papeis.gerenciar',
        ];

        foreach ($permissoes as $nome) {
            Permission::findOrCreate($nome, 'web');
        }

        $adm = Role::findOrCreate('adm', 'web');
        $gestor = Role::findOrCreate('gestor', 'web');
        $vendedor = Role::findOrCreate('vendedor', 'web');

        $adm->syncPermissions($permissoes);

        $gestor->syncPermissions([
            'unidades.ver',
            'usuarios.ver',
            'usuarios.criar',
            'usuarios.editar',
            'prospectos.ver',
            'prospectos.buscar',
            'territorio.verificar',
            'visitas.criar',
            'visitas.ver',
            'admin.acessar',
            'app.acessar',
        ]);

        $vendedor->syncPermissions([
            'prospectos.ver',
            'prospectos.buscar',
            'territorio.verificar',
            'visitas.criar',
            'visitas.ver',
            'app.acessar',
        ]);
    }
}
